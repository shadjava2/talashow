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
    $isDramaBoxRow = $variant === 'row';

    $genreChips = [];
    if (is_array($series->genres ?? null)) {
        $map = is_array($genreNameMap) ? $genreNameMap : [];
        $chipLimit = $isDramaBoxRow ? 2 : 3;
        foreach (array_slice($series->genres, 0, $chipLimit) as $raw) {
            $k = strtolower(trim((string) $raw));
            $genreChips[] = $map[$k] ?? (string) $raw;
        }
    }
@endphp

<a
    href="{{ route('series.show', $series->slug) }}"
    class="ts-poster-card ts-poster-card--{{ $variant }} group block min-w-0"
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
            <div class="ts-poster-card__shade" aria-hidden="true"></div>

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

            <div class="ts-poster-card__hover" aria-hidden="true">
                <span class="ts-poster-card__play">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </span>
            </div>
        </div>

        @if($isDramaBoxRow)
            <div class="ts-poster-card__dramabox-meta">
                <h3 class="ts-poster-card__title">{{ $series->titleForLocale() }}</h3>
                <p class="ts-poster-card__ep-count">
                    {{ trans_choice('ui.home.episodes_count', $epCount, ['count' => $epCount]) }}
                </p>
                @if(count($genreChips))
                    <div class="ts-poster-card__genres">
                        @foreach($genreChips as $chip)
                            <span class="ts-poster-card__genre">{{ $chip }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        @elseif(! $isRanked)
            <div class="ts-poster-card__meta">
                <h3 class="ts-poster-card__title">{{ $series->titleForLocale() }}</h3>
                <p class="ts-poster-card__episodes">
                    {{ trans_choice('ui.home.episodes_count_short', $epCount, ['count' => $epCount]) }}
                </p>
                @if(count($genreChips))
                    <div class="ts-poster-card__genres">
                        @foreach($genreChips as $chip)
                            <span class="ts-poster-card__genre">{{ $chip }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <div class="ts-poster-card__rank-meta">
                <h3 class="ts-poster-card__title">{{ $series->titleForLocale() }}</h3>
                <p class="ts-poster-card__episodes">
                    {{ trans_choice('ui.home.episodes_count', $epCount, ['count' => $epCount]) }}
                </p>
            </div>
        @endif
    </div>
</a>
