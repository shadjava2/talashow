@props([
    'embedUrl' => '',
    'poster' => null,
    'title' => '',
    'showProcessing' => false,
    'autoplay' => false,
    'muted' => false,
    'class' => '',
])

@php
    $allow = 'accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture; fullscreen';
@endphp
<div
    id="ts-bunny-embed-root"
    {{ $attributes->merge(['class' => 'relative w-full aspect-video bg-black overflow-hidden rounded-lg '.$class]) }}
>
    @if($showProcessing)
        <div class="absolute inset-0 z-20 flex items-center justify-center bg-black/85 text-sm text-amber-100 px-4 text-center pointer-events-none">
            {{ __('ui.player.bunny_processing') }}
        </div>
    @endif
    @if($poster)
        <div
            class="absolute inset-0 z-0 bg-cover bg-center opacity-30 pointer-events-none"
            style="background-image:url('{{ e($poster) }}')"
            aria-hidden="true"
        ></div>
    @endif
    <iframe
        src="{{ $embedUrl }}"
        title="{{ e($title) }}"
        loading="lazy"
        class="absolute inset-0 z-10 h-full w-full border-0 pointer-events-auto"
        allow="{{ $allow }}"
        allowfullscreen
        referrerpolicy="strict-origin-when-cross-origin"
    ></iframe>
</div>
