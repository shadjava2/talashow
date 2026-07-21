@extends('admin.layouts.app')

@section('title', 'Admin - Épisodes')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6 gap-4 flex-wrap">
        <div>
            <h1 class="text-3xl font-bold">Épisodes</h1>
            <p class="text-gray-400">{{ $series->title }}</p>
            <p class="text-xs text-gray-500 mt-1">Le <strong>n°</strong> reste l’épisode métier. L’<strong>ordre</strong> contrôle l’affichage (plus petit = plus haut). Bouton <strong>1ʳᵉ position</strong> = lecture / liste en tête.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.series') }}" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg font-semibold transition">← Séries</a>
            <a href="{{ route('admin.episodes.create', $series->id) }}" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">+ Nouvel épisode</a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 rounded-lg bg-green-600/20 border border-green-500/40 text-green-100 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-gray-800 rounded-lg overflow-x-auto">
        <table class="w-full min-w-[700px]">
            <thead class="bg-gray-900">
                <tr>
                    <th class="text-left p-4">Ordre</th>
                    <th class="text-left p-4">N°</th>
                    <th class="text-left p-4">Titre</th>
                    <th class="text-left p-4">Accès</th>
                    <th class="text-left p-4">Vidéo</th>
                    <th class="text-right p-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($episodes as $index => $ep)
                <tr class="border-t border-gray-700 {{ $index === 0 ? 'bg-red-950/20' : '' }}">
                    <td class="p-4 font-mono text-sm text-gray-200">
                        {{ (int) $ep->sort_order }}
                        @if($index === 0)
                            <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] bg-red-600 text-white font-semibold">1er</span>
                        @endif
                    </td>
                    <td class="p-4 font-semibold">{{ $ep->episode_number }}</td>
                    <td class="p-4">{{ $ep->title }}</td>
                    <td class="p-4 text-sm text-gray-300">
                        @if($ep->is_free)
                            Gratuit
                        @elseif($ep->is_premium_only)
                            Abonnement
                        @else
                            {{ $ep->unlock_coins }} pièces
                        @endif
                    </td>
                    <td class="p-4 text-xs text-gray-400">
                        {{ $ep->video_type }}<br>
                        <span class="break-all">{{ Str::limit($ep->video_url, 50) }}</span>
                    </td>
                    <td class="p-4">
                        <div class="flex justify-end gap-2 flex-wrap">
                            @if($index !== 0)
                                <form method="POST" action="{{ route('admin.episodes.promote', [$series->id, $ep->id]) }}">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 bg-red-600 hover:bg-red-500 rounded text-sm font-semibold" title="Afficher en premier">
                                        1ʳᵉ position
                                    </button>
                                </form>
                            @endif
                            <a class="px-3 py-1 bg-gray-700 hover:bg-gray-600 rounded" href="{{ route('episode.show', [$series->slug, $ep->id]) }}">Voir</a>
                            <a class="px-3 py-1 bg-gray-700 hover:bg-gray-600 rounded" href="{{ route('admin.episodes.edit', [$series->id, $ep->id]) }}">Modifier</a>
                            <form method="POST" action="{{ route('admin.episodes.delete', [$series->id, $ep->id]) }}" onsubmit="return confirm('Supprimer cet épisode ?')">
                                @csrf
                                @method('DELETE')
                                <button class="px-3 py-1 bg-red-700 hover:bg-red-600 rounded">Supprimer</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
