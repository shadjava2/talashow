@extends('layouts.app')

@section('title', $episode->titleForLocale())

@php
    $playbackMeta = $playbackMeta ?? [
        'provider' => 'bunny',
        'mode' => 'embed',
        'hls_url' => '',
        'embed_url' => '',
        'poster' => $episode->thumbnail ?? null,
        'subtitles' => [],
        'is_ready' => true,
    ];
    $nextEpisodeUrl = $nextEpisodeUrl ?? null;
    $coinsNeeded = $coinsNeeded ?? $episode->coinUnlockCost();
    $needsMoreCoins = $needsMoreCoins ?? false;
    $needsSubscriptionOnly = $needsSubscriptionOnly ?? false;
@endphp

@push('styles')
<link rel="preconnect" href="https://player.mediadelivery.net" crossorigin>
<link rel="dns-prefetch" href="https://player.mediadelivery.net">
@if(!empty($nextEpisodeUrl))
<link rel="prefetch" as="document" href="{{ $nextEpisodeUrl }}">
@endif
<style>
    /* Laisse les clics/touches atteindre l’iframe Bunny (évite zones mortes à droite / sur mobile). */
    #ts-player-shell {
        pointer-events: none;
        isolation: isolate;
    }
    #ts-player-shell iframe,
    #ts-bunny-embed-root iframe {
        pointer-events: auto;
    }
    @media (max-width: 767px) {
        .ts-episode-page {
            padding-bottom: calc(5rem + env(safe-area-inset-bottom, 0px));
        }
    }
</style>
@endpush

@section('content')
@php
    $__embedUrlGlobal = trim((string) ($playbackMeta['embed_url'] ?? ''));
    $__useEmbedGlobal = $__embedUrlGlobal !== '';
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 ts-episode-page">
    @php
        $settings = app(\App\Services\SettingsService::class);
        $epSingular = $settings->get('episode_label_singular', __('ui.labels.episode_singular'));
        $epPlural = $settings->get('episode_label_plural', __('ui.labels.episode_plural'));
        // UI labels must be translated (settings can be a single-language override).
        $epUi = __('ui.labels.episode_singular');

        $imgUrl = fn ($u) => \App\Support\MediaDisplay::url($u);
    @endphp
    <!-- Breadcrumbs -->
    <nav class="ts-crumb mb-6">
        <a href="{{ route('home') }}">{{ __('ui.nav.home') }}</a>
        <span class="mx-2 opacity-60">›</span>
        <a href="{{ route('series.show', $series->slug) }}">{{ $series->titleForLocale() }}</a>
        <span class="mx-2 opacity-60">›</span>
        <span class="ts-crumb__current">{{ $episode->titleForLocale() }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start">
        <!-- Video Player -->
        <div class="lg:col-span-3 min-w-0 w-full">
            @if(auth()->check() && !$hasSubscription && $isUnlocked && $currentCoinUnlockUntil)
                <div class="mb-4 bg-amber-500/10 border border-amber-500/30 rounded-lg p-4">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div class="text-sm text-amber-100">
                            <span class="font-semibold">{{ __('ui.episode.coin_unlock_title') }}</span>
                            <span class="text-amber-200/80">— {{ __('ui.episode.temp_access') }}</span>
                        </div>
                        <div class="text-xs font-semibold px-3 py-1 rounded bg-amber-500/20 text-amber-100">
                            {{ __('ui.episode.expires') }} {{ \Illuminate\Support\Carbon::parse($currentCoinUnlockUntil)->diffForHumans() }}
                        </div>
                    </div>
                    <p class="text-xs text-amber-200/80 mt-2">
                        {!! __('ui.episode.coin_unlock_terms_html', ['days' => 7]) !!}
                    </p>
                </div>
            @endif
            @if($isUnlocked || $episode->is_free)
                @if(empty($playbackMeta['is_ready']) && ($playbackMeta['provider'] ?? '') === 'bunny')
                    <div class="mb-4 rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-100">
                        {{ __('ui.player.bunny_processing') }}
                    </div>
                @endif
                {{-- Lecteur Bunny uniquement (iframe https://player.mediadelivery.net/play/…) --}}
                <div class="mb-6 space-y-3">
                    @if(count($videoLangOptions ?? []) > 1)
                    <div class="flex flex-wrap items-center gap-2">
                        <label for="video-lang-select" class="sr-only">{{ __('ui.player.language') }}</label>
                        <div class="relative inline-flex">
                            <select
                                id="video-lang-select"
                                data-series-id="{{ (int) $series->id }}"
                                data-episode-id="{{ (int) $episode->id }}"
                                data-selected="{{ $selectedVideoLang }}"
                                class="ts-input text-xs font-semibold appearance-none pr-9"
                                title="{{ __('ui.player.language') }}"
                            >
                                @foreach(($videoLangOptions ?? []) as $opt)
                                    <option value="{{ $opt['code'] }}" {{ $opt['code'] === $selectedVideoLang ? 'selected' : '' }}>
                                        {{ $opt['label'] }} ({{ $opt['code'] }}){{ !empty($opt['native']) ? (' — ' . $opt['native']) : '' }}{{ !$opt['available'] ? (' — ' . __('ui.player.unavailable')) : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <svg class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4" style="color: var(--ts-text-muted)" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                    @endif
                    <div class="rounded-lg overflow-hidden bg-black">
                        @if($__useEmbedGlobal)
                            <div class="relative w-full" id="ts-player-shell">
                                <x-video.bunny-player
                                    :embed-url="$__embedUrlGlobal"
                                    :title="$episode->titleForLocale()"
                                    class="w-full"
                                />
                            </div>
                        @else
                            <div class="min-h-[46vh] flex items-center justify-center px-6 py-12 text-center">
                                <div>
                                    <div class="text-2xl font-bold mb-2">{{ __('ui.player.video_unavailable_title') }}</div>
                                    <div class="text-gray-300">
                                        {!! __('ui.player.video_unavailable_subtitle_html', ['lang' => strtoupper($selectedVideoLang)]) !!}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                @if($explicitLang && !$selectedVideoUrl)
                    <div class="-mt-4 mb-4 bg-red-600/15 border border-red-600/30 rounded-lg p-4 text-red-100">
                        <span class="font-semibold">{{ __('ui.player.unavailable_selected_title') }}</span>
                        {{ __('ui.player.unavailable_selected_subtitle') }}
                    </div>
                @endif
                {{-- Engagement bar (Like / Views / Favorite) --}}
                <div class="ts-engagement-bar">
                    <div class="ts-engagement-bar__left">
                        <button
                            type="button"
                            id="ts-series-like-btn"
                            data-ts-engagement="like"
                            data-url="{{ route('series.like', $series->slug) }}"
                            data-liked="{{ isset($isLiked) && $isLiked ? '1' : '0' }}"
                            class="ts-chip-action {{ isset($isLiked) && $isLiked ? 'is-active' : '' }}"
                            title="{{ __('ui.engagement.like') }}"
                        >
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 21s-7.2-4.43-9.6-8.05C.7 10.2 1.3 6.9 4.1 5.2c2-1.2 4.5-.8 6.1.9 1.6-1.7 4.1-2.1 6.1-.9 2.8 1.7 3.4 5 1.7 7.75C19.2 16.57 12 21 12 21z"/>
                            </svg>
                            <span class="ts-like-label">{{ __('ui.engagement.like') }}</span>
                            <span class="ts-chip-action__sep">•</span>
                            <span id="ts-like-count">{{ number_format((int) ($series->likes_count ?? 0), 0, ',', ' ') }}</span>
                        </button>

                        <div
                            class="ts-chip-action"
                            data-ts-view-track
                            data-view-url="{{ route('episode.view', $episode->id) }}"
                            data-episode-id="{{ $episode->id }}"
                        >
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"/>
                            </svg>
                            <span>{{ __('ui.engagement.views_episode') }}:</span>
                            <span id="ts-views-count">{{ number_format((int) ($episode->views_count ?? 0), 0, ',', ' ') }}</span>
                        </div>
                    </div>

                    <button
                        type="button"
                        id="ts-series-favorite-btn"
                        data-ts-engagement="favorite"
                        data-url="{{ route('series.favorite', $series->slug) }}"
                        data-favorited="{{ isset($isFavorited) && $isFavorited ? '1' : '0' }}"
                        class="ts-chip-action {{ isset($isFavorited) && $isFavorited ? 'is-active' : '' }}"
                        title="{{ __('ui.engagement.favorite') }}"
                    >
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 17.3l-6.2 3.7 1.7-7.1L2 8.9l7.3-.6L12 1.8l2.7 6.5 7.3.6-5.5 5 1.7 7.1z"/>
                        </svg>
                        <span class="ts-fav-label">{{ isset($isFavorited) && $isFavorited ? __('ui.engagement.favorited') : __('ui.engagement.favorite') }}</span>
                    </button>
                </div>
            @else
                <!-- Locked Episode -->
                <div class="ts-surface ts-surface--pad text-center">
                    <svg class="w-20 h-20 text-red-500 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                    <h3 class="text-2xl font-bold mb-4 ts-page-title">{{ __('ui.episode.locked_title', ['label' => $epUi]) }}</h3>
                    <p class="ts-page-sub mb-6">
                        @if(($needsSubscriptionOnly ?? false))
                            {{ __('ui.episode.premium_required') }}
                        @elseif(($coinsNeeded ?? 0) > 0)
                            {{ __('ui.episode.unlock_with_coins', ['coins' => $coinsNeeded, 'days' => 7]) }}
                        @elseif($episode->is_premium_only)
                            {{ __('ui.episode.premium_required') }}
                        @else
                            {{ __('ui.episode.unlock_with_coins', ['coins' => $episode->unlock_coins, 'days' => 7]) }}
                        @endif
                    </p>
                    @auth
                        @if($canUnlock && ($coinsNeeded ?? 0) > 0)
                            <button onclick="unlockEpisode({{ $episode->id }})"
                                    class="px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">
                                {{ __('ui.episode.unlock_button', ['coins' => $coinsNeeded, 'days' => 7]) }}
                            </button>
                        @elseif($needsMoreCoins ?? false)
                            <a href="{{ route('payment.recharge') }}"
                               class="inline-block px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">
                                {{ __('ui.episode.recharge_coins') }}
                            </a>
                        @elseif($needsSubscriptionOnly ?? false)
                            <a href="{{ route('payment.recharge') }}"
                               class="inline-block px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">
                                {{ __('ui.nav.subscribe') }}
                            </a>
                        @else
                            <a href="{{ route('payment.recharge') }}"
                               class="inline-block px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">
                                {{ __('ui.episode.recharge_coins') }}
                            </a>
                        @endif
                    @else
                        <a href="{{ route('login') }}"
                           class="inline-block px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">
                            {{ __('ui.episode.login_to_unlock') }}
                        </a>
                    @endauth
                </div>
            @endif

            <!-- Episode Info -->
            <div class="mb-6">
                <h1 class="text-3xl font-bold mb-2 ts-page-title">{{ $episode->titleForLocale() }}</h1>
                <p class="ts-page-sub">
                    {{ $series->titleForLocale() }} •
                    {{ $episode->labelForLocale() }}
                </p>
            </div>

            @if($episode->descriptionForLocale())
            <div class="ts-surface ts-surface--pad mb-6">
                <h2 class="text-xl font-semibold mb-3 ts-page-title">{{ __('ui.episode.description') }}</h2>
                <p class="ts-text-secondary leading-relaxed">{{ $episode->descriptionForLocale() }}</p>
            </div>
            @endif
        </div>

        <!-- Episodes Sidebar -->
        <div class="lg:col-span-1 min-w-0 w-full relative z-0">
            <div class="ts-surface ts-surface--pad lg:sticky lg:top-20">
                <h3 class="text-lg font-semibold mb-4 ts-page-title">{{ $epUi }} ({{ $allEpisodes->count() }})</h3>
                <div class="space-y-2 max-h-[600px] overflow-y-auto">
                    @foreach($allEpisodes as $ep)
                    @php
                        $isEpUnlocked = $ep->is_free
                            || (auth()->check() && ($hasSubscription || in_array($ep->id, $unlockedEpisodeIds, true)));
                        $isEpLocked = !$isEpUnlocked;
                        $coinUntil = isset($coinUnlocks[$ep->id]) ? \Illuminate\Support\Carbon::parse($coinUnlocks[$ep->id]) : null;
                        $lockLabel = null;
                        if (!$ep->is_free) {
                            if ($ep->is_premium_only) {
                                $lockLabel = 'VIP';
                            } elseif (($ep->unlock_coins ?? 0) > 0) {
                                $lockLabel = $ep->unlock_coins . '🪙';
                            }
                        }
                    @endphp
                    <a href="{{ route('episode.show', [$series->slug, $ep->id]) }}"
                       class="ts-ep-sidebar__item {{ $ep->id === $episode->id ? 'is-current' : '' }}">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">{{ $ep->labelForLocale() }}</span>
                            <div class="flex items-center gap-2">
                                @if($coinUntil && $isEpUnlocked && !$hasSubscription)
                                    <span
                                        class="px-2 py-0.5 rounded text-[10px] font-semibold"
                                        style="background: var(--ts-accent-soft); color: var(--ts-accent)"
                                        title="{{ __('ui.episode.coin_unlock_title') }} — {{ __('ui.episode.expires') }} {{ $coinUntil->toDateTimeString() }}"
                                    >
                                        {{ __('ui.episode.expires') }} {{ $coinUntil->diffForHumans() }}
                                    </span>
                                @endif
                                @if($isEpLocked)
                                    @if($lockLabel)
                                        <span class="px-2 py-0.5 rounded text-xs font-semibold" style="background: var(--ts-btn-ghost-bg); color: var(--ts-text-secondary)">
                                            {{ $lockLabel }}
                                        </span>
                                    @endif
                                    <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20" aria-label="{{ __('ui.episode.locked_aria') }}">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20" aria-label="{{ __('ui.episode.available_aria') }}">
                                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.172 7.707 8.879a1 1 0 10-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                                    </svg>
                                @endif
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@guest
    @if($episode->is_free)
        <!-- Guest gate modal (inciter à créer un compte après 10s) -->
        <div id="guest-gate-modal" class="fixed inset-0 z-[60] hidden" aria-hidden="true">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
            <div class="relative w-full h-full flex items-center justify-center p-4">
                <div class="max-w-lg w-full ts-panel ts-surface--pad">
                    <div>
                        <h3 class="text-2xl font-bold ts-page-title">{{ __('ui.episode.guest_gate.title') }}</h3>
                        <p class="ts-text-secondary mt-2 leading-relaxed">{!! __('ui.episode.guest_gate.subtitle_html') !!}</p>
                    </div>

                    <div class="mt-6 flex flex-col sm:flex-row gap-3">
                        <a href="{{ route('login') }}" class="ts-btn-primary w-full sm:w-auto text-center">
                            {{ __('ui.episode.guest_gate.login_cta') }}
                        </a>
                        <a href="{{ route('register') }}" class="ts-btn-soft w-full sm:w-auto text-center">
                            {{ __('ui.episode.guest_gate.register_cta') }}
                        </a>
                    </div>

                    <p class="text-xs ts-page-sub mt-4">
                        {{ __('ui.episode.guest_gate.footer_hint') }}
                    </p>
                </div>
            </div>
        </div>
    @endif
@endguest

@push('scripts')
<script>
    (function setupVideoLangSelect() {
        const select = document.getElementById('video-lang-select');
        if (!select) return;
        const seriesId = select.dataset.seriesId || '';
        const current = (select.dataset.selected || select.value || '').toString();
        const key = 'talashow:video_lang_pref:series:' + (seriesId || '0');
        try {
            const url = new URL(window.location.href);
            const has = url.searchParams.get('vlang');
            const pref = localStorage.getItem(key);
            if (!has && pref && pref !== current) {
                url.searchParams.set('vlang', pref);
                window.location.replace(url.toString());
                return;
            }
        } catch (_) {}
        try { localStorage.setItem(key, current); } catch (_) {}
        select.addEventListener('change', () => {
            const next = (select.value || '').toString();
            if (!next || next === current) return;
            try { localStorage.setItem(key, next); } catch (_) {}
            try {
                const url = new URL(window.location.href);
                url.searchParams.set('vlang', next);
                window.location.href = url.toString();
            } catch (_) {
                window.location.href = window.location.pathname + '?vlang=' + encodeURIComponent(next);
            }
        });
    })();

    @guest
        @if($episode->is_free)
        (function guestGateBunnyPlayer() {
            const modal = document.getElementById('guest-gate-modal');
            if (!modal) return;
            setTimeout(function () {
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
            }, 10000);
        })();
        @endif
    @endguest

    async function unlockEpisode(episodeId) {
        const coins = {{ (int) $episode->unlock_coins }};
        const days = 7;
        const msg = @json(__('ui.episode.unlock_confirm_message', ['coins' => ':coins', 'days' => ':days']))
            .replace(':coins', String(coins))
            .replace(':days', String(days));

        const ok = await (window.talashowConfirm ? window.talashowConfirm({
            title: @json(__('ui.episode.unlock_confirm_title')),
            message: msg,
            confirmText: @json(__('ui.episode.unlock_confirm_cta')),
            cancelText: @json(__('ui.common.cancel'))
        }) : Promise.resolve(confirm(msg)));

        if (!ok) return;

        try {
            const response = await fetch('/episode/' + episodeId + '/unlock', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            const data = await response.json();
            if (data.success) {
                location.reload();
            } else {
                if (data.redirect_to) {
                    window.location.href = data.redirect_to;
                    return;
                }
                alert(data.message || @json(__('ui.episode.unlock_error_generic')));
            }
        } catch (error) {
            alert(@json(__('ui.episode.unlock_error_prefix')) + ' ' + (error?.message || ''));
        }
    }
</script>
@endpush
@endsection
