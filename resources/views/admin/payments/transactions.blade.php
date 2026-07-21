@extends('admin.layouts.app')

@section('title', 'Transactions')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold">Transactions</h1>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <div class="p-4 border-b border-gray-800 flex flex-wrap gap-3">
            <form class="flex flex-wrap gap-3">
                <select name="type" class="px-3 py-2 bg-gray-950 border border-gray-800 rounded-lg text-sm">
                    <option value="">Type</option>
                    @foreach(['subscription' => 'Abonnement', 'coins' => 'Pièces', 'unlock_episode' => 'Déblocage'] as $k => $label)
                        <option value="{{ $k }}" {{ request('type') === $k ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="status" class="px-3 py-2 bg-gray-950 border border-gray-800 rounded-lg text-sm">
                    <option value="">Statut</option>
                    @foreach(['pending' => 'pending', 'completed' => 'completed', 'failed' => 'failed', 'refunded' => 'refunded'] as $k => $label)
                        <option value="{{ $k }}" {{ request('status') === $k ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-semibold">Filtrer</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-950">
                    <tr>
                        <th class="text-left p-4">Date</th>
                        <th class="text-left p-4">Utilisateur</th>
                        <th class="text-left p-4">Type</th>
                        <th class="text-left p-4">Statut</th>
                        <th class="text-right p-4">Montant</th>
                        <th class="text-right p-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $t)
                        <tr class="border-t border-gray-800">
                            <td class="p-4 text-gray-300">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                            <td class="p-4">
                                <div class="font-semibold">{{ $t->user->name }}</div>
                                <div class="text-gray-400 text-xs">{{ $t->user->email }}</div>
                            </td>
                            <td class="p-4 text-gray-300">{{ $t->type }}</td>
                            <td class="p-4">
                                <span class="px-2 py-1 rounded text-xs {{ $t->status === 'completed' ? 'bg-green-600' : 'bg-gray-700' }}">
                                    {{ $t->status }}
                                </span>
                            </td>
                            <td class="p-4 text-right font-semibold">${{ number_format($t->amount, 2) }}</td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a
                                        href="{{ route('transaction.invoice', $t->id) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="px-3 py-1 rounded bg-white/10 hover:bg-white/20 text-white text-xs font-semibold transition"
                                    >
                                        Facture
                                    </a>

                                    @if($t->status === 'completed')
                                        <form
                                            method="POST"
                                            action="{{ route('admin.payments.transactions.invoice.resend', $t->id) }}"
                                            onsubmit="return confirm('Renvoyer la facture par email à {{ $t->user->email }} ?')"
                                            class="inline"
                                        >
                                            @csrf
                                            <button
                                                type="submit"
                                                class="px-3 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition"
                                            >
                                                Renvoyer
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-800">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection

