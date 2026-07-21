@extends('admin.layouts.app')

@section('title', 'Admin - Modifier langue vidéo')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between gap-4 flex-wrap mb-6">
        <div>
            <h1 class="text-3xl font-bold">Modifier la langue vidéo</h1>
            <p class="text-gray-400 text-sm">ID: {{ $lang->id }}</p>
        </div>
        <a href="{{ route('admin.video-languages.index') }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-semibold transition">
            Retour
        </a>
    </div>

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700/60">
        <form method="POST" action="{{ route('admin.video-languages.update', $lang->id) }}" class="space-y-4" data-ts-validate="form" novalidate>
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-2">Code</label>
                    <input name="code" value="{{ old('code', $lang->code) }}" required maxlength="12"
                           class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" />
                </div>
                <div>
                    <label class="block text-sm mb-2">Ordre</label>
                    <input name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $lang->sort_order) }}"
                           class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-2">Nom</label>
                    <input name="name" value="{{ old('name', $lang->name) }}" required maxlength="80"
                           class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" />
                </div>
                <div>
                    <label class="block text-sm mb-2">Nom natif (optionnel)</label>
                    <input name="native_name" value="{{ old('native_name', $lang->native_name) }}" maxlength="80"
                           class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" />
                </div>
            </div>

            <label class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0" />
                <input type="checkbox" name="is_active" value="1" class="rounded" {{ old('is_active', $lang->is_active) ? 'checked' : '' }} />
                <span>Actif</span>
            </label>

            <div class="pt-2 flex items-center justify-end">
                <button class="px-5 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

