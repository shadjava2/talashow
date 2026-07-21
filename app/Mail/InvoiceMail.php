<?php

namespace App\Mail;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Transaction $transaction,
        public string $invoiceNumber,
        public string $invoiceTitle,
        public array $invoiceLines,
        public string $invoiceUrl
    ) {
    }

    public function build()
    {
        return $this->subject('Talashow — Facture ' . $this->invoiceNumber)
            ->view('emails.invoice', [
                'transaction' => $this->transaction,
                'invoiceNumber' => $this->invoiceNumber,
                'invoiceTitle' => $this->invoiceTitle,
                'invoiceLines' => $this->invoiceLines,
                'invoiceUrl' => $this->invoiceUrl,
            ]);
    }
}

