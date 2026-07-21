@extends('admin.layouts.app')

@section('title', 'Admin - Langues vidéo')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between gap-4 flex-wrap mb-6">
        <div>
            <h1 class="text-3xl font-bold">Langues vidéo</h1>
            <p class="text-gray-400 text-sm">Définit les langues disponibles pour les URLs HLS/MP4 (audio/doublage).</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.video-languages.create') }}" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-semibold">
                Ajouter
            </a>
        </div>
    </div>

    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700/60 mb-4">
        <form method="GET" class="flex items-center gap-3 flex-wrap">
            <input
                name="q"
                value="{{ $q ?? '' }}"
                class="w-full md:w-80 px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg"
                placeholder="Rechercher (code, nom...)"
            />
            <button class="px-4 py-3 bg-white/10 hover:bg-white/20 rounded-lg font-semibold">Filtrer</button>
            <a href="{{ route('admin.video-languages.index') }}" class="px-4 py-3 bg-white/5 hover:bg-white/10 rounded-lg font-semibold">Reset</a>
        </form>
    </div>

    <div class="bg-gray-800 rounded-lg border border-gray-700/60 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-900/60 text-gray-300">
                    <tr>
                        <th class="text-left px-4 py-3">Code</th>
                        <th class="text-left px-4 py-3">Nom</th>
                        <th class="text-left px-4 py-3">Nom natif</th>
                        <th class="text-left px-4 py-3">Actif</th>
                        <th class="text-left px-4 py-3">Ordre</th>
                        <th class="text-right px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/60">
                    @forelse($langs as $l)
                        <tr class="hover:bg-white/5">
                            <td class="px-4 py-3 font-mono">{{ $l->code }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $l->name }}</td>
                            <td class="px-4 py-3 text-gray-300">{{ $l->native_name ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if($l->is_active)
                                    <span class="px-2 py-1 rounded-md bg-green-600/20 text-green-200 border border-green-600/30 text-xs font-semibold">Oui</span>
                                @else
                                    <span class="px-2 py-1 rounded-md bg-gray-700/40 text-gray-200 border border-gray-700 text-xs font-semibold">Non</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-200">{{ (int) $l->sort_order }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.video-languages.edit', $l->id) }}" class="px-3 py-2 bg-white/10 hover:bg-white/20 rounded-lg font-semibold">
                                        Modifier
                                    </a>
                                    <form method="POST" action="{{ route('admin.video-languages.destroy', $l->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-2 bg-red-600/20 hover:bg-red-600/30 border border-red-600/30 rounded-lg font-semibold">
                                            Supprimer
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-gray-400">Aucune langue vidéo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $langs->links() }}
    </div>
</div>
@endsection

