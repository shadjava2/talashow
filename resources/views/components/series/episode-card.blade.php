@props([
    'series',
    'episode',
    'imgUrl',
    'epShort' => 'EP',
    'userState' => null,
    'index' => 0,
])

@php
    $isEpAvailable = method_exists($episode, 'isPublished') ? $episode->isPublished() : true;
    $epHref = route('episode.show', [$series->slug, $episode->id]);
    $thumbRaw = $episode->thumbnail ?? $series->poster ?? '/images/placeholders/placeholder.svg';
    $thumb = $imgUrl($thumbRaw);
    $label = $episode->labelForLocale();
    $title = $episode->titleForLocale() ?: $label;
    $durationSec = (int) ($episode->duration ?? 0);
    $durationLabel = $durationSec > 0
        ? sprintf('%d:%02d', intdiv($durationSec, 60), $durationSec % 60)
        : null;

    $progress = (int) ($userState->watch_progress ?? 0);
    $isCompleted = (bool) ($userState->is_completed ?? false);
    $progressPct = ($durationSec > 0 && $progress > 0)
        ? min(100, (int) round(($progress / $durationSec) * 100))
        : 0;

    $isLocked = ! $episode->is_free && ! auth()->check();
    $needsCoins = ! $episode->is_free && auth()->check() && ! $episode->isUnlockedForUser();
@endphp

<article
    id="ep-{{ $episode->id }}"
    class="ts-episode-card {{ ! $isEpAvailable ? 'ts-episode-card--locked' : '' }} {{ $isCompleted ? 'ts-episode-card--done' : '' }}"
    data-episode-card
    data-episode-index="{{ $index }}"
>
    @if($isEpAvailable)
        <a href="{{ $epHref }}" class="ts-episode-card__link">
    @else
        <div class="ts-episode-card__link ts-episode-card__link--disabled">
    @endif

        <span class="ts-episode-card__num" aria-hidden="true">{{ $episode->episode_number }}</span>

        <div class="ts-episode-card__thumb">
            <img
                src="{{ $thumb }}"
                alt=""
                loading="lazy"
                decoding="async"
                class="ts-episode-card__img js-skeleton-img"
                onerror="this.onerror=null; this.src='{{ asset('/images/placeholders/placeholder.svg') }}';"
            >
            @if($isEpAvailable)
                <span class="ts-episode-card__play" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                </span>
            @endif
            @if($progressPct > 0 && ! $isCompleted)
                <span class="ts-episode-card__progress" style="width: {{ $progressPct }}%"></span>
            @endif
            @if($isCompleted)
                <span class="ts-episode-card__done-badge">✓</span>
            @endif
        </div>

        <div class="ts-episode-card__body">
            <div class="ts-episode-card__head">
                <span class="ts-episode-card__label">{{ $label }}</span>
                @if($episode->is_free)
                    <span class="ts-episode-card__pill ts-episode-card__pill--free">{{ __('ui.series.free') }}</span>
                @elseif($needsCoins && $episode->unlock_coins > 0)
                    <span class="ts-episode-card__pill ts-episode-card__pill--coins">{{ $episode->unlock_coins }} {{ __('ui.series.coins') }}</span>
                @elseif($isLocked)
                    <span class="ts-episode-card__pill ts-episode-card__pill--lock">{{ __('ui.series.unlock_required') }}</span>
                @endif
            </div>
            <h3 class="ts-episode-card__title">{{ $title }}</h3>
            <p class="ts-episode-card__meta">
                @if($durationLabel)
                    <span>{{ $durationLabel }}</span>
                @endif
                @if($isCompleted)
                    <span class="ts-episode-card__dot">·</span><span class="text-green-400">{{ __('ui.series.watched') }}</span>
                @elseif($progressPct >= 8)
                    <span class="ts-episode-card__dot">·</span><span>{{ __('ui.series.continue_watching') }} ({{ $progressPct }}%)</span>
                @elseif($isEpAvailable)
                    <span class="ts-episode-card__dot">·</span><span>{{ __('ui.series.tap_to_play') }}</span>
                @endif
            </p>
            @if(! $isEpAvailable)
                <p class="ts-episode-card__soon">
                    @if($episode->published_at)
                        {{ __('ui.home.available_on_datetime', ['date' => $episode->published_at->format('d/m/Y'), 'time' => $episode->published_at->format('H:i')]) }}
                    @else
                        {{ __('ui.series.coming_soon') }}
                    @endif
                </p>
            @endif
        </div>

        @if($isEpAvailable)
            <span class="ts-episode-card__cta" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
            </span>
        @endif

    @if($isEpAvailable)
        </a>
    @else
        </div>
        <div class="ts-episode-card__notify px-4 pb-3">
            @auth
                <form method="POST" action="{{ route('episode.notify', [$series->slug, $episode->id]) }}">
                    @csrf
                    <button type="submit" class="text-sm text-red-400 hover:text-red-300 font-semibold">
                        {{ __('ui.series.notify_me') }}
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-sm text-red-400 hover:text-red-300 font-semibold">
                    {{ __('ui.series.login_to_be_notified') }}
                </a>
            @endauth
        </div>
    @endif
</article>
