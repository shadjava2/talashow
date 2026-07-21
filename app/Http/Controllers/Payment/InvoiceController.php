<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function show(Request $request, Transaction $transaction, InvoiceService $invoices)
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        // Accès: propriétaire de la transaction OU admin (perm payments.view).
        if ((int) $transaction->user_id !== (int) $user->id && !$user->hasPermission('payments.view')) {
            abort(403, 'Accès non autorisé.');
        }

        [$title, $lines] = $invoices->buildLines($transaction);
        $invoiceNumber = $invoices->invoiceNumber($transaction);

        return view('payment.invoice', [
            'transaction' => $transaction->loadMissing('user'),
            'invoiceNumber' => $invoiceNumber,
            'invoiceTitle' => $title,
            'invoiceLines' => $lines,
        ]);
    }
}

