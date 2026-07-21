<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TemplateMail;
use App\Services\SecurityAuditService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SettingsController extends Controller
{
    public function __construct(private SettingsService $settings)
    {
        $this->middleware(['auth', 'adminapp']);
    }

    private function extractTawkEmbedUrl(?string $input): ?string
    {
        if ($input === null) return null;
        $s = trim((string) $input);
        if ($s === '') return null;

        // L’admin peut coller l’URL directe OU le script complet.
        // On extrait uniquement l’URL embed.tawk.to pour éviter de stocker du JS brut en base.
        if (preg_match('~https://embed\.tawk\.to/[^\s"\'<>]+~i', $s, $m)) {
            return $m[0];
        }

        // Si c’est une URL simple (sans "embed.tawk.to"), on refuse.
        return null;
    }

    public function edit()
    {
        $values = [
            // Général
            'app_timezone' => $this->settings->get('app_timezone', config('app.timezone', 'UTC')),

            'episode_label_short' => $this->settings->get('episode_label_short', 'EP'),
            'episode_label_singular' => $this->settings->get('episode_label_singular', 'Épisode'),
            'episode_label_plural' => $this->settings->get('episode_label_plural', 'Épisodes'),

            // Bunny Storage (images) — surcharge .env si renseigné
            'bunny_storage_zone_name' => $this->settings->get('bunny_storage_zone_name', config('services.bunny_storage.zone_name')),
            'bunny_storage_region' => $this->settings->get('bunny_storage_region', config('services.bunny_storage.region', 'de')),
            'bunny_storage_cdn_url' => $this->settings->get('bunny_storage_cdn_url', config('services.bunny_storage.cdn_url')),
            'bunny_storage_verify_ssl' => $this->settings->get('bunny_storage_verify_ssl', (string) config('services.bunny_storage.verify_ssl', true)),
            'bunny_storage_api_key_set' => (bool) $this->settings->getSecret('bunny_storage_api_key'),

            // Vidéo : Bunny Stream uniquement (provider forcé côté UI)
            'video_provider' => 'bunny',
            'bunny_stream_library_id' => $this->settings->get('bunny_stream_library_id', config('services.bunny_stream.library_id')),
            'bunny_stream_cdn_hostname' => $this->settings->get('bunny_stream_cdn_hostname', config('services.bunny_stream.cdn_hostname')),
            'bunny_verify_ssl' => $this->settings->get('bunny_verify_ssl', (string) config('services.bunny_stream.verify_ssl', true)),
            'bunny_stream_signed_urls' => $this->settings->get('bunny_stream_signed_urls', (string) config('services.bunny_stream.signed_urls', false)),
            'bunny_stream_api_key_set' => (bool) $this->settings->getSecret('bunny_stream_api_key'),
            'bunny_stream_webhook_secret_set' => (bool) $this->settings->getSecret('bunny_stream_webhook_secret'),
            'bunny_stream_token_security_key_set' => (bool) $this->settings->getSecret('bunny_stream_token_security_key'),
            'bunny_stream_token_key_set' => (bool) $this->settings->getSecret('bunny_stream_token_key'),

            // Tarifs
            'price_subscription_weekly' => $this->settings->get('price_subscription_weekly', (string) config('app.subscription_weekly_price', 16.99)),
            'price_subscription_yearly' => $this->settings->get('price_subscription_yearly', (string) config('app.subscription_yearly_price', 149.99)),
            'coins_pack_500_price' => $this->settings->get('coins_pack_500_price', '4.99'),
            'coins_pack_1000_price' => $this->settings->get('coins_pack_1000_price', '9.99'),
            'coins_pack_2000_price' => $this->settings->get('coins_pack_2000_price', '19.99'),
            'coins_pack_3000_price' => $this->settings->get('coins_pack_3000_price', '29.99'),
            'coins_pack_500_reward' => $this->settings->get('coins_pack_500_reward', '0'),
            'coins_pack_1000_reward' => $this->settings->get('coins_pack_1000_reward', '50'),
            'coins_pack_2000_reward' => $this->settings->get('coins_pack_2000_reward', '200'),
            'coins_pack_3000_reward' => $this->settings->get('coins_pack_3000_reward', '1050'),

            // PayPal
            'paypal_mode' => $this->settings->get('paypal_mode', 'sandbox'),
            'paypal_currency' => $this->settings->get('paypal_currency', 'USD'),
            'paypal_client_id' => $this->settings->get('paypal_client_id'),
            'paypal_client_secret_set' => (bool) $this->settings->getSecret('paypal_client_secret'),

            // SMTP (emails)
            'smtp_host' => $this->settings->get('smtp_host', config('mail.mailers.smtp.host')),
            'smtp_port' => $this->settings->get('smtp_port', (string) config('mail.mailers.smtp.port', 587)),
            'smtp_encryption' => $this->settings->get('smtp_encryption', (string) config('mail.mailers.smtp.encryption', 'tls')),
            'smtp_username' => $this->settings->get('smtp_username', config('mail.mailers.smtp.username')),
            'smtp_password_set' => (bool) $this->settings->getSecret('smtp_password'),
            'mail_from_address' => $this->settings->get('mail_from_address', config('mail.from.address')),
            'mail_from_name' => $this->settings->get('mail_from_name', config('mail.from.name')),

            // Communauté (liens sociaux)
            'social_facebook_url' => $this->settings->get('social_facebook_url'),
            'social_youtube_url' => $this->settings->get('social_youtube_url'),
            'social_tiktok_url' => $this->settings->get('social_tiktok_url'),

            // Footer (emails)
            'footer_contact_email' => $this->settings->get('footer_contact_email', 'contact@tala-show.com'),
            'footer_phone' => $this->settings->get('footer_phone'),

            // Branding
            'site_logo_url' => $this->settings->get('site_logo_url'),

            // Contact (footer) - Coopération d'affaires
            'footer_business_label' => $this->settings->get('footer_business_label', "Coopération d'affaires"),
            'footer_business_url' => $this->settings->get('footer_business_url'),

            // Live chat (Tawk.to)
            'tawk_to_enabled' => $this->settings->get('tawk_to_enabled', (string) config('services.tawk.enabled', true)),
            'tawk_to_embed_url' => $this->settings->get('tawk_to_embed_url', (string) config('services.tawk.embed_url', '')),
        ];

        return view('admin.settings.edit', compact('values'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            // Général
            'app_timezone' => ['required', 'string', Rule::in(\DateTimeZone::listIdentifiers())],

            'episode_label_short' => 'required|string|max:10',
            'episode_label_singular' => 'required|string|max:30',
            'episode_label_plural' => 'required|string|max:30',

            // Bunny Storage (images)
            'bunny_storage_zone_name' => 'nullable|string|max:120',
            'bunny_storage_region' => 'nullable|string|max:12',
            'bunny_storage_cdn_url' => 'nullable|url|max:255',
            'bunny_storage_verify_ssl' => 'required|in:true,false,1,0',
            'bunny_storage_api_key' => 'nullable|string|max:500',

            // Vidéo / Bunny Stream
            'video_provider' => 'required|in:bunny',
            'bunny_stream_library_id' => 'nullable|string|max:64',
            'bunny_stream_cdn_hostname' => 'nullable|string|max:255',
            'bunny_verify_ssl' => 'required|in:true,false,1,0',
            'bunny_stream_signed_urls' => 'required|in:true,false,1,0',
            'bunny_stream_api_key' => 'nullable|string|max:500',
            'bunny_stream_webhook_secret' => 'nullable|string|max:500',
            'bunny_stream_token_security_key' => 'nullable|string|max:500',
            'bunny_stream_token_key' => 'nullable|string|max:200',

            // Tarifs
            'price_subscription_weekly' => 'required|numeric|min:0',
            'price_subscription_yearly' => 'required|numeric|min:0',
            'coins_pack_500_price' => 'required|numeric|min:0',
            'coins_pack_1000_price' => 'required|numeric|min:0',
            'coins_pack_2000_price' => 'required|numeric|min:0',
            'coins_pack_3000_price' => 'required|numeric|min:0',
            'coins_pack_500_reward' => 'required|integer|min:0',
            'coins_pack_1000_reward' => 'required|integer|min:0',
            'coins_pack_2000_reward' => 'required|integer|min:0',
            'coins_pack_3000_reward' => 'required|integer|min:0',

            // PayPal
            'paypal_mode' => 'required|in:sandbox,live',
            'paypal_currency' => 'required|string|size:3',
            'paypal_client_id' => 'nullable|string|max:120',
            'paypal_client_secret' => 'nullable|string|max:200',

            // SMTP
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_encryption' => 'nullable|in:tls,ssl,none',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',

            // Communauté
            'social_facebook_url' => 'nullable|url|max:255',
            'social_youtube_url' => 'nullable|url|max:255',
            'social_tiktok_url' => 'nullable|url|max:255',

            // Footer (emails)
            // IMPORTANT: souple (le client peut changer plus tard, sans DNS check)
            'footer_contact_email' => 'nullable|email|max:120',
            'footer_phone' => 'nullable|string|max:40',

            // Branding
            'site_logo_url' => 'nullable|url|max:255',

            // Contact (footer) - Coopération d'affaires
            'footer_business_label' => 'nullable|string|max:60',
            'footer_business_url' => 'nullable|url|max:255',

            // Live chat (Tawk.to)
            'tawk_to_enabled' => 'required|in:true,false,1,0',
            // URL embed ou script complet (on extrait l’URL). On autorise large.
            'tawk_to_widget' => 'nullable|string|max:5000',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                // Général
                $this->settings->set('app_timezone', $validated['app_timezone']);

                // Labels
                $this->settings->set('episode_label_short', $validated['episode_label_short']);
                $this->settings->set('episode_label_singular', $validated['episode_label_singular']);
                $this->settings->set('episode_label_plural', $validated['episode_label_plural']);

                // Bunny Storage
                $this->settings->set('bunny_storage_zone_name', $validated['bunny_storage_zone_name'] ?: null);
                $this->settings->set('bunny_storage_region', $validated['bunny_storage_region'] ?: null);
                $this->settings->set('bunny_storage_cdn_url', $validated['bunny_storage_cdn_url'] ? rtrim((string) $validated['bunny_storage_cdn_url'], '/') : null);
                $this->settings->set('bunny_storage_verify_ssl', (string) $validated['bunny_storage_verify_ssl']);
                if (! empty($validated['bunny_storage_api_key'])) {
                    $this->settings->setSecret('bunny_storage_api_key', $validated['bunny_storage_api_key']);
                }

                // Vidéo / Bunny Stream
                $this->settings->set('video_provider', 'bunny');
                $this->settings->set('bunny_stream_library_id', $validated['bunny_stream_library_id'] ?: null);
                $this->settings->set('bunny_stream_cdn_hostname', $validated['bunny_stream_cdn_hostname'] ?: null);
                $this->settings->set('bunny_verify_ssl', (string) $validated['bunny_verify_ssl']);
                $this->settings->set('bunny_stream_signed_urls', (string) $validated['bunny_stream_signed_urls']);

                if (!empty($validated['bunny_stream_api_key'])) {
                    $this->settings->setSecret('bunny_stream_api_key', $validated['bunny_stream_api_key']);
                }
                if (!empty($validated['bunny_stream_webhook_secret'])) {
                    $this->settings->setSecret('bunny_stream_webhook_secret', $validated['bunny_stream_webhook_secret']);
                }
                if (!empty($validated['bunny_stream_token_security_key'])) {
                    $this->settings->setSecret('bunny_stream_token_security_key', $validated['bunny_stream_token_security_key']);
                }
                if (!empty($validated['bunny_stream_token_key'])) {
                    $this->settings->setSecret('bunny_stream_token_key', $validated['bunny_stream_token_key']);
                }

                // Tarifs
                $this->settings->set('price_subscription_weekly', (string) $validated['price_subscription_weekly']);
                $this->settings->set('price_subscription_yearly', (string) $validated['price_subscription_yearly']);
                $this->settings->set('coins_pack_500_price', (string) $validated['coins_pack_500_price']);
                $this->settings->set('coins_pack_1000_price', (string) $validated['coins_pack_1000_price']);
                $this->settings->set('coins_pack_2000_price', (string) $validated['coins_pack_2000_price']);
                $this->settings->set('coins_pack_3000_price', (string) $validated['coins_pack_3000_price']);
                $this->settings->set('coins_pack_500_reward', (string) $validated['coins_pack_500_reward']);
                $this->settings->set('coins_pack_1000_reward', (string) $validated['coins_pack_1000_reward']);
                $this->settings->set('coins_pack_2000_reward', (string) $validated['coins_pack_2000_reward']);
                $this->settings->set('coins_pack_3000_reward', (string) $validated['coins_pack_3000_reward']);

                // PayPal
                $this->settings->set('paypal_mode', $validated['paypal_mode']);
                $this->settings->set('paypal_currency', strtoupper($validated['paypal_currency']));
                $this->settings->set('paypal_client_id', $validated['paypal_client_id'] ?: null);
                if (!empty($validated['paypal_client_secret'])) {
                    $this->settings->setSecret('paypal_client_secret', $validated['paypal_client_secret']);
                }

                // SMTP (emails)
                $this->settings->set('smtp_host', !empty($validated['smtp_host']) ? trim($validated['smtp_host']) : null);
                $this->settings->set('smtp_port', !empty($validated['smtp_port']) ? (string) $validated['smtp_port'] : null);
                $this->settings->set('smtp_encryption', !empty($validated['smtp_encryption']) ? $validated['smtp_encryption'] : null);
                $this->settings->set('smtp_username', !empty($validated['smtp_username']) ? $validated['smtp_username'] : null);
                if (!empty($validated['smtp_password'])) {
                    $this->settings->setSecret('smtp_password', $validated['smtp_password']);
                }
                $this->settings->set('mail_from_address', !empty($validated['mail_from_address']) ? $validated['mail_from_address'] : null);
                $this->settings->set('mail_from_name', !empty($validated['mail_from_name']) ? $validated['mail_from_name'] : null);

                // Communauté (liens sociaux)
                $this->settings->set('social_facebook_url', $validated['social_facebook_url'] ?: null);
                $this->settings->set('social_youtube_url', $validated['social_youtube_url'] ?: null);
                $this->settings->set('social_tiktok_url', $validated['social_tiktok_url'] ?: null);

                // Footer (emails)
                $this->settings->set('footer_contact_email', $validated['footer_contact_email'] ?: null);
                $this->settings->set('footer_phone', $validated['footer_phone'] ?: null);

                // Branding
                $this->settings->set('site_logo_url', ! empty($validated['site_logo_url']) ? trim((string) $validated['site_logo_url']) : null);

                // Contact (footer) - Coopération d'affaires
                $this->settings->set('footer_business_label', !empty($validated['footer_business_label']) ? $validated['footer_business_label'] : "Coopération d'affaires");
                $this->settings->set('footer_business_url', $validated['footer_business_url'] ?: null);

                // Live chat (Tawk.to)
                $this->settings->set('tawk_to_enabled', (string) $validated['tawk_to_enabled']);
                $tawkUrl = $this->extractTawkEmbedUrl($validated['tawk_to_widget'] ?? null);
                if (($validated['tawk_to_widget'] ?? null) !== null && trim((string) $validated['tawk_to_widget']) !== '' && !$tawkUrl) {
                    throw new \InvalidArgumentException("Code Tawk.to invalide : collez l'URL embed.tawk.to ou le script complet.");
                }
                // Si champ vide => on conserve l’existant (pas de reset involontaire).
                if (($validated['tawk_to_widget'] ?? null) !== null && trim((string) $validated['tawk_to_widget']) !== '') {
                    $this->settings->set('tawk_to_embed_url', $tawkUrl);
                }
            });
        } catch (\Throwable $e) {
            report($e);
            $key = $e instanceof \InvalidArgumentException ? 'tawk_to_widget' : 'paypal_mode';
            $msg = $e instanceof \InvalidArgumentException
                ? $e->getMessage()
                : "Impossible d'enregistrer les paramètres pour le moment. Vérifiez la base de données puis réessayez.";

            return back()->withErrors([$key => $msg])->withInput();
        }

        SecurityAuditService::adminActivity('settings.updated', [
            'fields' => array_keys($validated),
        ], $request);

        return redirect()->route('admin.settings.edit')->with('success', 'Paramètres enregistrés.');
    }

    public function sendTestEmail(Request $request)
    {
        $validated = $request->validate([
            'test_email_to' => 'required|email|max:255',
        ]);

        try {
            Mail::to($validated['test_email_to'])->send(new TemplateMail('system.test_email', []));
        } catch (TransportExceptionInterface $e) {
            // Log pour diagnostiquer les erreurs SMTP (auth, port, TLS, sender rejeté, etc.)
            report($e);
            return back()->with('error', "Impossible d'envoyer l'email de test. Vérifiez SMTP puis réessayez.");
        }

        return back()->with('success', "Email de test envoyé à {$validated['test_email_to']}.");
    }
}

