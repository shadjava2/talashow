@extends('admin.layouts.app')

@section('title', 'Admin - Séries')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6 gap-4 flex-wrap">
        <div>
            <h1 class="text-3xl font-bold">Séries</h1>
            <p class="text-sm text-gray-400 mt-1">Plus petit <strong>ordre</strong> = plus haut dans le carrousel. Bouton <strong>1ʳᵉ position</strong> = vedette + première place.</p>
        </div>
        <a href="{{ route('admin.series.create') }}" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">
            + Nouvelle série
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 rounded-lg bg-green-600/20 border border-green-500/40 text-green-100 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-gray-800 rounded-lg overflow-x-auto">
        <table class="w-full min-w-[720px]">
            <thead class="bg-gray-900">
                <tr>
                    <th class="text-left p-4">Ordre</th>
                    <th class="text-left p-4">Titre</th>
                    <th class="text-left p-4">Épisodes</th>
                    <th class="text-left p-4">Visibilité</th>
                    <th class="text-left p-4">Disponibilité</th>
                    <th class="text-left p-4">Statut</th>
                    <th class="text-right p-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($series as $s)
                @php
                    $tz = config('app.timezone');
                    $publishedAt = $s->published_at;
                    $createdAt = $s->created_at;
                    $isScheduled = $publishedAt !== null;
                    $when = $isScheduled ? $publishedAt : $createdAt;
                    $whenLabel = $when ? \Illuminate\Support\Carbon::parse($when)->timezone($tz)->format('d/m/Y H:i') : '—';
                    $isFuture = $isScheduled && \Illuminate\Support\Carbon::parse($publishedAt)->isFuture();
                @endphp
                <tr class="border-t border-gray-700 {{ $s->is_featured ? 'bg-red-950/20' : '' }}">
                    <td class="p-4 font-mono text-sm text-gray-200">{{ (int) $s->sort_order }}</td>
                    <td class="p-4 font-semibold">{{ $s->title }}</td>
                    <td class="p-4 text-gray-300">{{ $s->episodes_count }}</td>
                    <td class="p-4">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            @if($s->is_featured)
                                <span class="px-2 py-1 rounded text-[11px] bg-red-600 text-white font-semibold">Vedette</span>
                            @endif
                            @if($s->is_trending)
                                <span class="px-2 py-1 rounded text-[11px] bg-amber-500/25 text-amber-100">Tendance</span>
                            @endif
                            @if($s->is_exclusive)
                                <span class="px-2 py-1 rounded text-[11px] bg-purple-500/25 text-purple-100">Exclusif</span>
                            @endif
                            @if(! $s->is_featured && ! $s->is_trending && ! $s->is_exclusive)
                                <span class="text-xs text-gray-500">—</span>
                            @endif
                        </div>
                    </td>
                    <td class="p-4">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2 py-1 rounded text-xs {{ $isScheduled ? 'bg-amber-500/20 text-amber-100' : 'bg-blue-500/20 text-blue-100' }}">
                                {{ $isScheduled ? 'Programmé' : 'Immédiat' }}
                            </span>
                            <span class="text-xs text-gray-300">{{ $whenLabel }}</span>
                            @if($isScheduled)
                                <span class="px-2 py-1 rounded text-[11px] {{ $isFuture ? 'bg-gray-700 text-gray-200' : 'bg-green-600/20 text-green-100' }}">
                                    {{ $isFuture ? 'À venir' : 'Disponible' }}
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="p-4">
                        <span class="px-2 py-1 rounded text-xs {{ $s->is_active ? 'bg-green-600' : 'bg-gray-600' }}">
                            {{ $s->is_active ? 'Actif' : 'Inactif' }}
                        </span>
                    </td>
                    <td class="p-4">
                        <div class="flex justify-end gap-2 flex-wrap">
                            <form method="POST" action="{{ route('admin.series.promote', $s->id) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1 bg-red-600 hover:bg-red-500 rounded text-sm font-semibold" title="Mettre en 1ʳᵉ position du carrousel">
                                    1ʳᵉ position
                                </button>
                            </form>
                            <a class="px-3 py-1 bg-gray-700 hover:bg-gray-600 rounded" href="{{ route('admin.episodes', $s->id) }}">Épisodes</a>
                            <a class="px-3 py-1 bg-gray-700 hover:bg-gray-600 rounded" href="{{ route('admin.series.notifications', $s->id) }}">Notifications</a>
                            <a class="px-3 py-1 bg-gray-700 hover:bg-gray-600 rounded" href="{{ route('admin.series.edit', $s->id) }}">Modifier</a>
                            <form method="POST" action="{{ route('admin.series.delete', $s->id) }}" onsubmit="return confirm('Supprimer cette série ?')">
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

    <div class="mt-6">
        {{ $series->links() }}
    </div>
</div>
@endsection
