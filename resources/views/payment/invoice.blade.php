@extends('layouts.app')

@section('title', 'Facture ' . $invoiceNumber)

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between gap-3 flex-wrap mb-6 print:hidden">
        <div>
            <h1 class="text-3xl font-bold">Facture</h1>
            <div class="text-sm text-gray-400 mt-1">
                <span class="font-mono">{{ $invoiceNumber }}</span>
                <span class="mx-2">•</span>
                {{ $transaction->created_at->format('d/m/Y H:i') }}
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ url()->previous() }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-semibold transition">
                Retour
            </a>
            <button type="button" onclick="window.print()" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-semibold transition">
                Imprimer
            </button>
        </div>
    </div>

    <div class="bg-gray-900/40 border border-gray-800 rounded-2xl p-6 print:border-0 print:bg-white print:text-black">
        <div class="flex items-start justify-between gap-6 flex-wrap">
            <div>
                <div class="text-xs uppercase tracking-wider text-gray-400 print:text-gray-700">Facture</div>
                <div class="text-xl font-extrabold mt-1">{{ $invoiceNumber }}</div>
                <div class="text-sm text-gray-300 print:text-gray-800 mt-1">{{ $invoiceTitle }}</div>
                <div class="text-xs text-gray-400 print:text-gray-700 mt-2">
                    Date: {{ $transaction->created_at->format('d/m/Y H:i') }}
                </div>
            </div>
            <div class="text-sm">
                <div class="font-semibold">Client</div>
                <div class="text-gray-300 print:text-gray-800">{{ $transaction->user->name }}</div>
                <div class="text-gray-400 print:text-gray-700">{{ $transaction->user->email }}</div>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-950/60 print:bg-gray-100">
                    <tr>
                        <th class="text-left p-3">Description</th>
                        <th class="text-right p-3">Qté</th>
                        <th class="text-right p-3">Prix</th>
                        <th class="text-right p-3">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800 print:divide-gray-200">
                    @foreach($invoiceLines as $line)
                        @php
                            $qty = (int) ($line['qty'] ?? 1);
                            $unit = (float) ($line['unit'] ?? 0);
                            $total = $qty * $unit;
                        @endphp
                        <tr>
                            <td class="p-3 text-gray-200 print:text-gray-900">{{ $line['label'] ?? '' }}</td>
                            <td class="p-3 text-right text-gray-300 print:text-gray-900">{{ $qty }}</td>
                            <td class="p-3 text-right text-gray-300 print:text-gray-900">{{ strtoupper($transaction->currency) }} {{ number_format($unit, 2, '.', ' ') }}</td>
                            <td class="p-3 text-right font-semibold">{{ strtoupper($transaction->currency) }} {{ number_format($total, 2, '.', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-end">
            <div class="w-full max-w-sm">
                <div class="flex items-center justify-between py-2 border-t border-gray-800 print:border-gray-200">
                    <div class="text-gray-300 print:text-gray-800">Statut</div>
                    <div class="font-semibold">{{ $transaction->status }}</div>
                </div>
                <div class="flex items-center justify-between py-2 border-t border-gray-800 print:border-gray-200">
                    <div class="text-gray-300 print:text-gray-800">Sous-total</div>
                    <div class="font-semibold">{{ strtoupper($transaction->currency) }} {{ number_format((float) $transaction->amount, 2, '.', ' ') }}</div>
                </div>
                <div class="flex items-center justify-between py-2 border-t border-gray-800 print:border-gray-200">
                    <div class="text-gray-300 print:text-gray-800">Total</div>
                    <div class="text-lg font-extrabold">{{ strtoupper($transaction->currency) }} {{ number_format((float) $transaction->amount, 2, '.', ' ') }}</div>
                </div>
            </div>
        </div>

        <div class="mt-6 text-xs text-gray-400 print:text-gray-700">
            Paiement: {{ $transaction->payment_method }} • Référence: {{ $transaction->payment_id ?: ('TX-' . $transaction->id) }}
        </div>
    </div>
</div>

<style>
@media print {
  body { background: #fff !important; }
  nav, .print\\:hidden { display: none !important; }
}
</style>
@endsection

