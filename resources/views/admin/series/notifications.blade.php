@extends('admin.layouts.app')

@section('title', 'Admin - Notifications série')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-start justify-between gap-4 flex-wrap mb-6">
        <div>
            <h1 class="text-3xl font-bold">Notifications — Série</h1>
            <div class="text-gray-300 mt-2 font-semibold">{{ $series->title }}</div>
            <div class="text-xs text-gray-400 mt-1">
                Total: <span class="font-semibold text-white">{{ (int) ($total ?? 0) }}</span>
                — À notifier: <span class="font-semibold text-white">{{ (int) ($pendingCount ?? 0) }}</span>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.series') }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-semibold transition">
                Retour
            </a>
            <a href="{{ route('admin.series.edit', $series->id) }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-semibold transition">
                Modifier la série
            </a>
        </div>
    </div>

    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700/60 mb-4">
        <form method="GET" class="flex items-center gap-3 flex-wrap">
            <input
                name="q"
                value="{{ $q ?? '' }}"
                class="w-full md:w-96 px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg"
                placeholder="Rechercher (nom, email, locale...)"
            />
            <label class="inline-flex items-center gap-2 text-sm text-gray-300">
                <input type="hidden" name="pending" value="0" />
                <input type="checkbox" name="pending" value="1" class="rounded" {{ ($onlyPending ?? true) ? 'checked' : '' }} />
                <span>Uniquement “à notifier”</span>
            </label>
            <button class="px-4 py-3 bg-white/10 hover:bg-white/20 rounded-lg font-semibold">Filtrer</button>
        </form>
    </div>

    <div class="bg-gray-800 rounded-lg border border-gray-700/60 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-900/60 text-gray-300">
                    <tr>
                        <th class="text-left px-4 py-3">Utilisateur</th>
                        <th class="text-left px-4 py-3">Email</th>
                        <th class="text-left px-4 py-3">Locale</th>
                        <th class="text-left px-4 py-3">Statut</th>
                        <th class="text-left px-4 py-3">Demandé le</th>
                        <th class="text-left px-4 py-3">Notifié le</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/60">
                    @forelse($items as $it)
                        @php
                            $userName = $it->user?->name;
                            $userEmail = $it->user?->email;
                            $email = $userEmail ?: ($it->email ?: '—');
                            $name = $userName ?: '—';
                            $isPending = $it->notified_at === null;
                        @endphp
                        <tr class="hover:bg-white/5">
                            <td class="px-4 py-3 font-semibold">{{ $name }}</td>
                            <td class="px-4 py-3 text-gray-200">{{ $email }}</td>
                            <td class="px-4 py-3 text-gray-300">{{ $it->locale ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if($isPending)
                                    <span class="px-2 py-1 rounded-md bg-amber-500/20 text-amber-100 border border-amber-500/30 text-xs font-semibold">À notifier</span>
                                @else
                                    <span class="px-2 py-1 rounded-md bg-green-600/20 text-green-100 border border-green-600/30 text-xs font-semibold">Notifié</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-300">{{ optional($it->created_at)->format('d/m/Y H:i') ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-300">{{ optional($it->notified_at)->format('d/m/Y H:i') ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-gray-400">Aucune demande de notification.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $items->links() }}
    </div>
</div>
@endsection

