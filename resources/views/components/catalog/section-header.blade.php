@props([
    'title',
    'moreUrl' => null,
    'moreLabel' => null,
    'subtitle' => null,
    'icon' => null,
    'style' => 'dramabox',
])

<div class="ts-section-head {{ $style === 'dramabox' ? 'ts-section-head--dramabox' : '' }}">
    <div class="ts-section-head__main">
        @if($icon && $style !== 'dramabox')
            <span class="ts-section-head__icon" aria-hidden="true">{{ $icon }}</span>
        @endif
        <div>
            <h2 class="ts-section-head__title">{{ $title }}</h2>
            @if($subtitle && $style !== 'dramabox')
                <p class="ts-section-head__subtitle">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    @if($moreUrl)
        <a href="{{ $moreUrl }}" class="ts-section-head__more">
            {{ $moreLabel ?? __('ui.catalog.more') }} ›
        </a>
    @endif
</div>
