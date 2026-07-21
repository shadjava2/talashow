<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Mail\InvoiceMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\PayPalService;
use App\Services\SecurityAuditService;
use App\Services\SettingsService;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    public function __construct(private SettingsService $settings, private PayPalService $paypal)
    {}

    public function showRecharge()
    {
        $user = Auth::user();
        $paypalEnabled = $this->paypal->isConfigured();
        $paypalClientId = $this->paypal->getClientId();
        $paypalCurrency = strtoupper((string) $this->settings->get('paypal_currency', 'USD'));

        $prices = [
            'weekly' => (float) $this->settings->get('price_subscription_weekly', (string) config('app.subscription_weekly_price', 16.99)),
            'yearly' => (float) $this->settings->get('price_subscription_yearly', (string) config('app.subscription_yearly_price', 149.99)),
        ];

        $packages = [
            '500' => ['coins' => 500, 'reward' => (int) $this->settings->get('coins_pack_500_reward', '0'), 'price' => (float) $this->settings->get('coins_pack_500_price', '4.99')],
            '1000' => ['coins' => 1000, 'reward' => (int) $this->settings->get('coins_pack_1000_reward', '50'), 'price' => (float) $this->settings->get('coins_pack_1000_price', '9.99')],
            '2000' => ['coins' => 2000, 'reward' => (int) $this->settings->get('coins_pack_2000_reward', '200'), 'price' => (float) $this->settings->get('coins_pack_2000_price', '19.99')],
            '3000' => ['coins' => 3000, 'reward' => (int) $this->settings->get('coins_pack_3000_reward', '1050'), 'price' => (float) $this->settings->get('coins_pack_3000_price', '29.99')],
        ];

        return view('payment.recharge', compact('user', 'paypalEnabled', 'paypalClientId', 'paypalCurrency', 'prices', 'packages'));
    }

    public function showDonation()
    {
        $user = Auth::user();
        $paypalEnabled = $this->paypal->isConfigured();
        $paypalClientId = $this->paypal->getClientId();
        $paypalCurrency = strtoupper((string) $this->settings->get('paypal_currency', 'USD'));

        // Garde-fous UI
        $minDonation = 1.0;
        $maxDonation = 500.0;

        return view('payment.donation', compact(
            'user',
            'paypalEnabled',
            'paypalClientId',
            'paypalCurrency',
            'minDonation',
            'maxDonation'
        ));
    }

    private function getSubscriptionPrice(string $planType): float
    {
        return match ($planType) {
            'weekly' => (float) $this->settings->get('price_subscription_weekly', (string) config('app.subscription_weekly_price', 16.99)),
            default => (float) $this->settings->get('price_subscription_yearly', (string) config('app.subscription_yearly_price', 149.99)),
        };
    }

    private function getCoinPackage(string $packageKey): array
    {
        $map = [
            '500' => ['coins' => 500, 'reward_key' => 'coins_pack_500_reward', 'price_key' => 'coins_pack_500_price', 'price_default' => '4.99', 'reward_default' => '0'],
            '1000' => ['coins' => 1000, 'reward_key' => 'coins_pack_1000_reward', 'price_key' => 'coins_pack_1000_price', 'price_default' => '9.99', 'reward_default' => '50'],
            '2000' => ['coins' => 2000, 'reward_key' => 'coins_pack_2000_reward', 'price_key' => 'coins_pack_2000_price', 'price_default' => '19.99', 'reward_default' => '200'],
            '3000' => ['coins' => 3000, 'reward_key' => 'coins_pack_3000_reward', 'price_key' => 'coins_pack_3000_price', 'price_default' => '29.99', 'reward_default' => '1050'],
        ];
        $m = $map[$packageKey];
        return [
            'coins' => $m['coins'],
            'reward' => (int) $this->settings->get($m['reward_key'], $m['reward_default']),
            'price' => (float) $this->settings->get($m['price_key'], $m['price_default']),
        ];
    }

    public function paypalCreateOrder(Request $request)
    {
        if (!$this->paypal->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Le moyen de paiement n’est pas encore défini. Veuillez patienter.',
            ], 422);
        }

        $validated = $request->validate([
            'kind' => 'required|in:subscription,coins,donation',
            // IMPORTANT: éviter les 500 quand le front envoie un payload incomplet.
            // On force des règles conditionnelles pour renvoyer 422 (JSON) au lieu de planter.
            'plan_type' => 'required_if:kind,subscription|nullable|in:weekly,yearly',
            'coin_package' => 'required_if:kind,coins|nullable|in:500,1000,2000,3000',
            'donation_amount' => 'required_if:kind,donation|nullable|numeric|min:1|max:500',
        ]);

        $user = Auth::user();
        $currency = strtoupper((string) $this->settings->get('paypal_currency', 'USD'));

        $amount = 0.0;
        $type = '';
        $metadata = [];
        $description = '';

        if ($validated['kind'] === 'subscription') {
            $planType = (string) $validated['plan_type'];
            $amount = $this->getSubscriptionPrice($planType);
            $type = 'subscription';
            $metadata = ['plan_type' => $planType];
            $description = 'Abonnement Talashow - ' . ($planType === 'weekly' ? 'Hebdomadaire' : 'Annuel');
        } elseif ($validated['kind'] === 'coins') {
            $packKey = (string) $validated['coin_package'];
            if ($packKey === '') {
                return response()->json(['success' => false, 'message' => 'Pack pièces invalide.'], 422);
            }
            $pack = $this->getCoinPackage($packKey);
            $amount = (float) $pack['price'];
            $type = 'coins';
            $metadata = ['coins' => $pack['coins'], 'reward_coins' => $pack['reward'], 'coin_package' => $packKey];
            $description = $packKey . ' Pièces Talashow';
        } else {
            // Donation libre: pas de pièces / récompenses en retour (don “pur”).
            $amount = (float) ($validated['donation_amount'] ?? 0);
            if ($amount <= 0) {
                return response()->json(['success' => false, 'message' => 'Montant de donation invalide.'], 422);
            }

            $type = 'donation';
            $metadata = ['donation_amount' => $amount];
            $description = 'Donation Talashow';
        }

        try {
            return DB::transaction(function () use ($user, $amount, $currency, $type, $metadata, $description) {
                $tx = Transaction::create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'status' => 'pending',
                    'amount' => $amount,
                    'currency' => $currency,
                    'payment_method' => 'paypal',
                    'metadata' => $metadata,
                ]);

                $order = $this->paypal->createOrder([
                    'intent' => 'CAPTURE',
                    'purchase_units' => [[
                        'reference_id' => (string) $tx->id,
                        'custom_id' => (string) $tx->id,
                        'description' => $description,
                        'amount' => [
                            'currency_code' => $currency,
                            'value' => number_format($amount, 2, '.', ''),
                        ],
                    ]],
                    'application_context' => [
                        'brand_name' => 'Talashow',
                        'user_action' => 'PAY_NOW',
                    ],
                ]);

                $orderId = $order['id'] ?? null;
                if (!$orderId) {
                    throw new \RuntimeException('PayPal: order id manquant.');
                }

                $tx->payment_id = (string) $orderId;
                $tx->save();

                return response()->json([
                    'success' => true,
                    'orderID' => $orderId,
                ]);
            });
        } catch (\Throwable $e) {
            report($e);
            $statusCode = (int) $e->getCode();
            $msg = 'Erreur PayPal. Vérifiez la configuration puis réessayez.';

            // En production on garde un message user-friendly, mais on affiche quand même
            // les erreurs PayPal/cURL (sans infos sensibles) pour faciliter le diagnostic.
            $raw = (string) $e->getMessage();
            $lower = strtolower($raw);
            $safeToShow =
                str_starts_with($raw, 'PayPal ')
                || str_contains($lower, 'paypal ')
                || str_contains($lower, 'oauth')
                || str_contains($lower, 'create order')
                || str_contains($lower, 'capture')
                || str_contains($lower, 'curl')
                || str_contains($lower, 'ssl');

            if ($safeToShow) {
                $msg .= ' (' . $raw . ')';
            } elseif (app()->environment('local') || (bool) config('app.debug')) {
                $msg .= ' ' . $raw;
            }

            if (
                str_contains($lower, 'curl error 60')
                || str_contains($lower, 'unable to get local issuer certificate')
                || str_contains($lower, 'ssl certificate problem')
            ) {
                $msg .= " (Fix: configurez un CA bundle via php.ini curl.cainfo/openssl.cafile ou définissez TALASHOW_CURL_CAINFO=chemin/vers/cacert.pem)";
            }

            // Si PayPal nous répond 4xx, ce n'est pas un bug serveur: renvoyer un code 422/4xx pour que le front affiche correctement.
            if ($statusCode >= 400 && $statusCode < 500) {
                // 422 est fréquent (ex: PAYEE_ACCOUNT_RESTRICTED).
                return response()->json(['success' => false, 'message' => $msg], $statusCode === 422 ? 422 : $statusCode);
            }

            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    public function paypalCaptureOrder(Request $request)
    {
        if (!$this->paypal->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Le moyen de paiement n’est pas encore défini. Veuillez patienter.',
            ], 422);
        }

        $validated = $request->validate([
            'orderID' => 'required|string|max:120',
        ]);

        $user = Auth::user();
        $orderId = (string) $validated['orderID'];

        $tx = Transaction::where('user_id', $user->id)
            ->where('payment_method', 'paypal')
            ->where('payment_id', $orderId)
            ->first();

        if (!$tx) {
            return response()->json(['success' => false, 'message' => 'Transaction introuvable.'], 404);
        }
        if ($tx->status === 'completed') {
            return response()->json(['success' => true, 'message' => 'Déjà confirmé.']);
        }

        try {
            $capture = $this->paypal->captureOrder($orderId);
        } catch (\Throwable $e) {
            report($e);
            $msg = 'Erreur PayPal lors de la validation. Réessayez.';
            if (app()->environment('local') || (bool) config('app.debug')) {
                $msg .= ' ' . $e->getMessage();
            }
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
        $status = (string) ($capture['status'] ?? '');
        if ($status !== 'COMPLETED') {
            $tx->status = 'failed';
            $tx->save();
            return response()->json(['success' => false, 'message' => 'Paiement non complété: ' . $status], 422);
        }

        // Appliquer l’effet métier
        return DB::transaction(function () use ($tx, $user, $capture) {
            if ($tx->type === 'subscription') {
                $planType = (string) ($tx->metadata['plan_type'] ?? 'weekly');
                $startsAt = now();
                $endsAt = $planType === 'weekly' ? $startsAt->copy()->addWeek() : $startsAt->copy()->addYear();

                Subscription::where('user_id', $user->id)->where('is_active', true)->update(['is_active' => false]);
                Subscription::create([
                    'user_id' => $user->id,
                    'plan_type' => $planType,
                    'amount' => $tx->amount,
                    'currency' => $tx->currency,
                    'payment_method' => 'paypal',
                    'payment_id' => $tx->payment_id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'is_active' => true,
                    'auto_renew' => false,
                ]);
            } elseif ($tx->type === 'coins') {
                $coins = (int) ($tx->metadata['coins'] ?? 0);
                $reward = (int) ($tx->metadata['reward_coins'] ?? 0);
                if ($coins > 0) {
                    $user->increment('coins', $coins);
                }
                if ($reward > 0) {
                    $user->increment('reward_coins', $reward);
                }
            } elseif ($tx->type === 'donation') {
                // Donation “pure”: aucun crédit de pièces / récompenses.
            }

            $tx->status = 'completed';
            $tx->save();

            // Envoyer la facture par email après commit (ne doit pas casser le paiement si l'email échoue).
            DB::afterCommit(function () use ($tx) {
                try {
                    $tx->loadMissing('user');
                    /** @var InvoiceService $invoices */
                    $invoices = app(InvoiceService::class);
                    [$title, $lines] = $invoices->buildLines($tx);
                    $invoiceNumber = $invoices->invoiceNumber($tx);
                    $invoiceUrl = $invoices->invoiceUrl($tx);
                    Mail::to($tx->user->email)->send(new InvoiceMail($tx, $invoiceNumber, $title, $lines, $invoiceUrl));
                } catch (\Throwable $e) {
                    report($e);
                }
            });

            return response()->json(['success' => true]);
        });
    }

    public function createSubscription(Request $request)
    {
        $validated = $request->validate([
            'plan_type' => 'required|in:weekly,yearly',
            'payment_method' => 'nullable|string',
        ]);
        return back()->withErrors(['error' => 'Paiement non disponible. Utilise PayPal depuis la page Recharger.']);
    }

    public function purchaseCoins(Request $request)
    {
        $validated = $request->validate([
            'coin_package' => 'required|in:500,1000,2000,3000',
        ]);
        return back()->withErrors(['error' => 'Paiement non disponible. Utilise PayPal depuis la page Recharger.']);
    }

    public function success(Request $request)
    {
        return redirect('/payment/recharge')->with('success', 'Paiement traité.');
    }

    public function cancel()
    {
        return redirect('/payment/recharge')->with('info', 'Paiement annulé.');
    }

    public function webhook(Request $request)
    {
        $endpointSecret = config('services.stripe.webhook_secret');
        if (! is_string($endpointSecret) || $endpointSecret === '') {
            if (app()->environment('production')) {
                SecurityAuditService::securityEvent('stripe_webhook_unconfigured', 'critical', [], $request);
            }

            return response()->json(['error' => 'Webhook non configuré'], app()->environment('production') ? 503 : 400);
        }

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\Exception $e) {
            SecurityAuditService::securityEvent('stripe_webhook_invalid', 'high', [
                'message' => $e->getMessage(),
            ], $request);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Gérer les événements Stripe (renouvellement d'abonnement, etc.) — garder léger (mutualisation).
        if ($event->type === 'customer.subscription.updated') {
            // Logique de mise à jour d'abonnement
        }

        SecurityAuditService::securityEvent('stripe_webhook_ok', 'low', [
            'type' => $event->type,
        ], $request);

        return response()->json(['received' => true]);
    }
}
