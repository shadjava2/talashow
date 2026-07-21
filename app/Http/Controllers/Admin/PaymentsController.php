<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\InvoiceMail;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PaymentsController extends Controller
{
    public function transactions(Request $request)
    {
        $q = Transaction::query()->with('user')->latest();

        if ($request->filled('type')) {
            $q->where('type', $request->string('type'));
        }
        if ($request->filled('status')) {
            $q->where('status', $request->string('status'));
        }

        $transactions = $q->paginate(25)->withQueryString();

        return view('admin.payments.transactions', compact('transactions'));
    }

    public function resendInvoiceEmail(Request $request, Transaction $transaction, InvoiceService $invoices)
    {
        $transaction->loadMissing('user');

        if (!$transaction->user || !$transaction->user->email) {
            return back()->with('error', 'Impossible : email client introuvable.');
        }

        if ($transaction->status !== 'completed') {
            return back()->with('error', 'Impossible : la transaction n’est pas "completed".');
        }

        try {
            [$title, $lines] = $invoices->buildLines($transaction);
            $invoiceNumber = $invoices->invoiceNumber($transaction);
            $invoiceUrl = $invoices->invoiceUrl($transaction);

            Mail::to($transaction->user->email)->send(
                new InvoiceMail($transaction, $invoiceNumber, $title, $lines, $invoiceUrl)
            );

            return back()->with('success', 'Facture renvoyée au client : ' . $transaction->user->email);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', "Échec de l'envoi de la facture. Vérifie les logs.");
        }
    }

    public function subscriptions(Request $request)
    {
        $q = Subscription::query()->with('user')->latest();

        if ($request->filled('plan_type')) {
            $q->where('plan_type', $request->string('plan_type'));
        }
        if ($request->filled('is_active')) {
            $q->where('is_active', (bool) $request->boolean('is_active'));
        }

        $subscriptions = $q->paginate(25)->withQueryString();

        return view('admin.payments.subscriptions', compact('subscriptions'));
    }
}

