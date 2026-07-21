@extends('admin.layouts.app')

@section('title', 'Admin - Modifier série')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold">Modifier la série</h1>
        <a href="{{ route('admin.series') }}" class="text-gray-300 hover:text-white">← Retour</a>
    </div>

    <form method="POST" action="{{ route('admin.series.update', $series->id) }}" enctype="multipart/form-data" class="bg-gray-800 rounded-lg p-6 space-y-4" data-ts-validate="form" novalidate>
        @csrf
        @method('PUT')

        <input type="hidden" name="poster_url" id="poster_url" value="{{ old('poster_url', $series->poster ?? '') }}">
        <input type="hidden" name="cover_image_url" id="cover_image_url" value="{{ old('cover_image_url', $series->cover_image ?? '') }}">

        @php
            $titleFr = old('title_fr', $series->title_fr ?: $series->title);
            $titleEn = old('title_en', $series->title_en ?: $series->title);
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="series_title_fr" class="block text-sm mb-2">Titre (FR)</label>
                <input id="series_title_fr" name="title_fr" value="{{ $titleFr }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" required minlength="2" maxlength="255" />
            </div>
            <div>
                <label for="series_title_en" class="block text-sm mb-2">Title (EN)</label>
                <input id="series_title_en" name="title_en" value="{{ $titleEn }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" required minlength="2" maxlength="255" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="series_slug_fr" class="block text-sm mb-2">Slug (FR) — auto</label>
                <input id="series_slug_fr" type="text" value="{{ old('title_fr') ? '' : ($series->slug_fr ?? $series->slug ?? '') }}" readonly class="w-full px-4 py-3 bg-gray-900/60 border border-gray-700 rounded-lg text-gray-200" />
                <p id="series_slug_exists" class="text-xs text-red-300 mt-1 hidden">
                    Ce slug FR existe déjà. Modifie légèrement le titre FR.
                </p>
            </div>
            <div>
                <label for="series_slug_en" class="block text-sm mb-2">Slug (EN) — auto</label>
                <input id="series_slug_en" type="text" value="{{ old('title_en') ? '' : ($series->slug_en ?? $series->slug ?? '') }}" readonly class="w-full px-4 py-3 bg-gray-900/60 border border-gray-700 rounded-lg text-gray-200" />
                <p class="text-xs text-gray-400 mt-2">
                    Le slug EN est généré depuis le titre EN. En cas de collision, le serveur peut ajouter un suffixe.
                </p>
            </div>
        </div>

        @php
            $descFr = old('description_fr', $series->description_fr ?: $series->description);
            $descEn = old('description_en', $series->description_en ?: $series->description);
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="series_description_fr" class="block text-sm mb-2">Description (FR)</label>
                <textarea id="series_description_fr" name="description_fr" rows="6" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" required minlength="10">{{ $descFr }}</textarea>
            </div>
            <div>
                <label for="series_description_en" class="block text-sm mb-2">Description (EN)</label>
                <textarea id="series_description_en" name="description_en" rows="6" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" required minlength="10">{{ $descEn }}</textarea>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="series_release_year" class="block text-sm mb-2">Année</label>
                <input id="series_release_year" name="release_year" value="{{ old('release_year', $series->release_year) }}" type="number" min="1900" max="{{ date('Y') }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" />
            </div>
        </div>

        <div class="bg-gray-900/30 border border-gray-700/60 rounded-lg p-4">
            <div class="flex items-center justify-between gap-3 flex-wrap mb-3">
                <div>
                    <div class="font-semibold">Langues vidéo disponibles</div>
                    <div class="text-xs text-gray-400">Ces langues seront proposées au lecteur sur les épisodes.</div>
                </div>
                <a href="{{ route('admin.video-languages.index') }}" class="text-xs text-gray-300 hover:text-white underline">
                    Gérer les langues vidéo
                </a>
            </div>

            @php
                $selectedLangs = old('video_languages', $series->video_languages ?? ['fr']);
                $selectedLangs = is_array($selectedLangs) ? $selectedLangs : ['fr'];
                $selectedLangs = array_values(array_filter(array_map(fn($v) => strtolower(trim((string) $v)), $selectedLangs)));
            @endphp

            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                @forelse(($videoLanguages ?? []) as $vl)
                    @php
                        $code = (string) $vl->code;
                        $isChecked = in_array($code, $selectedLangs, true);
                    @endphp
                    <label class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white/5 hover:bg-white/10 transition">
                        <input type="checkbox" name="video_languages[]" value="{{ $code }}" class="rounded" {{ $isChecked ? 'checked' : '' }} />
                        <span class="text-sm">{{ $vl->name }} <span class="text-gray-400">({{ $code }})</span></span>
                    </label>
                @empty
                    <div class="text-sm text-gray-400">
                        Aucune langue vidéo. Ajoute-en via “Gérer les langues vidéo”.
                    </div>
                @endforelse
            </div>
            <p class="text-xs text-gray-400 mt-2">
                Sélectionne les langues de lecture disponibles pour les épisodes (indépendant de la langue du site).
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm mb-2">Poster (image)</label>
                <input type="file" name="poster" id="poster_file" class="w-full text-sm" />
                @php
                    $posterUrl = (string) ($series->poster ?? '');
                    $placeholderImg = asset('images/placeholders/placeholder.svg');
                @endphp
                <div id="poster_preview_wrap" class="mt-3 flex items-center gap-3">
                    <div class="relative h-20 w-14 rounded-lg overflow-hidden border border-white/10 bg-black/20">
                        <div class="absolute inset-0 skeleton"></div>
                        <img
                            id="poster_preview_img"
                            src="{{ $posterUrl }}"
                            data-placeholder="{{ $placeholderImg }}"
                            alt="Poster"
                            class="absolute inset-0 w-full h-full object-cover opacity-0 transition-opacity duration-200"
                            onload="this.classList.remove('opacity-0'); this.parentElement?.querySelector('.skeleton')?.classList.add('hidden')"
                            onerror="this.onerror=null; if(this.dataset.placeholder && this.src!==this.dataset.placeholder){this.src=this.dataset.placeholder;this.classList.remove('opacity-0')}else{this.style.display='none'; this.parentElement?.querySelector('.skeleton')?.classList.add('hidden')}"
                        >
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs text-gray-400">Actuel</div>
                        <a id="poster_preview_link" href="{{ $posterUrl }}" target="_blank" rel="noopener" class="text-xs text-gray-200 hover:text-white underline break-all">
                            {{ $posterUrl ?: '—' }}
                        </a>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="w-full h-2 bg-black/30 rounded overflow-hidden">
                        <div id="poster_progress" class="h-2 bg-red-600 w-0"></div>
                    </div>
                    <p id="poster_status" class="text-xs text-gray-400 mt-1"></p>
                </div>
            </div>
            <div>
                <label class="block text-sm mb-2">Cover (image)</label>
                <input type="file" name="cover_image" id="cover_file" class="w-full text-sm" />
                @php
                    $coverUrl = (string) ($series->cover_image ?? '');
                @endphp
                <div id="cover_preview_wrap" class="mt-3 flex items-center gap-3">
                    <div class="relative h-20 w-36 rounded-lg overflow-hidden border border-white/10 bg-black/20">
                        <div class="absolute inset-0 skeleton"></div>
                        <img
                            id="cover_preview_img"
                            src="{{ $coverUrl }}"
                            data-placeholder="{{ $placeholderImg }}"
                            alt="Cover"
                            class="absolute inset-0 w-full h-full object-cover opacity-0 transition-opacity duration-200"
                            onload="this.classList.remove('opacity-0'); this.parentElement?.querySelector('.skeleton')?.classList.add('hidden')"
                            onerror="this.onerror=null; if(this.dataset.placeholder && this.src!==this.dataset.placeholder){this.src=this.dataset.placeholder;this.classList.remove('opacity-0')}else{this.style.display='none'; this.parentElement?.querySelector('.skeleton')?.classList.add('hidden')}"
                        >
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs text-gray-400">Actuel</div>
                        <a id="cover_preview_link" href="{{ $coverUrl }}" target="_blank" rel="noopener" class="text-xs text-gray-200 hover:text-white underline break-all">
                            {{ $coverUrl ?: '—' }}
                        </a>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="w-full h-2 bg-black/30 rounded overflow-hidden">
                        <div id="cover_progress" class="h-2 bg-red-600 w-0"></div>
                    </div>
                    <p id="cover_status" class="text-xs text-gray-400 mt-1"></p>
                </div>
            </div>
        </div>

        <div class="bg-gray-900/40 border border-red-500/30 rounded-lg p-4">
            <div class="font-semibold mb-1 text-red-200">Position sur l’accueil</div>
            <p class="text-xs text-gray-400 mb-3">Cochez <strong>En vedette</strong> pour le carrousel. Plus l’ordre est petit, plus la série est en haut (0 = avant 10). Ou utilisez « 1ʳᵉ position » dans la liste.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-2">Ordre d’affichage (carrousel)</label>
                    <input name="sort_order" type="number" min="-9999" max="99999" value="{{ old('sort_order', $series->sort_order ?? 0) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" />
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <label class="flex items-center gap-2">
                <input type="hidden" name="is_exclusive" value="0" />
                <input type="checkbox" name="is_exclusive" value="1" class="rounded" {{ $series->is_exclusive ? 'checked' : '' }} />
                <span>Exclusif</span>
            </label>
            <label class="flex items-center gap-2">
                <input type="hidden" name="is_featured" value="0" />
                <input type="checkbox" name="is_featured" value="1" class="rounded" {{ $series->is_featured ? 'checked' : '' }} />
                <span>En vedette</span>
            </label>
            <label class="flex items-center gap-2">
                <input type="hidden" name="is_trending" value="0" />
                <input type="checkbox" name="is_trending" value="1" class="rounded" {{ $series->is_trending ? 'checked' : '' }} />
                <span>Tendance</span>
            </label>
            <label class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0" />
                <input type="checkbox" name="is_active" value="1" class="rounded" {{ old('is_active', $series->is_active) ? 'checked' : '' }} />
                <span>Actif</span>
            </label>
        </div>

        <div class="bg-gray-900/30 border border-gray-700/60 rounded-lg p-4">
            <div class="font-semibold mb-1">Publication</div>
            <div class="text-xs text-gray-400 mb-3">
                <strong>Actif</strong> = brouillon/visible. <strong>Publication</strong> = disponible tout de suite ou à une date future.
            </div>

            @php
                $pubMode = old('published_mode', $series->published_at ? 'scheduled' : 'immediate');
                $publishedAtValue = old('published_at');
                if ($publishedAtValue === null && $series->published_at) {
                    $publishedAtValue = $series->published_at->format('Y-m-d\\TH:i');
                }
            @endphp

            <div class="flex flex-wrap gap-4">
                <label class="flex items-center gap-2">
                    <input type="radio" name="published_mode" value="immediate" class="rounded" {{ $pubMode === 'immediate' ? 'checked' : '' }}>
                    <span>Immédiat</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" name="published_mode" value="scheduled" class="rounded" {{ $pubMode === 'scheduled' ? 'checked' : '' }}>
                    <span>Programmé</span>
                </label>
            </div>

            <div id="published_at_wrap" class="mt-4 {{ $pubMode === 'scheduled' ? '' : 'hidden' }}">
                <label class="block text-sm mb-2">Date/heure de disponibilité</label>
                <input
                    type="datetime-local"
                    name="published_at"
                    value="{{ $publishedAtValue }}"
                    class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg"
                />
                <p class="text-xs text-gray-400 mt-2">
                    Tant que la date n’est pas atteinte, le frontend affiche “Disponible le …” + bouton “Notifier moi”.
                </p>
            </div>
        </div>

        <div class="bg-gray-900/30 border border-gray-700/60 rounded-lg p-4">
            <div class="flex items-center justify-between gap-3 flex-wrap mb-3">
                <div>
                    <div class="font-semibold">Classements (Genres)</div>
                    <div class="text-xs text-gray-400">Sélectionne les genres visibles dans “Classification”.</div>
                </div>
                <a href="{{ route('admin.genres.index') }}" class="text-xs text-gray-300 hover:text-white underline">
                    Gérer les genres
                </a>
            </div>

            @php
                $selected = old('genres', $series->genres ?? []);
                $selected = is_array($selected) ? $selected : [];
                // Tolérance: certains slugs peuvent être non normalisés (majuscules/accents/spaces).
                // On compare donc (1) valeur brute, et (2) valeur slugifiée.
                $selectedRaw = array_values(array_map(fn ($v) => (string) $v, $selected));
                $selectedNormalized = collect($selectedRaw)->map(fn($v) => \Illuminate\Support\Str::slug($v))->values()->all();
            @endphp

            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                @forelse($genres ?? [] as $g)
                    @php
                        $gSlug = (string) $g->slug;
                        $gNorm = \Illuminate\Support\Str::slug($gSlug);
                        $isChecked = in_array($gSlug, $selectedRaw, true) || in_array($gNorm, $selectedNormalized, true);
                    @endphp
                    <label class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white/5 hover:bg-white/10 transition">
                        <input type="checkbox" name="genres[]" value="{{ $g->slug }}" class="rounded" {{ $isChecked ? 'checked' : '' }} />
                        <span class="text-sm">{{ $g->name }}</span>
                    </label>
                @empty
                    <div class="text-sm text-gray-400">
                        Aucun genre. Ajoute-en via “Gérer les genres”.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="pt-2 flex gap-3">
            <button id="series_submit" class="px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">Enregistrer</button>
            <a class="px-6 py-3 bg-gray-700 hover:bg-gray-600 rounded-lg font-semibold transition" href="{{ route('admin.episodes', $series->id) }}">
                Gérer les épisodes
            </a>
        </div>

        <p id="upload_guard" class="text-xs text-amber-300 mt-2 hidden">
            Veuillez patienter la fin de l'envoi de l'image. N'actualisez pas et ne quittez pas la page.
        </p>
    </form>
</div>

@push('scripts')
<script>
(() => {
  const form = document.querySelector('form[action="{{ route('admin.series.update', $series->id) }}"]');
  const btn = document.getElementById('series_submit');

  // Gestion des raisons de désactivation (slug doublon vs upload en cours)
  let disabledBySlug = false;
  let disabledByUpload = false;
  function applyDisabledState() {
    if (!btn) return;
    const shouldDisable = disabledBySlug || disabledByUpload;
    btn.disabled = shouldDisable;
    btn.classList.toggle('opacity-60', shouldDisable);
    btn.classList.toggle('cursor-not-allowed', shouldDisable);
  }

  // Slugs auto + check d’unicité (best-effort UI)
  const titleFrEl = document.getElementById('series_title_fr');
  const titleEnEl = document.getElementById('series_title_en');
  const slugFrEl = document.getElementById('series_slug_fr');
  const slugEnEl = document.getElementById('series_slug_en');
  const slugExistsEl = document.getElementById('series_slug_exists');
  const checkUrl = @json(route('admin.series.check-slug'));
  const ignoreId = @json((int) $series->id);
  const initialTitle = @json((string) ($series->title_fr ?: $series->title));
  let lastSlug = '';
  let slugExists = false;
  let checkTimer = null;

  function slugify(str) {
    try {
      return (str || '')
        .toString()
        .trim()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .replace(/-{2,}/g, '-');
    } catch (_) {
      return (str || '').toString().trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    }
  }

  async function checkSlug(slug) {
    if (!slug) return { exists: false };
    const res = await fetch(`${checkUrl}?slug=${encodeURIComponent(slug)}&ignore_id=${encodeURIComponent(String(ignoreId))}`, { headers: { 'Accept': 'application/json' } });
    const data = await res.json().catch(() => ({}));
    return { exists: !!data?.exists };
  }

  function renderSlugState() {
    if (!slugExistsEl) return;
    slugExistsEl.classList.toggle('hidden', !slugExists);
    if (slugFrEl) {
      slugFrEl.classList.toggle('border-red-600', slugExists);
      slugFrEl.classList.toggle('border-gray-700', !slugExists);
    }
    // En édition: on bloque seulement si le titre a changé ET slug en doublon.
    const titleChanged = (titleFrEl?.value || '') !== initialTitle;
    disabledBySlug = titleChanged && slugExists;
    applyDisabledState();
  }

  async function syncSlug() {
    const slugFr = slugify(titleFrEl?.value || '');
    const slugEn = slugify(titleEnEl?.value || '');
    if (slugFrEl) slugFrEl.value = slugFr || '';
    if (slugEnEl) slugEnEl.value = slugEn || '';

    // Tant que le titre n'a pas été modifié, aucun contrôle (le slug actuel est celui de la série chargée).
    const titleChanged = (titleFrEl?.value || '') !== initialTitle;
    if (!titleChanged) {
      slugExists = false;
      renderSlugState();
      return;
    }

    if (!slugFr) {
      slugExists = false;
      renderSlugState();
      return;
    }
    if (slugFr === lastSlug) return;
    lastSlug = slugFr;
    window.clearTimeout(checkTimer);
    checkTimer = window.setTimeout(async () => {
      try {
        const r = await checkSlug(slugFr);
        slugExists = !!r.exists;
        renderSlugState();
      } catch (_) {
        // no-op
      }
    }, 250);
  }
  titleFrEl?.addEventListener('input', syncSlug);
  titleEnEl?.addEventListener('input', syncSlug);
  syncSlug();

  // Si la validation HTML5 empêche l'envoi, ne jamais laisser le bouton bloqué.
  form?.addEventListener('invalid', () => {
    if (!btn) return;
    // On ne réactive pas si c'est un doublon slug (et titre modifié).
    if (!disabledBySlug) {
      btn.disabled = false;
      btn.classList.remove('opacity-60', 'cursor-not-allowed');
    }
  }, true);

  // Anti double-submit: ne désactive le bouton QUE si la soumission part vraiment.
  form?.addEventListener('submit', (e) => {
    window.setTimeout(() => {
      if (!btn) return;
      if (e.defaultPrevented) return;
      if (!form.checkValidity()) return;
      const titleChanged = (titleFrEl?.value || '') !== initialTitle;
      if (titleChanged && slugExists) {
        e.preventDefault();
        window.talashowToast?.({ type: 'error', title: 'Slug déjà utilisé', messages: ['Modifie légèrement le titre.'] });
        return;
      }
      btn.disabled = true;
      btn.classList.add('opacity-60', 'cursor-not-allowed');
    }, 0);
  });

  // Toggle publication scheduled field
  const wrap = document.getElementById('published_at_wrap');
  const radios = document.querySelectorAll('input[name="published_mode"]');
  function sync() {
    const mode = (document.querySelector('input[name="published_mode"]:checked')?.value) || 'immediate';
    if (!wrap) return;
    wrap.classList.toggle('hidden', mode !== 'scheduled');
    // Rendre obligatoire seulement si "programmé"
    const dt = wrap.querySelector('input[name="published_at"]');
    if (dt) dt.required = (mode === 'scheduled');
  }
  radios.forEach(r => r.addEventListener('change', sync));
  sync();

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  const submitBtn = document.getElementById('series_submit');
  const guard = document.getElementById('upload_guard');
  let pending = 0;

  function setUploading(isUploading) {
    if (!submitBtn) return;
    disabledByUpload = isUploading;
    applyDisabledState();
    if (guard) guard.classList.toggle('hidden', !isUploading);
  }

  function upload(file, type, progressEl, statusEl, hiddenEl) {
    if (!file) return;
    pending++;
    setUploading(true);
    statusEl.textContent = 'Envoi en cours...';
    progressEl.style.width = '0%';

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '{{ route('admin.media.upload-image') }}', true);
    xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
    xhr.responseType = 'json';
    xhr.upload.onprogress = (e) => {
      if (!e.lengthComputable) return;
      const pct = Math.round((e.loaded / e.total) * 100);
      progressEl.style.width = pct + '%';
      statusEl.textContent = `Envoi: ${pct}%`;
    };
    xhr.onload = () => {
      pending = Math.max(0, pending - 1);
      const data = xhr.response;
      if (xhr.status >= 200 && xhr.status < 300 && data?.success) {
        hiddenEl.value = data.url;
        progressEl.style.width = '100%';
        statusEl.textContent = 'Upload terminé ✅ (aperçu mis à jour)';

        // Update previews so the admin can SEE the result immediately
        if (type === 'poster') {
          const img = document.getElementById('poster_preview_img');
          const link = document.getElementById('poster_preview_link');
          if (img) {
            const skel = img.parentElement?.querySelector('.skeleton');
            skel?.classList.remove('hidden');
            img.style.display = '';
            img.classList.add('opacity-0');
            img.src = data.url;
            window.talashowRevealImageAfterSrcChange?.(img);
          }
          if (link) { link.href = data.url; link.textContent = data.url; }
        }
        if (type === 'cover') {
          const img = document.getElementById('cover_preview_img');
          const link = document.getElementById('cover_preview_link');
          if (img) {
            const skel = img.parentElement?.querySelector('.skeleton');
            skel?.classList.remove('hidden');
            img.style.display = '';
            img.classList.add('opacity-0');
            img.src = data.url;
            window.talashowRevealImageAfterSrcChange?.(img);
          }
          if (link) { link.href = data.url; link.textContent = data.url; }
        }
      } else {
        statusEl.textContent = (data?.message || 'Upload échoué') + ' ❌';
        progressEl.style.width = '0%';
      }
      setUploading(pending > 0);
    };
    xhr.onerror = () => {
      pending = Math.max(0, pending - 1);
      statusEl.textContent = 'Erreur réseau ❌';
      progressEl.style.width = '0%';
      setUploading(pending > 0);
    };

    const fd = new FormData();
    fd.append('type', type);
    fd.append('file', file);
    xhr.send(fd);
  }

  const posterFile = document.getElementById('poster_file');
  const coverFile = document.getElementById('cover_file');
  const posterProgress = document.getElementById('poster_progress');
  const coverProgress = document.getElementById('cover_progress');
  const posterStatus = document.getElementById('poster_status');
  const coverStatus = document.getElementById('cover_status');
  const posterUrl = document.getElementById('poster_url');
  const coverUrl = document.getElementById('cover_image_url');

  // Local preview immediately on file pick (before upload ends)
  posterFile?.addEventListener('change', () => {
    const f = posterFile.files?.[0];
    if (f) {
      const img = document.getElementById('poster_preview_img');
      if (img) {
        const skel = img.parentElement?.querySelector('.skeleton');
        skel?.classList.remove('hidden');
        img.classList.add('opacity-0');
        img.style.display='';
        img.src = URL.createObjectURL(f);
      }
    }
  });
  coverFile?.addEventListener('change', () => {
    const f = coverFile.files?.[0];
    if (f) {
      const img = document.getElementById('cover_preview_img');
      if (img) {
        const skel = img.parentElement?.querySelector('.skeleton');
        skel?.classList.remove('hidden');
        img.classList.add('opacity-0');
        img.style.display='';
        img.src = URL.createObjectURL(f);
      }
    }
  });

  posterFile?.addEventListener('change', () => upload(posterFile.files?.[0], 'poster', posterProgress, posterStatus, posterUrl));
  coverFile?.addEventListener('change', () => upload(coverFile.files?.[0], 'cover', coverProgress, coverStatus, coverUrl));
})();
</script>
@endpush
@endsection

