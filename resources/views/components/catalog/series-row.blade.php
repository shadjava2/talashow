@props([
    'title',
    'series',
    'moreUrl' => null,
    'moreLabel' => null,
    'imgUrl' => null,
    'rowId' => null,
    'layout' => 'scroll',
    'icon' => null,
    'subtitle' => null,
    'genreNameMap' => [],
    'limit' => 6,
])

@php
    $isRanked = $layout === 'ranked';
    $sectionClass = 'ts-catalog-row-section' . ($isRanked ? ' ts-catalog-row-section--ranked' : '');
    $items = $series instanceof \Illuminate\Support\Collection
        ? $series->take($limit)
        : collect($series)->take($limit);
@endphp

<section
    class="ts-dramabox-row {{ $sectionClass }}"
    data-catalog-reveal
    @if($rowId) id="{{ $rowId }}" @endif
>
    <div class="ts-dramabox-row__inner max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <x-catalog.section-header
            :title="$title"
            :subtitle="$subtitle"
            :icon="$icon"
            :more-url="$moreUrl"
            :more-label="$moreLabel"
            style="dramabox"
        />

        <div class="ts-dramabox-row__grid {{ $isRanked ? 'ts-dramabox-row__grid--ranked' : '' }}">
            @foreach($items as $index => $item)
                <x-catalog.series-card
                    :series="$item"
                    :img-url="$imgUrl"
                    :variant="$isRanked ? 'ranked' : 'row'"
                    :rank="$isRanked ? $index + 1 : null"
                    :genre-name-map="$genreNameMap"
                />
            @endforeach
        </div>
    </div>
</section>
