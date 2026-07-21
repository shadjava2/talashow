<?php

namespace App\Services;

use App\Models\Transaction;

class InvoiceService
{
    public function invoiceNumber(Transaction $tx): string
    {
        return 'INV-' . str_pad((string) $tx->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{0:string,1:array<int,array{label:string,qty:int,unit:float}>}
     */
    public function buildLines(Transaction $tx): array
    {
        $meta = is_array($tx->metadata) ? $tx->metadata : [];

        if ($tx->type === 'subscription') {
            $plan = (string) ($meta['plan_type'] ?? 'weekly');
            $label = $plan === 'yearly' ? 'Abonnement annuel' : 'Abonnement hebdomadaire';
            return [$label, [
                ['label' => $label, 'qty' => 1, 'unit' => (float) $tx->amount],
            ]];
        }

        if ($tx->type === 'coins') {
            $coins = (int) ($meta['coins'] ?? 0);
            $reward = (int) ($meta['reward_coins'] ?? 0);
            $label = 'Achat de pièces';
            $detail = $coins > 0 ? ($coins . ' pièces' . ($reward > 0 ? ' + ' . $reward . ' bonus' : '')) : 'Pack pièces';
            return [$label, [
                ['label' => $detail, 'qty' => 1, 'unit' => (float) $tx->amount],
            ]];
        }

        if ($tx->type === 'donation') {
            return ['Donation', [
                ['label' => 'Donation', 'qty' => 1, 'unit' => (float) $tx->amount],
            ]];
        }

        return ['Paiement', [
            ['label' => (string) ($tx->type ?: 'Transaction'), 'qty' => 1, 'unit' => (float) $tx->amount],
        ]];
    }

    public function invoiceUrl(Transaction $tx): string
    {
        return route('transaction.invoice', $tx->id);
    }
}

