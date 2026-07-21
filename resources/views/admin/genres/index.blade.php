@extends('admin.layouts.app')

@section('title', 'Admin - Genres')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold">Genres (classements)</h1>
            <p class="text-gray-400 text-sm">Ces genres alimentent la page “Classification”.</p>
        </div>
        <a href="{{ route('admin.genres.create') }}" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">
            + Ajouter
        </a>
    </div>

    <div class="bg-gray-800 rounded-lg overflow-hidden border border-gray-700/60">
        <table class="w-full text-sm">
            <thead class="bg-gray-900/60">
                <tr>
                    <th class="text-left px-4 py-3">Nom</th>
                    <th class="text-left px-4 py-3">Slug</th>
                    <th class="text-left px-4 py-3">Ordre</th>
                    <th class="text-left px-4 py-3">Actif</th>
                    <th class="text-right px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($genres as $g)
                <tr class="border-t border-gray-700/60">
                    <td class="px-4 py-3 font-semibold">{{ $g->name }}</td>
                    <td class="px-4 py-3 text-gray-300">{{ $g->slug }}</td>
                    <td class="px-4 py-3 text-gray-300">{{ $g->sort_order }}</td>
                    <td class="px-4 py-3">
                        @if($g->is_active)
                            <span class="px-2 py-1 rounded bg-green-600/80 text-white text-xs font-semibold">Oui</span>
                        @else
                            <span class="px-2 py-1 rounded bg-gray-700 text-white text-xs font-semibold">Non</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.genres.edit', $g->id) }}" class="px-3 py-2 bg-white/10 hover:bg-white/20 rounded-lg font-semibold">Éditer</a>
                        <form method="POST" action="{{ route('admin.genres.destroy', $g->id) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button onclick="return confirm('Supprimer ce genre ?')" class="px-3 py-2 bg-red-600/80 hover:bg-red-600 rounded-lg font-semibold">
                                Supprimer
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-gray-400">Aucun genre pour le moment.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($genres->hasPages())
        <div class="mt-6">{{ $genres->links() }}</div>
    @endif
</div>
@endsection

