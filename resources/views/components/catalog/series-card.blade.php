@props([
    'series',
    'imgUrl' => null,
    'variant' => 'grid',
    'rank' => null,
    'genreNameMap' => [],
])

@php
    use App\Support\CatalogBadge;

    $resolveUrl = $imgUrl ?? fn ($u) => \App\Support\MediaDisplay::url($u);
    $poster = $series->poster ?? '/images/placeholders/placeholder.svg';
    $epCount = (int) ($series->active_episodes_count ?? $series->total_episodes ?? 0);
    $scheduled = $series->published_at && $series->published_at->isFuture();
    $badge = CatalogBadge::forSeries($series);
    $isRanked = $variant === 'ranked';
    $isRow = $variant === 'row' || $isRanked;
@endphp

<a
    href="{{ route('series.show', $series->slug) }}"
    class="ts-poster-card ts-poster-card--{{ $variant }} ts-poster-card--sober group block min-w-0"
    aria-label="{{ $series->titleForLocale() }}"
>
    @if($isRanked && $rank)
        <span class="ts-poster-card__rank" aria-hidden="true">{{ $rank }}</span>
    @endif

    <div class="ts-poster-card__inner">
        <div class="ts-poster-card__media">
            <img
                src="{{ $resolveUrl($poster) }}"
                alt=""
                loading="lazy"
                decoding="async"
                class="ts-poster-card__img js-skeleton-img"
                onerror="this.onerror=null; this.src='{{ asset('/images/placeholders/placeholder.svg') }}';"
            >
            {{-- Scrim bas pour lisibilité du compteur --}}
            <div class="ts-poster-card__scrim" aria-hidden="true"></div>

            @if($badge && ! $scheduled)
                <span class="ts-poster-card__tag ts-poster-card__tag--{{ $badge }}">
                    {{ __('ui.catalog.badge_' . $badge) }}
                </span>
            @endif

            @if($scheduled)
                <span class="ts-poster-card__badge">
                    {{ __('ui.home.available_on_date', ['date' => $series->published_at->format('d/m/Y')]) }}
                </span>
            @endif

            @unless($scheduled)
                <div class="ts-poster-card__ep-meta" aria-hidden="true">
                    <svg class="ts-poster-card__ep-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    <span>{{ trans_choice('ui.home.episodes_count', $epCount, ['count' => $epCount]) }}</span>
                </div>
            @endunless
        </div>

        @if(! $isRanked)
            <div class="ts-poster-card__caption">
                <h3 class="ts-poster-card__title">{{ $series->titleForLocale() }}</h3>
            </div>
        @else
            <div class="ts-poster-card__rank-meta">
                <h3 class="ts-poster-card__title">{{ $series->titleForLocale() }}</h3>
            </div>
        @endif
    </div>
</a>
