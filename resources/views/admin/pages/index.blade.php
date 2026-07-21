@extends('admin.layouts.app')

@section('title', 'Admin - Pages')

@section('breadcrumb')
    Pages
@endsection

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold">Pages</h1>
            <p class="text-sm text-gray-400">Contenu éditable du site (conditions, confidentialité, cookies…).</p>
        </div>
        <a href="{{ route('admin.settings.edit') }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-semibold">
            Retour Paramètres
        </a>
    </div>

    <div class="bg-gray-800 rounded-lg overflow-hidden border border-gray-700/60">
        <table class="w-full text-sm">
            <thead class="bg-gray-900/40 text-gray-300">
                <tr>
                    <th class="text-left px-4 py-3">Titre</th>
                    <th class="text-left px-4 py-3">Slug</th>
                    <th class="text-left px-4 py-3">Statut</th>
                    <th class="text-right px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pages as $p)
                    <tr class="border-t border-gray-700/60">
                        <td class="px-4 py-3 font-semibold">{{ $p->title }}</td>
                        <td class="px-4 py-3 text-gray-300">{{ $p->slug }}</td>
                        <td class="px-4 py-3">
                            @if($p->is_active)
                                <span class="inline-flex px-2 py-1 rounded bg-green-500/15 text-green-200 text-xs">Actif</span>
                            @else
                                <span class="inline-flex px-2 py-1 rounded bg-gray-500/15 text-gray-300 text-xs">Inactif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.pages.edit', $p->id) }}" class="px-3 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-semibold">
                                Éditer
                            </a>
                        </td>
                    </tr>
                @endforeach
                @if($pages->isEmpty())
                    <tr class="border-t border-gray-700/60">
                        <td class="px-4 py-6 text-center text-gray-400" colspan="4">Aucune page.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection

