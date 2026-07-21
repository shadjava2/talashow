@extends('layouts.app')

@section('title', __('ui.nav.home'))

@section('content')
@php
    $imgUrl = fn ($u) => \App\Support\MediaDisplay::url($u);
    $genreRows = $genreRows ?? [];
    $quickGenres = $quickGenres ?? collect();
@endphp

@if($featured->count() > 0)
@php $heroSlides = $featured->take(4); @endphp
<section class="hero-carousel" data-hero-carousel>
    <div class="hero-stage">
        @foreach($heroSlides as $i => $series)
            @php
                $bg = $series->cover_image ?? $series->poster ?? '/images/placeholders/placeholder.svg';
                $genres = null;
                if (is_array($series->genres ?? null) && count($series->genres)) {
                    $map = $genreNameMap ?? [];
                    $items = array_slice($series->genres, 0, 3);
                    $items = array_map(function ($x) use ($map) {
                        $k = strtolower(trim((string) $x));
                        return $map[$k] ?? (string) $x;
                    }, $items);
                    $genres = implode(', ', $items);
                }
            @endphp
            <article class="hero-slide" data-hero-slide data-index="{{ $i }}" aria-hidden="{{ $i === 0 ? 'false' : 'true' }}">
                <img class="hero-bg js-skeleton-img" src="{{ $imgUrl($bg) }}" alt="{{ $series->titleForLocale() }}"
                     loading="{{ $i === 0 ? 'eager' : 'lazy' }}" fetchpriority="{{ $i === 0 ? 'high' : 'auto' }}" decoding="async"
                     onerror="this.onerror=null; this.src='{{ asset('/images/placeholders/placeholder.svg') }}';">
                <div class="hero-overlay"></div>
                <div class="hero-content max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="w-full grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-10 items-center">
                        <div class="max-w-2xl">
                            <div class="mb-4 flex items-center gap-2 flex-wrap">
                                <span class="inline-flex items-center gap-2 px-3 py-1 bg-red-600 text-white text-xs font-semibold rounded">{{ __('ui.home.exclusive_badge') }}</span>
                                @if($series->published_at && $series->published_at->isFuture())
                                    <span class="inline-flex items-center px-3 py-1 bg-white/10 border border-white/15 text-white text-xs font-semibold rounded">
                                        {{ __('ui.home.available_on_datetime', ['date' => $series->published_at->format('d/m/Y'), 'time' => $series->published_at->format('H:i')]) }}
                                    </span>
                                @endif
                                @if($series->rating)<span class="text-xs text-gray-300">★ {{ number_format($series->rating, 1) }}</span>@endif
                            </div>
                            <h1 class="text-4xl md:text-6xl font-black tracking-tight leading-[1.02] mb-4">{{ $series->titleForLocale() }}</h1>
                            <p class="text-base md:text-lg ts-text-secondary mb-4 max-w-xl line-clamp-3">{{ Str::limit($series->descriptionForLocale(), 200) }}</p>
                            <p class="ts-hero-ep-count mb-4">
                                {{ trans_choice('ui.home.episodes_count', (int) ($series->active_episodes_count ?? $series->total_episodes ?? 0), ['count' => (int) ($series->active_episodes_count ?? $series->total_episodes ?? 0)]) }}
                            </p>
                            @if(is_array($series->genres ?? null) && count($series->genres))
                                <div class="ts-hero-genres flex flex-wrap gap-2 mb-6">
                                    @foreach(array_slice($series->genres, 0, 4) as $g)
                                        @php $k = strtolower(trim((string) $g)); $label = ($genreNameMap ?? [])[$k] ?? (string) $g; @endphp
                                        <span class="ts-hero-genre-chip">{{ $label }}</span>
                                    @endforeach
                                </div>
                            @endif
                            <div class="flex flex-wrap gap-3">
                                <a href="{{ route('series.show', $series->slug) }}" class="px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition shadow-lg shadow-red-600/20">
                                    {{ ($series->published_at && $series->published_at->isFuture()) ? __('ui.home.view_date') : __('ui.home.watch_now') }}
                                </a>
                                <a href="{{ route('series.show', $series->slug) }}" class="px-6 py-3 bg-white/10 hover:bg-white/20 rounded-lg font-semibold backdrop-blur-sm transition">{{ __('ui.home.more_info') }}</a>
                            </div>
                        </div>
                        <div class="hidden lg:block">
                            <div class="hero-poster">
                                <img class="js-skeleton-img" src="{{ $imgUrl($series->poster ?? $series->cover_image ?? '/images/placeholders/placeholder.svg') }}"
                                     alt="{{ $series->titleForLocale() }}" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" decoding="async"
                                     onerror="this.onerror=null; this.src='{{ asset('/images/placeholders/placeholder.svg') }}';">
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
    <button type="button" class="hero-nav hero-prev" data-hero-prev aria-label="{{ __('ui.home.carousel.prev') }}">‹</button>
    <button type="button" class="hero-nav hero-next" data-hero-next aria-label="{{ __('ui.home.carousel.next') }}">›</button>
    <div class="hero-rail-wrap">
        <div class="hero-rail" data-hero-rail aria-label="{{ __('ui.home.carousel.select') }}">
            @foreach($heroSlides as $i => $series)
                <button type="button" class="hero-thumb" data-hero-thumb data-index="{{ $i }}" aria-label="{{ __('ui.home.carousel.view_slide', ['title' => $series->titleForLocale()]) }}">
                    <img class="js-skeleton-img" src="{{ $imgUrl($series->poster ?? $series->cover_image ?? '/images/placeholders/placeholder.svg') }}"
                         alt="{{ $series->titleForLocale() }}" loading="lazy" decoding="async"
                         onerror="this.onerror=null; this.src='{{ asset('/images/placeholders/placeholder.svg') }}';">
                    <span class="hero-thumb-title">{{ $series->titleForLocale() }}</span>
                </button>
            @endforeach
        </div>
    </div>
</section>
@endif

<div class="ts-page-dramabox relative z-[2] pb-20" id="catalog-top">
    @if($quickGenres->count())
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-catalog.category-tabs :genres="$quickGenres" />
        </div>
    @endif

    <div class="ts-page-dramabox__sections">
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
    </div>
</div>
@endsection
