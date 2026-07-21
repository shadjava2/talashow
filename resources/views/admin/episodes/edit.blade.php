@extends('admin.layouts.app')

@section('title', 'Admin - Modifier épisode')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold">Modifier l'épisode</h1>
            <p class="text-gray-400">{{ $series->title }} • Épisode {{ $episode->episode_number }}</p>
        </div>
        <a href="{{ route('admin.episodes', $series->id) }}" class="text-gray-300 hover:text-white">← Retour</a>
    </div>

    <form method="POST" action="{{ route('admin.episodes.update', [$series->id, $episode->id]) }}" enctype="multipart/form-data" class="bg-gray-800 rounded-lg p-6 space-y-4" id="episode_form">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm mb-2">Numéro</label>
                <input name="episode_number" type="number" value="{{ old('episode_number', $episode->episode_number) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" required />
                <p class="text-xs text-gray-400 mt-1">Astuce: mets <strong>0</strong> pour “Bande annonce”.</p>
            </div>
            <div>
                <label class="block text-sm mb-2">Durée (secondes)</label>
                <input name="duration" type="number" value="{{ old('duration', $episode->duration) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm mb-2">Libellé affiché (optionnel)</label>
                <input name="display_label" value="{{ old('display_label', $episode->display_label) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" placeholder="Bande annonce" />
                <p class="text-xs text-gray-400 mt-1">Si rempli, le site affichera ce texte au lieu de “EP X”.</p>
            </div>
            <div>
                <label class="block text-sm mb-2">Ordre d’affichage</label>
                <input name="sort_order" type="number" value="{{ old('sort_order', $episode->sort_order) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" />
                <p class="text-xs text-gray-400 mt-1">Plus petit = plus haut. Ou utilisez « 1ʳᵉ position » dans la liste des épisodes.</p>
            </div>
        </div>

        @php
            $titleFr = old('title_fr', $episode->title_fr ?: $episode->title);
            $titleEn = old('title_en', $episode->title_en ?: $episode->title);
            $descFr = old('description_fr', $episode->description_fr ?: $episode->description);
            $descEn = old('description_en', $episode->description_en ?: $episode->description);
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm mb-2">Titre (FR)</label>
                <input name="title_fr" value="{{ $titleFr }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" required minlength="2" maxlength="255" />
            </div>
            <div>
                <label class="block text-sm mb-2">Title (EN)</label>
                <input name="title_en" value="{{ $titleEn }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" required minlength="2" maxlength="255" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm mb-2">Description (FR)</label>
                <textarea name="description_fr" rows="4" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" required minlength="10">{{ $descFr }}</textarea>
            </div>
            <div>
                <label class="block text-sm mb-2">Description (EN)</label>
                <textarea name="description_en" rows="4" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" required minlength="10">{{ $descEn }}</textarea>
            </div>
        </div>

        {{-- Compat legacy (champ unique) --}}
        <input type="hidden" name="title" value="{{ $titleFr }}">
        <input type="hidden" name="description" value="{{ $descFr }}">

        <div>
            <label class="block text-sm mb-2">Miniature (image)</label>
            <input type="hidden" name="thumbnail_url" id="thumbnail_url" value="{{ old('thumbnail_url', $episode->thumbnail ?? '') }}" />
            <input type="file" name="thumbnail" id="thumb_file" class="w-full text-sm" />

            @php
                $thumbStored = $episode->thumbnail;
                $thumbDisplay = $thumbStored;
            @endphp

            @if($thumbDisplay && \Illuminate\Support\Str::startsWith($thumbDisplay, ['http://', 'https://', '/']))
                <div class="mt-2 flex items-center gap-3">
                    <img
                        id="thumb_preview"
                        src="{{ $thumbDisplay }}"
                        alt="Miniature"
                        class="h-16 w-28 object-cover rounded border border-white/10"
                        onerror="this.onerror=null; this.src='{{ asset('/images/placeholders/placeholder.svg') }}';"
                    >
                    <p class="text-xs text-gray-400 break-all">Actuelle: {{ $thumbStored }}</p>
                </div>
            @else
                <img id="thumb_preview" src="" alt="" class="hidden h-16 w-28 object-cover rounded border border-white/10 mt-2">
            @endif

            <div class="mt-2">
                <div class="w-full h-2 bg-black/30 rounded overflow-hidden">
                    <div id="thumb_progress" class="h-2 bg-red-600 w-0"></div>
                </div>
                <p id="thumb_status" class="text-xs text-gray-300 mt-2"></p>
                <p id="thumb_guard" class="text-xs text-amber-300 mt-1 hidden">
                    Veuillez patienter la fin de l'envoi de l'image. N'actualisez pas et ne quittez pas la page.
                </p>
            </div>
        </div>

        <div class="bg-gray-900/30 border border-gray-700/60 rounded-lg p-4">
            <div class="font-semibold mb-1">Vidéos par langue</div>
            <div class="text-xs text-gray-400 mb-3">
                L’utilisateur pourra choisir la langue dans le lecteur. Si l’URL est vide pour une langue, la vidéo sera “indisponible” pour cette langue.
            </div>

            {{-- Talashow: Bunny Stream --}}
            <input type="hidden" name="video_type" id="video_type" value="bunny" />

            @php
                $seriesLangs = $series->video_languages ?? [];
                $seriesLangs = is_array($seriesLangs) ? $seriesLangs : [];
                $seriesLangs = array_values(array_unique(array_map(fn($v) => strtolower(trim((string) $v)), $seriesLangs)));
                $def = $seriesLangs[0] ?? 'fr';

                $urls = old('video_urls', $episode->video_urls ?? []);
                $urls = is_array($urls) ? $urls : [];
                // Compat: si pas de map mais video_url existe, on le met sur la langue par défaut
                if (empty($urls) && !empty($episode->video_url)) {
                    $urls[$def] = $episode->video_url;
                }
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($seriesLangs as $code)
                    @php
                        $vl = $videoLanguages[$code] ?? null;
                        $label = $vl ? ($vl->name . ' (' . $code . ')') : strtoupper($code);
                        $val = (string) ($urls[$code] ?? '');
                    @endphp
                    <div>
                        <label class="block text-sm mb-2">URL vidéo — {{ $label }}</label>
                        <input
                            name="video_urls[{{ $code }}]"
                            value="{{ $val }}"
                            class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg"
                            placeholder="Colle ici l'URL du manifeste HLS (.m3u8)"
                        />
                        @if($code === $def)
                            <p class="text-xs text-gray-400 mt-1">Langue par défaut de la série.</p>
                        @endif
                    </div>
                @endforeach
            </div>

            <input type="hidden" name="video_url" value="{{ old('video_url', $episode->video_url) }}">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <label class="flex items-center gap-2">
                <input type="hidden" name="is_free" value="0" />
                <input type="checkbox" name="is_free" value="1" class="rounded" {{ old('is_free', $episode->is_free) ? 'checked' : '' }} />
                <span>Gratuit</span>
            </label>
            <label class="flex items-center gap-2">
                <input type="hidden" name="is_premium_only" value="0" />
                <input type="checkbox" name="is_premium_only" value="1" class="rounded" {{ old('is_premium_only', $episode->is_premium_only) ? 'checked' : '' }} />
                <span>Abonnement requis</span>
            </label>
            <div>
                <label class="block text-sm mb-2">Pièces (si payant)</label>
                <input name="unlock_coins" type="number" value="{{ old('unlock_coins', $episode->unlock_coins) }}" class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg" />
            </div>
        </div>

        <div class="bg-gray-900/30 border border-gray-700/60 rounded-lg p-4">
            <div class="font-semibold mb-1">Disponibilité (publication)</div>
            <div class="text-xs text-gray-400 mb-3">
                Vidéo accessible immédiatement ou à une date ultérieure (avec “Notifier moi”).
            </div>

            @php
                $pubMode = old('published_mode', $episode->published_at ? 'scheduled' : 'immediate');
                $publishedAtValue = old('published_at');
                if ($publishedAtValue === null && $episode->published_at) {
                    $publishedAtValue = $episode->published_at->format('Y-m-d\\TH:i');
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
            </div>
        </div>

        <div class="pt-2 flex flex-wrap gap-3">
            <button id="episode_submit" class="px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">Enregistrer</button>
            <a class="px-6 py-3 bg-gray-700 hover:bg-gray-600 rounded-lg font-semibold transition" href="{{ route('episode.show', [$series->slug, $episode->id]) }}">
                Voir côté site
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
(() => {
  // Toggle publication scheduled field
  const wrap = document.getElementById('published_at_wrap');
  const radios = document.querySelectorAll('input[name="published_mode"]');
  function syncPub() {
    const mode = (document.querySelector('input[name="published_mode"]:checked')?.value) || 'immediate';
    if (!wrap) return;
    wrap.classList.toggle('hidden', mode !== 'scheduled');
  }
  radios.forEach(r => r.addEventListener('change', syncPub));
  syncPub();

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  const submitBtn = document.getElementById('episode_submit');

  const thumbFile = document.getElementById('thumb_file');
  const thumbProgress = document.getElementById('thumb_progress');
  const thumbStatus = document.getElementById('thumb_status');
  const thumbGuard = document.getElementById('thumb_guard');
  const thumbUrl = document.getElementById('thumbnail_url');
  const thumbPreview = document.getElementById('thumb_preview');

  let pending = 0;
  function setUploading(v) {
    if (submitBtn) {
      submitBtn.disabled = v;
      submitBtn.classList.toggle('opacity-60', v);
      submitBtn.classList.toggle('cursor-not-allowed', v);
    }
    if (thumbGuard) thumbGuard.classList.toggle('hidden', !v);
  }

  function uploadThumb(file) {
    if (!file) return;
    pending++;
    setUploading(pending > 0);
    thumbProgress.style.width = '0%';
    thumbStatus.textContent = 'Envoi de la miniature...';

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '{{ route('admin.media.upload-image') }}', true);
    xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.responseType = 'json';
    xhr.upload.onprogress = (e) => {
      if (!e.lengthComputable) return;
      const pct = Math.round((e.loaded / e.total) * 100);
      thumbProgress.style.width = pct + '%';
      thumbStatus.textContent = `Envoi: ${pct}%`;
    };
    xhr.onload = () => {
      const data = xhr.response;
      if (xhr.status >= 200 && xhr.status < 300 && data?.success) {
        thumbProgress.style.width = '100%';
        thumbStatus.textContent = 'Miniature uploadée ✅';
        if (thumbUrl) thumbUrl.value = data.url;
        if (thumbPreview) {
          thumbPreview.src = data.url;
          thumbPreview.classList.remove('hidden');
          window.talashowRevealImageAfterSrcChange?.(thumbPreview);
        }
      } else {
        const fallback = (xhr.responseText || '').toString().slice(0, 180);
        thumbStatus.textContent = `Upload échoué (HTTP ${xhr.status}) ❌ ${data?.message ? ('— ' + data.message) : fallback}`;
        thumbProgress.style.width = '0%';
      }
      pending = Math.max(0, pending - 1);
      setUploading(pending > 0);
    };
    xhr.onerror = () => {
      thumbStatus.textContent = 'Erreur réseau ❌';
      thumbProgress.style.width = '0%';
      pending = Math.max(0, pending - 1);
      setUploading(pending > 0);
    };

    const fd = new FormData();
    fd.append('type', 'thumb');
    fd.append('file', file);
    xhr.send(fd);
  }

  thumbFile?.addEventListener('change', () => uploadThumb(thumbFile.files?.[0]));
})();
</script>
@endpush
@endsection

