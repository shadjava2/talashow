@extends('layouts.app')

@section('title', $series->titleForLocale())

@section('content')
@php
    $settings = app(\App\Services\SettingsService::class);
    $epShort = $settings->get('episode_label_short', 'EP');
    $imgUrl = fn ($u) => \App\Support\MediaDisplay::url($u);
    $poster = $imgUrl($series->poster ?? '/images/placeholders/placeholder.svg');
    $cover = $imgUrl($series->cover_image ?? $series->poster ?? '/images/placeholders/placeholder.svg');
    $firstEpisode = $series->episodes->first();
    $episodeUserStates = $episodeUserStates ?? collect();
    $epCount = (int) $series->episodes->count();
@endphp

<div class="ts-series-page relative z-[2] pb-16">
    {{-- Hero compact --}}
    <section class="ts-series-hero">
        <div class="ts-series-hero__bg">
            <img src="{{ $cover }}" alt="" class="ts-series-hero__bg-img" loading="eager" decoding="async">
            <div class="ts-series-hero__bg-overlay"></div>
        </div>

        <div class="ts-series-hero__inner max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="ts-series-hero__crumb text-sm mb-4">
                <a href="{{ route('home') }}">{{ __('ui.nav.home') }}</a>
                <span class="mx-2 opacity-60">›</span>
                <span class="ts-series-hero__crumb-current line-clamp-1">{{ $series->titleForLocale() }}</span>
            </nav>

            <div class="ts-series-hero__grid">
                <div class="ts-series-hero__poster">
                    <img src="{{ $poster }}" alt="{{ $series->titleForLocale() }}" class="js-skeleton-img" loading="eager" decoding="async"
                         onerror="this.onerror=null; this.src='{{ asset('/images/placeholders/placeholder.svg') }}';">
                </div>

                <div class="ts-series-hero__info">
                    <h1 class="ts-series-hero__title">{{ $series->titleForLocale() }}</h1>
                    <div class="ts-series-hero__meta">
                        <span class="ts-series-hero__badge">{{ trans_choice('ui.home.episodes_count', $epCount, ['count' => $epCount]) }}</span>
                        @if($series->release_year)<span>{{ $series->release_year }}</span>@endif
                        @if($series->rating)<span>★ {{ number_format((float) $series->rating, 1) }}</span>@endif
                    </div>

                    @if($firstEpisode)
                        <div class="ts-series-hero__actions">
                            <a href="{{ route('episode.show', [$series->slug, $firstEpisode->id]) }}" class="ts-series-hero__play">
                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                                {{ __('ui.series.play_now') }}
                            </a>
                            <a href="#episodes" class="ts-series-hero__episodes-link">{{ __('ui.series.jump_episodes') }}</a>
                            <button id="share-series" type="button" class="ts-series-hero__share">{{ __('ui.common.share') }}</button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Épisodes — section principale, visible immédiatement --}}
    <section class="ts-series-episodes max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" id="episodes">
        <header class="ts-series-episodes__head">
            <div>
                <h2 class="ts-series-episodes__title">{{ __('ui.series.episodes_list') }}</h2>
                <p class="ts-series-episodes__sub">{{ __('ui.series.episodes_list_sub') }}</p>
            </div>
            <span class="ts-series-episodes__count">{{ $epCount }}</span>
        </header>

        @if($epCount === 0)
            <div class="ts-series-episodes__empty">
                <p class="text-lg font-bold">{{ __('ui.series.no_episodes_title') }}</p>
                <p class="text-sm ts-text-muted mt-2">{{ __('ui.series.no_episodes_subtitle') }}</p>
                <a href="{{ route('home') }}" class="inline-block mt-5 px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold">
                    {{ __('ui.series.back_home') }}
                </a>
            </div>
        @else
            {{-- Navigation rapide par numéro (DramaBox) --}}
            <div class="ts-episode-pills" data-episode-pills>
                <div class="ts-episode-pills__track">
                    @foreach($series->episodes as $idx => $episode)
                        <a href="#ep-{{ $episode->id }}"
                           class="ts-episode-pills__chip"
                           data-episode-pill="{{ $episode->id }}"
                           data-episode-index="{{ $idx }}">
                            {{ $episode->episode_number }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="ts-episode-list" data-episode-list>
                @foreach($series->episodes as $idx => $episode)
                    <x-series.episode-card
                        :series="$series"
                        :episode="$episode"
                        :img-url="$imgUrl"
                        :ep-short="$epShort"
                        :user-state="$episodeUserStates->get($episode->id)"
                        :index="$idx"
                    />
                @endforeach
            </div>
        @endif
    </section>

    {{-- À propos (sous les épisodes) --}}
    <section class="ts-series-about max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
        <h2 class="ts-series-about__title">{{ __('ui.series.about') }}</h2>
        @if($series->descriptionForLocale())
            <p class="ts-series-about__text">{{ $series->descriptionForLocale() }}</p>
        @endif
        @if($series->tags)
            <div class="flex flex-wrap gap-2 mt-4">
                @foreach($series->tags as $tag)
                    <span class="ts-series-about__tag">{{ $tag }}</span>
                @endforeach
            </div>
        @endif
    </section>
</div>

{{-- Share modal --}}
<div id="share-modal" class="fixed inset-0 z-[70] hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
    <div class="relative w-full h-full flex items-center justify-center p-4">
        <div class="max-w-lg w-full bg-gray-900 border border-gray-700/60 rounded-2xl shadow-2xl p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-2xl font-bold">{{ __('ui.common.share') }}</h3>
                    <p class="text-gray-300 mt-2">{{ __('ui.series.share_subtitle') }}</p>
                </div>
                <button id="share-close" type="button" class="px-3 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm">{{ __('ui.common.close') }}</button>
            </div>
            @php $shareUrl = url()->current(); $shareTitle = $series->titleForLocale(); @endphp
            <div class="mt-5">
                <label class="text-xs text-gray-400">{{ __('ui.common.link') }}</label>
                <div class="mt-2 flex gap-2">
                    <input id="share-link" readonly value="{{ $shareUrl }}" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-sm text-gray-200" />
                    <button id="share-copy" type="button" class="px-4 py-3 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-semibold whitespace-nowrap">{{ __('ui.common.copy') }}</button>
                </div>
                <div id="share-copied" class="hidden text-xs text-green-300 mt-2">{{ __('ui.common.link_copied') }}</div>
            </div>
            <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-2">
                <a id="share-wa" target="_blank" rel="noopener" class="px-3 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm text-center">WhatsApp</a>
                <a id="share-fb" target="_blank" rel="noopener" class="px-3 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm text-center">Facebook</a>
                <a id="share-x" target="_blank" rel="noopener" class="px-3 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm text-center">X</a>
                <a id="share-tg" target="_blank" rel="noopener" class="px-3 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm text-center">Telegram</a>
            </div>
            <div class="mt-3">
                <a id="share-mail" class="inline-block text-sm text-gray-300 hover:text-white underline underline-offset-4">{{ __('ui.series.share_by_email') }}</a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(() => {
  const btn = document.getElementById('share-series');
  const modal = document.getElementById('share-modal');
  const closeBtn = document.getElementById('share-close');
  const linkInput = document.getElementById('share-link');
  const copyBtn = document.getElementById('share-copy');
  const copied = document.getElementById('share-copied');
  const wa = document.getElementById('share-wa');
  const fb = document.getElementById('share-fb');
  const x = document.getElementById('share-x');
  const tg = document.getElementById('share-tg');
  const mail = document.getElementById('share-mail');

  if (btn && modal && linkInput) {
    const url = linkInput.value;
    const title = @json($shareTitle);
    const text = @json(__('ui.series.share_text', ['title' => $shareTitle]));
    const encUrl = encodeURIComponent(url);
    const encText = encodeURIComponent(text);
    wa.href = `https://wa.me/?text=${encText}%20${encUrl}`;
    fb.href = `https://www.facebook.com/sharer/sharer.php?u=${encUrl}`;
    x.href = `https://twitter.com/intent/tweet?text=${encText}&url=${encUrl}`;
    tg.href = `https://t.me/share/url?url=${encUrl}&text=${encText}`;
    mail.href = `mailto:?subject=${encodeURIComponent(@json(__('ui.series.share_email_subject', ['title' => $shareTitle])))}&body=${encText}%0A${encUrl}`;
    const openModal = () => { modal.classList.remove('hidden'); modal.setAttribute('aria-hidden', 'false'); };
    const closeModal = () => { modal.classList.add('hidden'); modal.setAttribute('aria-hidden', 'true'); copied?.classList.add('hidden'); };
    btn.addEventListener('click', async () => {
      if (navigator.share) { try { await navigator.share({ title, text, url }); return; } catch (e) {} }
      openModal();
    });
    closeBtn?.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal.firstElementChild) closeModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal(); });
    copyBtn?.addEventListener('click', async () => {
      try { await navigator.clipboard.writeText(url); } catch (e) { linkInput.select(); document.execCommand('copy'); }
      copied?.classList.remove('hidden');
      setTimeout(() => copied?.classList.add('hidden'), 1600);
    });
  }

  const pills = document.querySelectorAll('[data-episode-pill]');
  const cards = document.querySelectorAll('[data-episode-card]');
  if (pills.length && cards.length) {
    const setActive = (id) => {
      pills.forEach((p) => p.classList.toggle('is-active', p.dataset.episodePill === String(id)));
    };
    pills.forEach((pill) => {
      pill.addEventListener('click', () => setActive(pill.dataset.episodePill));
    });
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const id = entry.target.id?.replace('ep-', '');
            if (id) setActive(id);
          }
        });
      }, { rootMargin: '-40% 0px -50% 0px', threshold: 0 });
      cards.forEach((c) => observer.observe(c));
    }
  }
})();
</script>
@endpush
@endsection
