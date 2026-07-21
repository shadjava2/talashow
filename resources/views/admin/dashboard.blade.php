@extends('admin.layouts.app')

@section('title', 'Tableau de Bord Admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @php
        $canPayments = auth()->user()->hasPermission('payments.view');
    @endphp
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold">Tableau de Bord</h1>
            <p class="text-sm text-gray-400 mt-1">Talashow — gestion du contenu et des revenus</p>
        </div>
        @php
            $settings = app(\App\Services\SettingsService::class);
            $siteLogo = $settings->get('site_logo_url') ?: asset('logo.svg');
        @endphp
        <img src="{{ $siteLogo }}" alt="Talashow" class="h-10 w-auto rounded-md shadow-lg shadow-black/20 hidden md:block" onerror="this.onerror=null; this.src='{{ asset('logo.svg') }}';" />
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-gray-800 rounded-lg p-6">
            <h3 class="text-gray-400 text-sm mb-2">Total Séries</h3>
            <p class="text-3xl font-bold">{{ $stats['active_series'] ?? $stats['total_series'] }}</p>
            <p class="text-xs text-gray-500 mt-2">En base: {{ $stats['total_series'] }}</p>
        </div>
        <div class="bg-gray-800 rounded-lg p-6">
            <h3 class="text-gray-400 text-sm mb-2">Total Épisodes</h3>
            <p class="text-3xl font-bold">{{ $stats['active_episodes'] ?? $stats['total_episodes'] }}</p>
            <p class="text-xs text-gray-500 mt-2">En base: {{ $stats['total_episodes'] }}</p>
        </div>
        <div class="bg-gray-800 rounded-lg p-6">
            <h3 class="text-gray-400 text-sm mb-2">Utilisateurs</h3>
            <p class="text-3xl font-bold">{{ $stats['total_users'] }}</p>
        </div>
        @if($canPayments)
            <div class="bg-gray-800 rounded-lg p-6">
                <h3 class="text-gray-400 text-sm mb-2">Revenus</h3>
                <p class="text-3xl font-bold">${{ number_format($stats['total_revenue'], 2) }}</p>
            </div>
        @else
            <div class="bg-gray-800 rounded-lg p-6 opacity-70">
                <h3 class="text-gray-400 text-sm mb-2">Revenus</h3>
                <p class="text-3xl font-bold">—</p>
                <p class="text-xs text-gray-500 mt-2">Non autorisé (rôle Editor)</p>
            </div>
        @endif
    </div>

    <!-- Recent Series -->
    <div class="bg-gray-800 rounded-lg p-6 mb-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Séries Récentes</h2>
            <a href="{{ route('admin.series') }}" class="text-red-500 hover:text-red-400">Voir tout</a>
        </div>
        <div class="space-y-3">
            @foreach($recent_series as $series)
            <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                <div>
                    <h3 class="font-semibold">{{ $series->title }}</h3>
                    <p class="text-sm text-gray-400">{{ $series->episodes_count }} épisodes</p>
                </div>
                <a href="{{ route('admin.series.edit', $series->id) }}" class="text-red-500 hover:text-red-400">Modifier</a>
            </div>
            @endforeach
        </div>
    </div>

    @if($canPayments)
        <!-- Recent Transactions -->
        <div class="bg-gray-800 rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Transactions Récentes</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-700">
                            <th class="text-left p-3">Utilisateur</th>
                            <th class="text-left p-3">Type</th>
                            <th class="text-left p-3">Montant</th>
                            <th class="text-left p-3">Statut</th>
                            <th class="text-left p-3">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recent_transactions as $transaction)
                        <tr class="border-b border-gray-700">
                            <td class="p-3">{{ $transaction->user->name }}</td>
                            <td class="p-3">{{ $transaction->type }}</td>
                            <td class="p-3">${{ number_format($transaction->amount, 2) }}</td>
                            <td class="p-3">
                                <span class="px-2 py-1 rounded text-xs {{ $transaction->status === 'completed' ? 'bg-green-500' : 'bg-yellow-500' }}">
                                    {{ $transaction->status }}
                                </span>
                            </td>
                            <td class="p-3 text-sm text-gray-400">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
