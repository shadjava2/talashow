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
])

@php
    $rowKey = $rowId ?? 'row-' . md5($title . ($moreUrl ?? ''));
    $isRanked = $layout === 'ranked';
    $sectionClass = 'ts-catalog-row-section' . ($isRanked ? ' ts-catalog-row-section--ranked' : '');
@endphp

<section
    class="ts-dramabox-row {{ $sectionClass }}"
    data-catalog-row-section
    data-catalog-reveal
    @if($rowId) id="{{ $rowId }}" @endif
>
    <div class="ts-dramabox-row__head max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <x-catalog.section-header
        :title="$title"
        :subtitle="$subtitle"
        :icon="$icon"
        :more-url="$moreUrl"
        :more-label="$moreLabel"
        style="dramabox"
    />
    </div>

    <div class="ts-catalog-row ts-dramabox-row__scroll" data-catalog-row>
        <button type="button" class="ts-catalog-row__nav ts-catalog-row__nav--prev" data-catalog-row-prev aria-label="{{ __('ui.catalog.row_prev') }}">
            ‹
        </button>

        <div class="ts-catalog-row__viewport" tabindex="0">
            <div class="ts-catalog-row__track {{ $isRanked ? 'ts-catalog-row__track--ranked' : '' }}">
                @foreach($series as $index => $item)
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

        <button type="button" class="ts-catalog-row__nav ts-catalog-row__nav--next" data-catalog-row-next aria-label="{{ __('ui.catalog.row_next') }}">
            ›
        </button>
    </div>
</section>
