@php
    $loc = app()->getLocale();
    $current = strtoupper($loc === 'en' ? 'EN' : 'FR');
    $compact = $compact ?? false;
@endphp

<details class="relative {{ $compact ? 'ts-lang-switcher--compact' : '' }}">
    <summary
        class="list-none cursor-pointer {{ $compact ? 'ts-icon-btn' : 'ts-theme-toggle' }}"
        aria-label="{{ __('ui.nav.language') }} ({{ $current }})"
        title="{{ __('ui.nav.language') }}"
    >
        <svg class="{{ $compact ? '' : 'w-4 h-4' }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" @if($compact) style="width:1.15rem;height:1.15rem" @endif>
            <path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2Zm7.93 9h-3.17a15.7 15.7 0 0 0-1.15-5.01A8.02 8.02 0 0 1 19.93 11ZM12 4c.92 0 2.55 2.12 3.32 7H8.68C9.45 6.12 11.08 4 12 4ZM4.07 13h3.17c.2 1.78.62 3.53 1.25 5.04A8.02 8.02 0 0 1 4.07 13Zm3.17-2H4.07a8.02 8.02 0 0 1 4.26-5.02A15.7 15.7 0 0 0 7.24 11Zm1.44 2h6.64c-.78 4.88-2.4 7-3.32 7-.92 0-2.55-2.12-3.32-7Zm7.83 5.04c.63-1.51 1.05-3.26 1.25-5.04h3.17a8.02 8.02 0 0 1-4.42 5.04Z"/>
        </svg>
        @unless($compact)
            <span class="uppercase text-[11px] tracking-wide font-semibold">{{ $current }}</span>
            <svg class="w-4 h-4 opacity-70" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd"/>
            </svg>
        @endunless
    </summary>
    <div class="absolute right-0 mt-2 z-[80] min-w-[10rem] ts-panel rounded-xl p-2">
        <div class="text-[11px] ts-text-muted px-2 pb-2">{{ __('ui.nav.language') }}</div>
        <div class="flex flex-col gap-2 px-2 pb-1">
            <a href="{{ route('lang.switch', 'fr') }}"
               onclick="this.closest('details')?.removeAttribute('open')"
               class="w-full text-center px-3 py-2 rounded-lg text-sm font-semibold {{ $loc === 'fr' ? 'bg-red-600 text-white' : 'ts-header-btn ts-header-btn--ghost' }}">
                FR
            </a>
            <a href="{{ route('lang.switch', 'en') }}"
               onclick="this.closest('details')?.removeAttribute('open')"
               class="w-full text-center px-3 py-2 rounded-lg text-sm font-semibold {{ $loc === 'en' ? 'bg-red-600 text-white' : 'ts-header-btn ts-header-btn--ghost' }}">
                EN
            </a>
        </div>
    </div>
</details>
