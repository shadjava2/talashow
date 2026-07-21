@extends('admin.layouts.app')

@section('title', 'Admin - Newsletter')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between gap-4 flex-wrap mb-6">
        <div>
            <h1 class="text-3xl font-bold">Newsletter</h1>
            <p class="text-gray-400 text-sm">Liste des emails inscrits + export CSV.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.newsletter.compose') }}"
               class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-semibold transition">
                Envoyer une newsletter
            </a>
            <a href="{{ route('admin.newsletter.export', ['q' => $q]) }}"
               class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-semibold transition">
                Export CSV
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.newsletter.index') }}" class="mb-4">
        <div class="flex items-center gap-2">
            <input
                name="q"
                value="{{ $q }}"
                placeholder="Rechercher un email…"
                class="w-full max-w-md px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg"
            />
            <button class="px-4 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">
                Rechercher
            </button>
        </div>
    </form>

    <div class="bg-gray-800 rounded-lg overflow-hidden border border-gray-700/60">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-900/60 text-gray-200">
                <tr>
                    <th class="text-left px-4 py-3">Email</th>
                    <th class="text-left px-4 py-3">Confirmé</th>
                    <th class="text-left px-4 py-3">Désinscrit</th>
                    <th class="text-left px-4 py-3">Locale</th>
                    <th class="text-left px-4 py-3">Source</th>
                    <th class="text-left px-4 py-3">Créé</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/60">
                @forelse($subscribers as $s)
                    <tr class="hover:bg-white/5">
                        <td class="px-4 py-3 font-semibold text-white">{{ $s->email }}</td>
                        <td class="px-4 py-3">
                            @if($s->confirmed_at)
                                <span class="px-2 py-1 rounded bg-green-600/20 border border-green-600/30 text-green-200 text-xs font-semibold">
                                    Oui
                                </span>
                            @else
                                <span class="px-2 py-1 rounded bg-amber-500/10 border border-amber-500/20 text-amber-200 text-xs font-semibold">
                                    Non
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($s->unsubscribed_at)
                                <span class="px-2 py-1 rounded bg-gray-700/40 border border-gray-600/40 text-gray-200 text-xs font-semibold">
                                    Oui
                                </span>
                            @else
                                <span class="px-2 py-1 rounded bg-green-600/10 border border-green-600/20 text-green-200 text-xs font-semibold">
                                    Non
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-300">{{ $s->locale ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-300">{{ $s->source ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-300">{{ optional($s->created_at)->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-6 text-gray-300" colspan="6">Aucun inscrit.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-4 border-t border-gray-700/60">
            {{ $subscribers->links() }}
        </div>
    </div>
</div>
@endsection

