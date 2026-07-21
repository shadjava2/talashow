@extends('layouts.app')

@section('title', __('ui.browse.title'))

@section('content')
<div class="ts-page-browse relative z-[2] pb-16">
    @php
        $imgUrl = fn ($u) => \App\Support\MediaDisplay::url($u);
        $activeGenre = request('genre');
        $isCatalogMode = $isCatalogMode ?? false;
        $genreNameMap = $genreNameMap ?? [];
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 md:pt-8">
        <div class="flex items-start justify-between gap-4 mb-4">
            <div>
                <h1 class="ts-page-browse__title">{{ __('ui.browse.title') }}</h1>
                @if(request()->filled('search'))
                    <p class="text-sm ts-text-muted mt-2">
                        {{ __('ui.browse.results_for') }}
                        <span class="text-white font-semibold">“{{ request('search') }}”</span>
                    </p>
                @elseif($isCatalogMode)
                    <p class="text-sm ts-page-browse__intro mt-2">{{ __('ui.catalog.browse_intro') }}</p>
                @endif
            </div>
            @if(request()->filled('search') || ($activeGenre && $activeGenre !== 'all'))
                <a href="{{ route('browse') }}" class="text-sm text-gray-300 hover:text-white underline underline-offset-4 shrink-0">
                    {{ __('ui.browse.reset') }}
                </a>
            @endif
        </div>

        <x-catalog.category-tabs :genres="$genres" :active-slug="$activeGenre" />
    </div>

    @if($isCatalogMode)
        <div class="ts-page-dramabox__sections mt-2">
            @if(($mustWatch ?? collect())->count())
                <x-catalog.series-row
                    :title="__('ui.catalog.must_sees')"
                    :series="$mustWatch"
                    :more-url="route('browse')"
                    :img-url="$imgUrl"
                    :genre-name-map="$genreNameMap"
                    row-id="browse-must"
                />
            @endif

            @if(($trending ?? collect())->count())
                <x-catalog.series-row
                    :title="__('ui.home.trending')"
                    :series="$trending"
                    :more-url="route('browse')"
                    :img-url="$imgUrl"
                    :genre-name-map="$genreNameMap"
                    row-id="browse-trending"
                />
            @endif

            @if(($newReleases ?? collect())->count())
                <x-catalog.series-row
                    :title="__('ui.catalog.new_releases')"
                    :series="$newReleases"
                    :img-url="$imgUrl"
                    :genre-name-map="$genreNameMap"
                    row-id="browse-new"
                />
            @endif

            @foreach($genreRows ?? [] as $row)
                <x-catalog.series-row
                    :title="$row['genre']->nameForLocale()"
                    :series="$row['series']"
                    :more-url="$row['url']"
                    :img-url="$imgUrl"
                    :genre-name-map="$genreNameMap"
                    :row-id="'browse-genre-' . $row['genre']->slug"
                />
            @endforeach
        </div>

        @if($series->count())
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
                <x-catalog.section-header :title="__('ui.catalog.all_series')" style="dramabox" />
                <div class="ts-catalog-grid ts-catalog-grid--drama mt-4">
                    @foreach($series as $s)
                        <x-catalog.series-card :series="$s" :img-url="$imgUrl" variant="grid" :genre-name-map="$genreNameMap" />
                    @endforeach
                </div>
                @if($series->hasPages())
                    <div class="mt-10">{{ $series->links() }}</div>
                @endif
            </div>
        @endif
    @else
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
            <div class="ts-catalog-grid ts-catalog-grid--drama">
                @forelse($series as $s)
                    <x-catalog.series-card :series="$s" :img-url="$imgUrl" variant="grid" :genre-name-map="$genreNameMap" />
                @empty
                    <div class="col-span-full text-center py-16 rounded-2xl bg-black/35 border border-white/10">
                        <p class="text-gray-400">{{ __('ui.browse.none_found') }}</p>
                    </div>
                @endforelse
            </div>
            @if($series->hasPages())
                <div class="mt-10">{{ $series->links() }}</div>
            @endif
        </div>
    @endif
</div>
@endsection
