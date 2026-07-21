<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
@php
    $settings = $settings ?? app(\App\Services\SettingsService::class);
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Talashow - Plateforme de streaming vidéo premium">

    <title>@yield('title', 'Talashow') - Talashow</title>

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#0b0b0e" data-ts-theme-color>
    <script>
        (function () {
            try {
                var key = 'talashow-theme';
                var saved = localStorage.getItem(key);
                var theme = (saved === 'light' || saved === 'dark') ? saved : 'dark';
                document.documentElement.setAttribute('data-theme', theme);
                var meta = document.querySelector('meta[data-ts-theme-color]');
                if (meta) meta.setAttribute('content', theme === 'light' ? '#f7f8fb' : '#0b0b0e');
                window.__TALASHOW_THEME__ = theme;
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icons/icon-192.png') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('logo.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
    <meta name="apple-mobile-web-app-title" content="Talashow">
    {{-- PWA icons: PNG requis (Chrome refuse souvent SVG dans le manifest). --}}

    <!-- Fonts: preconnect + load async pour ne pas bloquer le rendu -->
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="preload" href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet"></noscript>

    {{-- CDN médias (Bunny) : pull zone images + stream ; l’épisode ajoute preconnect vers l’origine HLS résolue --}}
    @php
        $bunnyHost = config('services.bunny_stream.cdn_hostname');
        $bunnyHost = is_string($bunnyHost) ? trim(preg_replace('#^https?://#i', '', $bunnyHost) ?? '') : '';
        $bunnyStorageCdn = rtrim((string) config('services.bunny_storage.cdn_url'), '/');
        $bunnyStorageHost = $bunnyStorageCdn !== '' ? (parse_url($bunnyStorageCdn, PHP_URL_HOST) ?: '') : '';
    @endphp
    @if($bunnyStorageHost !== '')
    <link rel="dns-prefetch" href="https://{{ $bunnyStorageHost }}">
    <link rel="preconnect" href="https://{{ $bunnyStorageHost }}" crossorigin>
    @endif
    @if($bunnyHost !== '')
    <link rel="dns-prefetch" href="https://{{ $bunnyHost }}">
    <link rel="preconnect" href="https://{{ $bunnyHost }}" crossorigin>
    @endif

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')

    {{-- Service Worker: en local, on évite le cache “stale”. Pour tester PWA en local, activer manuellement. --}}
    <script>
        (function () {
            // Fuseau horaire Talashow (choisi via backoffice) — utilisé par certains UI (compte à rebours, horloge “chez nous”).
            window.TALASHOW_PLATFORM_TZ = @json(config('app.timezone', 'UTC'));

            // Permet de tester la PWA en dev.
            // - TALASHOW_ENABLE_SW_LOCAL=true => autorise SW sur localhost (sinon on le désactive pour éviter le cache “stale”)
            // - TALASHOW_PWA_DEV_HOSTS=host1,host2 => liste d’hôtes considérés comme “dev” côté front (ex: IP Tailscale)
            @php
                $enableSwLocal = filter_var(env('TALASHOW_ENABLE_SW_LOCAL', false), FILTER_VALIDATE_BOOL);
            @endphp
            window.TALASHOW_ENABLE_SW_LOCAL = @json($enableSwLocal);
            @php
                $talashowPwaDevHosts = array_values(array_filter(array_map('trim', explode(',', (string) env('TALASHOW_PWA_DEV_HOSTS', '')))));
            @endphp
            window.TALASHOW_PWA_DEV_HOSTS = @json($talashowPwaDevHosts);
        })();
    </script>
</head>
    <body class="ts-app-shell font-sans antialiased overflow-x-hidden">
    <x-layout.ambient-bg />
    <div class="ts-app-shell__content relative z-10 isolate">
    <!-- Navigation -->
    <nav class="ts-chrome-nav ts-chrome-nav--top-safe border-b sticky top-0 z-50" data-ts-header>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @php
                $siteLogo = $settings->get('site_logo_url') ?: asset('logo.svg');
            @endphp

            {{-- Mobile header : logo + actions icônes (pas de débordement) --}}
            <div class="ts-mobile-header md:hidden">
                <a href="{{ route('home') }}" class="ts-mobile-header__logo shrink-0">
                    <img
                        src="{{ $siteLogo }}"
                        alt="Talashow"
                        class="h-7 max-h-7 w-auto max-w-[100px] object-contain"
                        data-no-skeleton
                        onerror="this.onerror=null; this.src='{{ asset('logo.svg') }}';"
                    />
                </a>

                <div class="ts-mobile-header__actions">
                    @include('components.theme-switcher', ['compact' => true])
                    @include('components.lang-switcher', ['compact' => true])

                    @auth
                        @if(!auth()->user()->hasActiveSubscription())
                            <a href="{{ route('payment.recharge') }}" class="ts-icon-btn ts-icon-btn--accent" aria-label="{{ __('ui.nav.subscribe') }}" title="{{ __('ui.nav.subscribe') }}">
                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"/>
                                </svg>
                            </a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="ts-icon-btn ts-icon-btn--accent" aria-label="{{ __('ui.nav.login') }}" title="{{ __('ui.nav.login') }}">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4.42 0-8 2-8 4.5V21h16v-2.5C20 16 16.42 14 12 14Z"/>
                            </svg>
                        </a>
                    @endauth

                    <a href="{{ route('payment.recharge') }}" class="ts-icon-btn" aria-label="{{ __('ui.nav.buy_coins') }}" title="{{ __('ui.nav.buy_coins') }}">
                        <svg class="ts-icon-btn__coin" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 2C7.03 2 3 4.24 3 7v10c0 2.76 4.03 5 9 5s9-2.24 9-5V7c0-2.76-4.03-5-9-5Zm0 2c4.06 0 7 .99 7 3s-2.94 3-7 3-7-.99-7-3 2.94-3 7-3Zm0 16c-4.06 0-7-.99-7-3v-2.02C6.53 16.2 9.15 17 12 17s5.47-.8 7-2.02V17c0 2.01-2.94 3-7 3Zm0-5c-4.06 0-7-.99-7-3V9.98C6.53 11.2 9.15 12 12 12s5.47-.8 7-2.02V12c0 2.01-2.94 3-7 3Z"/>
                        </svg>
                    </a>
                </div>
            </div>

            {{-- Desktop header --}}
            <div class="hidden md:flex items-center gap-3 h-16 ts-header-desktop">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0">
                    <img
                        src="{{ $siteLogo }}"
                        alt="Talashow"
                        class="h-9 w-auto rounded-md"
                        data-no-skeleton
                        onerror="this.onerror=null; this.src='{{ asset('logo.svg') }}';"
                    />
                </a>

                <!-- Navigation Links -->
                <nav class="hidden lg:flex items-center gap-1 shrink-0">
                    <a href="{{ route('home') }}" class="ts-nav-link {{ request()->routeIs('home') ? 'is-active' : '' }}">{{ __('ui.nav.home') }}</a>
                    <a href="{{ route('browse') }}" class="ts-nav-link {{ request()->routeIs('browse') ? 'is-active' : '' }}">{{ __('ui.nav.genre') }}</a>
                    <a href="{{ route('application') }}" class="ts-nav-link {{ request()->routeIs('application') ? 'is-active' : '' }}">{{ __('ui.nav.application') }}</a>
                </nav>

                <!-- Search -->
                <form method="GET" action="{{ route('browse') }}" class="ts-search relative flex-1 max-w-xs mx-auto hidden md:block">
                    <svg class="ts-search__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="{{ __('ui.nav.search') }}"
                        autocomplete="off"
                        class="ts-search-input ts-search__field"
                    >
                    @if(request('genre') && request('genre') !== 'all')
                        <input type="hidden" name="genre" value="{{ request('genre') }}">
                    @endif
                </form>

                <!-- Actions -->
                <div class="ts-header-tools flex items-center gap-1.5 shrink-0 ml-auto">
                    @auth
                        @if(auth()->user()->hasActiveSubscription())
                            <a href="{{ route('payment.recharge') }}" class="ts-header-btn ts-header-btn--soft">
                                {{ __('ui.nav.subscriber') }}
                            </a>
                        @else
                            <a href="{{ route('payment.recharge') }}" class="ts-header-btn ts-header-btn--primary">
                                {{ __('ui.nav.subscribe') }}
                            </a>
                        @endif

                        <a href="{{ route('payment.recharge') }}" class="ts-header-btn ts-header-btn--ghost" title="{{ __('ui.nav.buy_coins') }}">
                            <svg class="ts-header-btn__icon ts-header-btn__icon--coin" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 2C7.03 2 3 4.24 3 7v10c0 2.76 4.03 5 9 5s9-2.24 9-5V7c0-2.76-4.03-5-9-5Zm0 2c4.06 0 7 .99 7 3s-2.94 3-7 3-7-.99-7-3 2.94-3 7-3Zm0 16c-4.06 0-7-.99-7-3v-2.02C6.53 16.2 9.15 17 12 17s5.47-.8 7-2.02V17c0 2.01-2.94 3-7 3Zm0-5c-4.06 0-7-.99-7-3V9.98C6.53 11.2 9.15 12 12 12s5.47-.8 7-2.02V12c0 2.01-2.94 3-7 3Z"/>
                            </svg>
                            <span class="hidden xl:inline">{{ __('ui.nav.buy_coins') }}</span>
                        </a>
                        <a href="{{ route('payment.donation') }}" class="ts-header-btn ts-header-btn--ghost" title="{{ __('ui.nav.donation') }}">
                            <svg class="ts-header-btn__icon ts-header-btn__icon--gift" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M20 7h-2.18A3 3 0 0 0 12 4.18 3 3 0 0 0 6.18 7H4a2 2 0 0 0-2 2v2a1 1 0 0 0 1 1h1v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-9h1a1 1 0 0 0 1-1V9a2 2 0 0 0-2-2Zm-8-1.5A1.5 1.5 0 0 1 13.5 7H12V5.5ZM10.5 7A1.5 1.5 0 0 1 12 5.5V7h-1.5ZM4 9h16v2H4V9Zm2 4h6v8H6v-8Zm8 0h4v8h-4v-8Z"/>
                            </svg>
                            <span class="hidden xl:inline">{{ __('ui.nav.donation') }}</span>
                        </a>
                        <a href="{{ route('payment.recharge') }}" class="ts-header-coins">
                            <span class="ts-header-coins__n">{{ auth()->user()->total_coins }}</span>
                        </a>
                        <a href="{{ route('profile') }}" class="ts-header-avatar" title="{{ __('ui.nav.profile') }}">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="ts-header-btn ts-header-btn--primary">
                            {{ __('ui.nav.login') }}
                        </a>
                        <a href="{{ route('payment.recharge') }}" class="ts-header-btn ts-header-btn--ghost hidden xl:inline-flex">
                            {{ __('ui.nav.subscribe') }}
                        </a>
                        <a href="{{ route('payment.recharge') }}" class="ts-header-btn ts-header-btn--ghost" title="{{ __('ui.nav.buy_coins') }}">
                            <svg class="ts-header-btn__icon ts-header-btn__icon--coin" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 2C7.03 2 3 4.24 3 7v10c0 2.76 4.03 5 9 5s9-2.24 9-5V7c0-2.76-4.03-5-9-5Zm0 2c4.06 0 7 .99 7 3s-2.94 3-7 3-7-.99-7-3 2.94-3 7-3Zm0 16c-4.06 0-7-.99-7-3v-2.02C6.53 16.2 9.15 17 12 17s5.47-.8 7-2.02V17c0 2.01-2.94 3-7 3Zm0-5c-4.06 0-7-.99-7-3V9.98C6.53 11.2 9.15 12 12 12s5.47-.8 7-2.02V12c0 2.01-2.94 3-7 3Z"/>
                            </svg>
                            <span class="hidden xl:inline">{{ __('ui.nav.buy_coins') }}</span>
                        </a>
                        <a href="{{ route('payment.donation') }}" class="ts-header-btn ts-header-btn--ghost" title="{{ __('ui.nav.donation') }}">
                            <svg class="ts-header-btn__icon ts-header-btn__icon--gift" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M20 7h-2.18A3 3 0 0 0 12 4.18 3 3 0 0 0 6.18 7H4a2 2 0 0 0-2 2v2a1 1 0 0 0 1 1h1v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-9h1a1 1 0 0 0 1-1V9a2 2 0 0 0-2-2Zm-8-1.5A1.5 1.5 0 0 1 13.5 7H12V5.5ZM10.5 7A1.5 1.5 0 0 1 12 5.5V7h-1.5ZM4 9h16v2H4V9Zm2 4h6v8H6v-8Zm8 0h4v8h-4v-8Z"/>
                            </svg>
                            <span class="hidden xl:inline">{{ __('ui.nav.donation') }}</span>
                        </a>
                    @endauth

                    @include('components.theme-switcher', ['compact' => true])
                    @include('components.lang-switcher')
                </div>
            </div>
        </div>
    </nav>

    {{-- Bouton Retour (global) --}}
    <button
        id="ts-back-button"
        type="button"
        class="fixed left-4 z-[95] bottom-[calc(5.25rem+env(safe-area-inset-bottom))] md:bottom-6 hidden items-center gap-2 px-3 py-2 rounded-full bg-black/45 hover:bg-black/60 border border-white/10 text-white text-sm font-semibold backdrop-blur shadow-lg shadow-black/30"
        aria-label="{{ __('ui.common.back') }}"
        title="{{ __('ui.common.back') }}"
    >
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 18l-6-6 6-6"/>
        </svg>
        <span class="hidden sm:inline">{{ __('ui.common.back') }}</span>
    </button>
    <script>
        (function () {
            const btn = document.getElementById('ts-back-button');
            if (!btn) return;
            const isHome = (window.location.pathname === '/' || window.location.pathname === @json(parse_url(route('home'), PHP_URL_PATH) ?: '/'));
            function canGoBack() {
                try {
                    if (window.history.length > 1) return true;
                    if (document.referrer) {
                        const r = new URL(document.referrer);
                        return r.origin === window.location.origin;
                    }
                } catch (e) {}
                return false;
            }
            if (!isHome && canGoBack()) {
                btn.classList.remove('hidden');
                btn.classList.add('flex');
            }
            btn.addEventListener('click', function () {
                try {
                    if (isHome) return;
                    if (canGoBack()) {
                        window.history.back();
                        return;
                    }
                } catch (e) {}
                window.location.href = @json(route('home'));
            });
        })();
    </script>

    <!-- Main Content -->
    <main class="ts-main-content pb-[calc(6rem+env(safe-area-inset-bottom))] md:pb-0 relative z-10">
        {{-- Toasts (pro) --}}
        <div id="toast-root" class="fixed top-4 right-4 z-[60] w-auto max-w-sm space-y-3 pointer-events-none" aria-live="polite">
            @if(session('success'))
                <div class="js-toast pointer-events-auto bg-green-600/90 border border-green-500/40 text-white px-4 py-3 rounded-xl shadow-lg shadow-black/30 backdrop-blur">
                    <div class="flex items-start justify-between gap-3">
                        <div class="text-sm font-semibold">{{ __('ui.common.success') }}</div>
                        <button type="button" class="js-toast-close text-white/80 hover:text-white transition" aria-label="{{ __('ui.common.close') }}">✕</button>
                    </div>
                    <div class="mt-1 text-sm text-white/95">{{ session('success') }}</div>
                </div>
            @endif

            @if(session('error') || $errors->any())
                <div class="js-toast pointer-events-auto bg-red-600/90 border border-red-500/40 text-white px-4 py-3 rounded-xl shadow-lg shadow-black/30 backdrop-blur">
                    <div class="flex items-start justify-between gap-3">
                        <div class="text-sm font-semibold">{{ __('ui.common.action_impossible') }}</div>
                        <button type="button" class="js-toast-close text-white/80 hover:text-white transition" aria-label="{{ __('ui.common.close') }}">✕</button>
                    </div>
                    <div class="mt-1 text-sm text-white/95">
                        @if(session('error'))
                            <div>{{ session('error') }}</div>
                        @endif
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Modal confirm (style bootstrap, UX pro) --}}
        <div id="ts-modal" class="fixed inset-0 z-[70] hidden" aria-hidden="true">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
            <div class="relative w-full h-full flex items-center justify-center p-4">
                <div class="w-full max-w-lg ts-panel overflow-hidden">
                    <div class="ts-panel__head">
                        <div id="ts-modal-title" class="ts-panel__title">{{ __('ui.common.confirmation') }}</div>
                        <button id="ts-modal-close" type="button" class="ts-panel__close" aria-label="{{ __('ui.common.close') }}">✕</button>
                    </div>
                    <div class="ts-panel__body">
                        <div id="ts-modal-message"></div>
                    </div>
                    <div class="ts-panel__foot">
                        <button id="ts-modal-cancel" type="button" class="ts-btn-soft">
                            {{ __('ui.common.cancel') }}
                        </button>
                        <button id="ts-modal-confirm" type="button" class="ts-btn-primary">
                            {{ __('ui.common.confirm') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Newsletter modal (auto invite) --}}
        @php
            $newsletterInviteEligible = false;
            $newsletterPrefillEmail = null;
            try {
                if (auth()->check() && method_exists(auth()->user(), 'hasActiveSubscription') && !auth()->user()->hasActiveSubscription()) {
                    $newsletterPrefillEmail = (string) (auth()->user()->email ?? '');
                    $emailLower = strtolower(trim($newsletterPrefillEmail));
                    $alreadyConfirmed = false;
                    if ($emailLower !== '') {
                        $alreadyConfirmed = \App\Models\NewsletterSubscriber::query()
                            ->where('email', $emailLower)
                            ->whereNotNull('confirmed_at')
                            ->whereNull('unsubscribed_at')
                            ->exists();
                    }
                    $newsletterInviteEligible = !$alreadyConfirmed;
                }
            } catch (\Throwable) {
                // no-op (ne jamais casser le rendu)
            }
        @endphp
        <script>
            window.TALASHOW_NEWSLETTER_INVITE_ELIGIBLE = {{ $newsletterInviteEligible ? 'true' : 'false' }};
            window.TALASHOW_NEWSLETTER_PREFILL_EMAIL = @json($newsletterPrefillEmail);
        </script>

        <div id="ts-newsletter-modal" class="fixed inset-0 z-[80] hidden" aria-hidden="true">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
            <div class="relative w-full h-full flex items-center justify-center p-4">
                <div class="w-full max-w-md ts-panel overflow-hidden">
                    <div class="ts-panel__head">
                        <div class="ts-panel__title">{{ __('ui.newsletter.title') }}</div>
                        <button id="ts-newsletter-close" type="button" class="ts-panel__close" aria-label="{{ __('ui.common.close') }}">✕</button>
                    </div>
                    <form id="ts-newsletter-form" class="ts-panel__body space-y-4" data-ts-validate="form" novalidate>
                        @csrf
                        <div>
                            <label for="ts-newsletter-email" class="block text-sm mb-2" style="color: var(--ts-text-primary)">{{ __('ui.common.email') }}</label>
                            <input
                                id="ts-newsletter-email"
                                name="email"
                                type="email"
                                required
                                autocomplete="email"
                                class="ts-input"
                                placeholder="{{ __('ui.newsletter.email_placeholder') }}"
                            />
                            <p class="text-xs mt-2" style="color: var(--ts-text-muted)">
                                {{ __('ui.newsletter.subtitle') }}
                            </p>
                        </div>
                        <input type="hidden" name="source" value="nonsubscriber_popup" />

                        <div class="flex items-center justify-end gap-2 flex-wrap">
                            <button type="button" id="ts-newsletter-cancel" class="ts-btn-soft">
                                {{ __('ui.common.later') }}
                            </button>
                            <button type="button" id="ts-newsletter-resend" class="ts-btn-soft">
                                {{ __('ui.newsletter.resend') }}
                            </button>
                            <button type="submit" class="ts-btn-primary">
                                {{ __('ui.newsletter.subscribe') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @yield('content')
    </main>

    {{-- Bottom Navigation mobile : Accueil / Genre / Rechercher / Achat / Compte --}}
    @php
        $isBrowse = request()->routeIs('browse');
        $isSearchTab = $isBrowse && (request()->filled('search') || request('tab') === 'search');
        $isGenreTab = $isBrowse && ! $isSearchTab;
    @endphp
    <nav class="ts-bottom-nav md:hidden" aria-label="Navigation">
        <div class="ts-bottom-nav__inner">
            <a href="{{ route('home') }}"
               class="ts-bottom-nav__item {{ request()->routeIs('home') ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3l9 8h-3v10h-5v-6H11v6H6V11H3l9-8z"/></svg>
                <span>{{ __('ui.nav.home') }}</span>
            </a>

            <a href="{{ route('browse') }}"
               class="ts-bottom-nav__item {{ $isGenreTab ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z"/></svg>
                <span>{{ __('ui.nav.genre') }}</span>
            </a>

            <a href="{{ route('browse', ['tab' => 'search']) }}"
               class="ts-bottom-nav__item {{ $isSearchTab ? 'is-active' : '' }}"
               aria-label="{{ __('ui.nav.search') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span>{{ __('ui.nav.search') }}</span>
            </a>

            <a href="{{ route('payment.recharge') }}"
               class="ts-bottom-nav__item {{ request()->routeIs('payment.*') ? 'is-active' : '' }}">
                <span class="ts-bottom-nav__icon-wrap">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 2C7.03 2 3 4.24 3 7v10c0 2.76 4.03 5 9 5s9-2.24 9-5V7c0-2.76-4.03-5-9-5Zm0 2c4.06 0 7 .99 7 3s-2.94 3-7 3-7-.99-7-3 2.94-3 7-3Zm0 16c-4.06 0-7-.99-7-3v-2.02C6.53 16.2 9.15 17 12 17s5.47-.8 7-2.02V17c0 2.01-2.94 3-7 3Z"/>
                    </svg>
                    @auth
                        <span class="ts-bottom-nav__badge">{{ min(99, (int) auth()->user()->total_coins) }}</span>
                    @endauth
                </span>
                <span>{{ __('ui.nav.buy_coins') }}</span>
            </a>

            @auth
                <a href="{{ route('profile') }}"
                   class="ts-bottom-nav__item {{ request()->routeIs('profile') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4.42 0-8 2-8 4.5V21h16v-2.5C20 16 16.42 14 12 14Z"/>
                    </svg>
                    <span>{{ __('ui.nav.account') }}</span>
                </a>
            @else
                <a href="{{ route('login') }}"
                   class="ts-bottom-nav__item {{ request()->routeIs('login') || request()->routeIs('register') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4.42 0-8 2-8 4.5V21h16v-2.5C20 16 16.42 14 12 14Z"/>
                    </svg>
                    <span>{{ __('ui.nav.account') }}</span>
                </a>
            @endauth
        </div>
    </nav>

    <!-- Footer -->
    <footer class="ts-chrome-footer border-t mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4" style="color: var(--ts-text-primary)">{{ __('ui.footer.about_title') }}</h3>
                    <ul class="space-y-2 text-sm ts-footer-link-list">
                        <li><a href="{{ route('legal.terms') }}" class="ts-footer-link">{{ __('ui.footer.terms') }}</a></li>
                        <li><a href="{{ route('legal.privacy') }}" class="ts-footer-link">{{ __('ui.footer.privacy') }}</a></li>
                        <li><a href="{{ route('legal.cookies') }}" class="ts-footer-link">{{ __('ui.footer.cookies') }}</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4" style="color: var(--ts-text-primary)">{{ __('ui.footer.write_us') }}</h3>
                    @php
                        $contactEmail = $settings->get('footer_contact_email');
                        $businessLabel = $settings->get('footer_business_label', "Coopération d'affaires");
                        $businessUrl = $settings->get('footer_business_url');
                    @endphp
                    @if($contactEmail)
                        <a href="mailto:{{ $contactEmail }}" class="text-sm ts-footer-link">{{ $contactEmail }}</a>
                    @else
                        <span class="text-sm ts-text-muted">{{ __('ui.footer.email_missing') }}</span>
                    @endif
                    @if($businessUrl)
                        <a href="{{ $businessUrl }}" target="_blank" rel="noopener" class="mt-2 inline-block text-sm ts-footer-link">{{ $businessLabel ?: "Coopération d'affaires" }}</a>
                    @endif
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4" style="color: var(--ts-text-primary)">{{ __('ui.footer.contact') }}</h3>
                    @php
                        $phone = $settings->get('footer_phone');
                        $tel = $phone ? preg_replace('/\s+/', '', $phone) : null;
                    @endphp
                    @if($phone)
                        <a href="tel:{{ $tel }}" class="text-sm ts-footer-link">{{ $phone }}</a>
                    @else
                        <span class="text-sm ts-text-muted">{{ __('ui.footer.phone_missing') }}</span>
                    @endif
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4" style="color: var(--ts-text-primary)">{{ __('ui.footer.community') }}</h3>
                    @php
                        $fb = $settings->get('social_facebook_url');
                        $yt = $settings->get('social_youtube_url');
                        $tt = $settings->get('social_tiktok_url');
                    @endphp

                    <div class="flex flex-wrap gap-x-4 gap-y-2">
                        @if($fb)
                            <a href="{{ $fb }}" target="_blank" rel="noopener" class="ts-footer-link">Facebook</a>
                        @endif
                        @if($yt)
                            <a href="{{ $yt }}" target="_blank" rel="noopener" class="ts-footer-link">Youtube</a>
                        @endif
                        @if($tt)
                            <a href="{{ $tt }}" target="_blank" rel="noopener" class="ts-footer-link">Tiktok</a>
                        @endif

                        @if(!$fb && !$yt && !$tt)
                            <span class="text-sm ts-text-muted">{{ __('ui.footer.links_missing') }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="ts-footer-legal mt-8 pt-8 text-center text-sm">
                <span>{{ __('ui.footer.copyright') }}</span>
                <span class="mx-2">·</span>
                <a href="https://nexus.cosoft.app/" target="_blank" rel="noopener noreferrer"
                   class="ts-footer-link ts-footer-link--strong underline underline-offset-4">
                    {{ __('ui.footer.developed_by') }}
                </a>
            </div>
        </div>
    </footer>

    <x-layout.tawk :settings="$settings" />

    @stack('scripts')
    </div>
</body>
</html>
