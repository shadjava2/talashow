@extends('admin.layouts.app')

@section('title', 'Admin - Éditer page')

@section('breadcrumb')
    <a href="{{ route('admin.pages.index') }}" class="text-gray-300 hover:text-white">Pages</a>
    <span class="text-gray-500">/</span>
    <span>{{ $page->title }}</span>
@endsection

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold">Éditer la page</h1>
            <p class="text-sm text-gray-400">Slug: <span class="text-gray-200 font-semibold">{{ $page->slug }}</span></p>
        </div>
        <a href="{{ route('admin.pages.index') }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-semibold">
            Retour
        </a>
    </div>

    <form method="POST" action="{{ route('admin.pages.update', $page->id) }}" class="bg-gray-800 rounded-lg p-6 space-y-4" data-ts-validate="form" novalidate>
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="page_title_fr" class="block text-sm mb-2">Titre (FR)</label>
                <input id="page_title_fr" name="title_fr" value="{{ old('title_fr', $page->titleForLocale('fr')) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" required minlength="2" maxlength="120" />
            </div>
            <div>
                <label for="page_title_en" class="block text-sm mb-2">Title (EN)</label>
                <input id="page_title_en" name="title_en" value="{{ old('title_en', $page->titleForLocale('en')) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" minlength="2" maxlength="120" />
                <p class="text-xs text-gray-400 mt-1">Optionnel (si vide, le site affichera le FR en fallback).</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="page_content_fr" class="block text-sm mb-2">Contenu (FR)</label>
                <textarea id="page_content_fr" name="content_fr" rows="14" maxlength="200000" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg">{{ old('content_fr', $page->contentForLocale('fr')) }}</textarea>
            </div>
            <div>
                <label for="page_content_en" class="block text-sm mb-2">Content (EN)</label>
                <textarea id="page_content_en" name="content_en" rows="14" maxlength="200000" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg">{{ old('content_en', $page->contentForLocale('en')) }}</textarea>
            </div>
            <p class="text-xs text-gray-400 md:col-span-2">Le frontend affiche automatiquement FR/EN selon la langue du site.</p>
        </div>

        <div class="flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-700 bg-gray-900" {{ old('is_active', $page->is_active ? '1' : '0') === '1' ? 'checked' : '' }}>
            <span class="text-sm text-gray-200">Page active</span>
        </div>

        <div class="pt-2 flex gap-3">
            <button class="px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">
                Enregistrer
            </button>
            <a href="{{ route('page.show', $page->slug) }}" target="_blank" rel="noopener" class="px-6 py-3 bg-white/10 hover:bg-white/20 rounded-lg font-semibold transition">
                Voir sur le site
            </a>
        </div>
    </form>
</div>
@endsection

