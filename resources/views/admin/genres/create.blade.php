@extends('admin.layouts.app')

@section('title', 'Admin - Ajouter genre')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold">Ajouter un genre</h1>
        <a href="{{ route('admin.genres.index') }}" class="text-gray-300 hover:text-white">← Retour</a>
    </div>

    <form method="POST" action="{{ route('admin.genres.store') }}" class="bg-gray-800 rounded-lg p-6 space-y-4" data-ts-validate="form" novalidate>
        @csrf

        <div>
            <label for="genre_name_fr" class="block text-sm mb-2">Nom (FR)</label>
            <input id="genre_name_fr" name="name_fr" value="{{ old('name_fr') }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" required minlength="2" maxlength="80" />
            <p class="text-xs text-gray-400 mt-1">Ex: Romance, Action, Animation, Mafia…</p>
        </div>

        <div>
            <label for="genre_name_en" class="block text-sm mb-2">Nom (EN)</label>
            <input id="genre_name_en" name="name_en" value="{{ old('name_en') }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" required minlength="2" maxlength="80" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="genre_slug_fr" class="block text-sm mb-2">Slug (FR)</label>
                <input id="genre_slug_fr" value="" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg opacity-80" readonly />
                <p class="text-xs text-gray-400 mt-1">Généré automatiquement à partir du nom FR.</p>
            </div>
            <div>
                <label for="genre_slug_en" class="block text-sm mb-2">Slug (EN)</label>
                <input id="genre_slug_en" value="" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg opacity-80" readonly />
                <p class="text-xs text-gray-400 mt-1">Généré automatiquement à partir du nom EN.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="genre_sort_order" class="block text-sm mb-2">Ordre</label>
                <input id="genre_sort_order" name="sort_order" type="number" value="{{ old('sort_order', 0) }}" min="0" max="9999" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" />
            </div>
            <label class="flex items-center gap-2 mt-7">
                <input type="hidden" name="is_active" value="0" />
                <input type="checkbox" name="is_active" value="1" class="rounded" {{ old('is_active') ? 'checked' : '' }} />
                <span>Actif</span>
            </label>
        </div>

        <div class="pt-2">
            <button class="px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">Créer</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(() => {
  const nameFr = document.getElementById('genre_name_fr');
  const nameEn = document.getElementById('genre_name_en');
  const slugFr = document.getElementById('genre_slug_fr');
  const slugEn = document.getElementById('genre_slug_en');

  const slugify = (s) => {
    return String(s || '')
      .normalize('NFKD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/(^-|-$)/g, '');
  };

  const sync = () => {
    if (slugFr) slugFr.value = slugify(nameFr?.value || '');
    if (slugEn) slugEn.value = slugify(nameEn?.value || '');
  };

  nameFr?.addEventListener('input', sync);
  nameEn?.addEventListener('input', sync);
  sync();
})();
</script>
@endpush
