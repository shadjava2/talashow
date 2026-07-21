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
    {{-- Fond cinématique plein écran --}}
    <div class="ts-coverflow__bgs" aria-hidden="true">
        @foreach($heroSlides as $i => $series)
            @php $bg = $series->cover_image ?? $series->poster ?? '/images/placeholders/placeholder.svg'; @endphp
            <div
                class="ts-coverflow__bg {{ $i === 0 ? 'is-active' : '' }}"
                data-hero-bg
                data-index="{{ $i }}"
                style="background-image: url('{{ $imgUrl($bg) }}')"
            ></div>
        @endforeach
    </div>
    <div class="ts-coverflow__scrim" aria-hidden="true"></div>
    <div class="ts-coverflow__glow" aria-hidden="true"></div>
    <div class="ts-coverflow__noise" aria-hidden="true"></div>

    <div class="ts-coverflow__layout max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="ts-coverflow__copy" data-hero-info>
            <span class="ts-coverflow__badge" data-hero-anim="1">{{ __('ui.home.exclusive_badge') }}</span>
            <p class="ts-coverflow__meta" data-hero-meta data-hero-anim="2"></p>
            <h1 class="ts-coverflow__title" data-hero-title data-hero-anim="3"></h1>
            <p class="ts-coverflow__desc" data-hero-desc data-hero-anim="4"></p>
            <div class="ts-coverflow__actions" data-hero-anim="5">
                <a href="#" class="ts-btn ts-btn--primary px-6 py-3 font-semibold" data-hero-play>{{ __('ui.home.watch_now') }}</a>
                <a href="#" class="ts-btn ts-btn--ghost px-6 py-3 font-semibold" data-hero-more>{{ __('ui.home.more_info') }}</a>
            </div>
        </div>

        <div class="ts-coverflow__stage-wrap" data-hero-anim="stage">
            <div class="ts-coverflow__stage" data-coverflow-stage>
                @foreach($heroSlides as $i => $series)
                    @php
                        $poster = $series->poster ?? $series->cover_image ?? '/images/placeholders/placeholder.svg';
                        $desc = Str::limit(strip_tags((string) $series->descriptionForLocale()), 180);
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
                        <span class="ts-coverflow__card-shine" aria-hidden="true"></span>
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
        </div>
    </div>

    <div class="ts-coverflow__controls">
        <button type="button" class="ts-coverflow__nav" data-hero-prev aria-label="{{ __('ui.home.carousel.prev') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <div class="ts-coverflow__dots" data-hero-dots role="tablist" aria-label="{{ __('ui.home.carousel.select') }}">
            @foreach($heroSlides as $i => $series)
                <button type="button" class="ts-coverflow__dot {{ $i === 0 ? 'is-active' : '' }}" data-hero-thumb data-index="{{ $i }}" aria-label="{{ __('ui.home.carousel.view_slide', ['title' => $series->titleForLocale()]) }}"></button>
            @endforeach
        </div>
        <button type="button" class="ts-coverflow__nav" data-hero-next aria-label="{{ __('ui.home.carousel.next') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>

    <div class="ts-coverflow__progress" aria-hidden="true">
        <span class="ts-coverflow__progress-bar" data-hero-progress></span>
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
