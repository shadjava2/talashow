@extends('layouts.app')

@section('title', __('ui.nav.home'))

@section('content')
@php
    $imgUrl = fn ($u) => \App\Support\MediaDisplay::url($u);
    $genreRows = $genreRows ?? [];
    $quickGenres = $quickGenres ?? collect();
@endphp

@if($featured->count() > 0)
@php $heroSlides = $featured->take(6); @endphp
<section class="ts-coverflow" data-hero-carousel aria-roledescription="carousel">
    <div class="ts-coverflow__glow" aria-hidden="true"></div>

    <div class="ts-coverflow__stage" data-coverflow-stage>
        @foreach($heroSlides as $i => $series)
            @php
                $poster = $series->poster ?? $series->cover_image ?? '/images/placeholders/placeholder.svg';
                $desc = Str::limit(strip_tags((string) $series->descriptionForLocale()), 160);
                $genreBits = [];
                if (is_array($series->genres ?? null)) {
                    foreach (array_slice($series->genres, 0, 2) as $g) {
                        $k = strtolower(trim((string) $g));
                        $genreBits[] = ($genreNameMap ?? [])[$k] ?? (string) $g;
                    }
                }
                $epCount = (int) ($series->active_episodes_count ?? $series->total_episodes ?? 0);
            @endphp
            <button
                type="button"
                class="ts-coverflow__card {{ $i === 0 ? 'is-active' : '' }}"
                data-hero-slide
                data-index="{{ $i }}"
                data-title="{{ $series->titleForLocale() }}"
                data-desc="{{ $desc }}"
                data-url="{{ route('series.show', $series->slug) }}"
                data-genres="{{ implode(' • ', $genreBits) }}"
                data-episodes="{{ trans_choice('ui.home.episodes_count', $epCount, ['count' => $epCount]) }}"
                aria-label="{{ $series->titleForLocale() }}"
                aria-hidden="{{ $i === 0 ? 'false' : 'true' }}"
            >
                <img
                    class="ts-coverflow__img js-skeleton-img"
                    src="{{ $imgUrl($poster) }}"
                    alt="{{ $series->titleForLocale() }}"
                    loading="{{ $i === 0 ? 'eager' : 'lazy' }}"
                    decoding="async"
                    onerror="this.onerror=null; this.src='{{ asset('/images/placeholders/placeholder.svg') }}';"
                >
            </button>
        @endforeach
    </div>

    <button type="button" class="ts-coverflow__nav ts-coverflow__nav--prev" data-hero-prev aria-label="{{ __('ui.home.carousel.prev') }}">‹</button>
    <button type="button" class="ts-coverflow__nav ts-coverflow__nav--next" data-hero-next aria-label="{{ __('ui.home.carousel.next') }}">›</button>

    <div class="ts-coverflow__info max-w-3xl mx-auto px-4 text-center">
        <p class="ts-coverflow__meta" data-hero-meta></p>
        <h1 class="ts-coverflow__title" data-hero-title></h1>
        <p class="ts-coverflow__desc" data-hero-desc></p>
        <div class="ts-coverflow__actions">
            <a href="#" class="ts-btn ts-btn--primary px-6 py-3 font-semibold" data-hero-play>{{ __('ui.home.watch_now') }}</a>
            <a href="#" class="ts-btn ts-btn--ghost px-6 py-3 font-semibold" data-hero-more>{{ __('ui.home.more_info') }}</a>
        </div>
        <div class="ts-coverflow__dots" data-hero-dots role="tablist" aria-label="{{ __('ui.home.carousel.select') }}">
            @foreach($heroSlides as $i => $series)
                <button type="button" class="ts-coverflow__dot {{ $i === 0 ? 'is-active' : '' }}" data-hero-thumb data-index="{{ $i }}" aria-label="{{ __('ui.home.carousel.view_slide', ['title' => $series->titleForLocale()]) }}"></button>
            @endforeach
        </div>
    </div>
</section>
@endif

<div class="ts-page-dramabox ts-page-main relative z-[2]" id="catalog-top">
    @if($quickGenres->count())
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-catalog.category-tabs :genres="$quickGenres" />
        </div>
    @endif

    <div class="ts-page-dramabox__sections">
        @if($trending->count())
            <x-catalog.series-row
                :title="__('ui.home.trending')"
                :series="$trending"
                :more-url="route('browse')"
                :img-url="$imgUrl"
                :genre-name-map="$genreNameMap ?? []"
                row-id="home-trending"
            />
        @endif

        @if(($newReleases ?? collect())->count())
            <x-catalog.series-row
                :title="__('ui.catalog.new_releases')"
                :series="$newReleases"
                :more-url="route('browse')"
                :img-url="$imgUrl"
                :genre-name-map="$genreNameMap ?? []"
                row-id="home-new"
            />
        @endif

        @foreach($genreRows as $row)
            <x-catalog.series-row
                :title="$row['genre']->nameForLocale()"
                :series="$row['series']"
                :more-url="$row['url']"
                :img-url="$imgUrl"
                :genre-name-map="$genreNameMap ?? []"
                :row-id="'home-genre-' . $row['genre']->slug"
            />
        @endforeach

        @if($mustWatch->count())
            <x-catalog.series-row
                :title="__('ui.catalog.must_sees')"
                :series="$mustWatch"
                :more-url="route('browse')"
                :img-url="$imgUrl"
                :genre-name-map="$genreNameMap ?? []"
                row-id="home-must-watch"
            />
        @endif
    </div>
</div>
@endsection
