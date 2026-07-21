@php
    $compact = $compact ?? false;
@endphp

<button
    type="button"
    class="ts-theme-toggle {{ $compact ? 'ts-theme-toggle--compact' : '' }}"
    data-ts-theme-toggle
    data-label-dark="{{ __('ui.nav.theme_dark') }}"
    data-label-light="{{ __('ui.nav.theme_light') }}"
    aria-label="{{ __('ui.nav.theme') }}"
    title="{{ __('ui.nav.theme') }}"
>
    <svg class="ts-theme-toggle__icon ts-theme-toggle__icon--moon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M21.75 15.5A9.25 9.25 0 0 1 8.5 2.25 9.5 9.5 0 1 0 21.75 15.5Z"/>
    </svg>
    <svg class="ts-theme-toggle__icon ts-theme-toggle__icon--sun" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M12 18a6 6 0 1 0 0-12 6 6 0 0 0 0 12Zm0-16.5a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0V2.25A.75.75 0 0 1 12 1.5Zm0 16.5a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5a.75.75 0 0 1 .75-.75ZM3.75 12a.75.75 0 0 1 .75-.75h1.5a.75.75 0 0 1 0 1.5H4.5A.75.75 0 0 1 3.75 12Zm14.25 0a.75.75 0 0 1 .75-.75h1.5a.75.75 0 0 1 0 1.5H18.75a.75.75 0 0 1-.75-.75ZM5.47 5.47a.75.75 0 0 1 1.06 0l1.06 1.06a.75.75 0 1 1-1.06 1.06L5.47 6.53a.75.75 0 0 1 0-1.06Zm11 11a.75.75 0 0 1 1.06 0l1.06 1.06a.75.75 0 1 1-1.06 1.06l-1.06-1.06a.75.75 0 0 1 0-1.06Zm1.06-11a.75.75 0 0 1 0 1.06l-1.06 1.06a.75.75 0 1 1-1.06-1.06l1.06-1.06a.75.75 0 0 1 1.06 0ZM6.53 16.47a.75.75 0 0 1 0 1.06L5.47 18.6a.75.75 0 1 1-1.06-1.06l1.06-1.06a.75.75 0 0 1 1.06 0Z"/>
    </svg>
    @unless($compact)
        <span class="hidden lg:inline text-[11px] font-semibold uppercase tracking-wide" data-ts-theme-label>
            {{ __('ui.nav.theme_dark') }}
        </span>
    @endunless
</button>
