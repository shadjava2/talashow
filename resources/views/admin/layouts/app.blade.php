<!DOCTYPE html>
<html lang="fr" data-theme="dark" data-ts-force-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - Talashow</title>
    <script>
        (function () {
            document.documentElement.setAttribute('data-theme', 'dark');
            document.documentElement.setAttribute('data-ts-force-theme', 'dark');
        })();
    </script>

    @php
        $bunnyStorageCdn = rtrim((string) config('services.bunny_storage.cdn_url'), '/');
        $bunnyStorageHost = $bunnyStorageCdn !== '' ? (parse_url($bunnyStorageCdn, PHP_URL_HOST) ?: '') : '';
        $bunnyStreamHost = config('services.bunny_stream.cdn_hostname');
        $bunnyStreamHost = is_string($bunnyStreamHost) ? trim(preg_replace('#^https?://#i', '', $bunnyStreamHost) ?? '') : '';
    @endphp
    @if($bunnyStorageHost !== '')
    <link rel="dns-prefetch" href="https://{{ $bunnyStorageHost }}">
    <link rel="preconnect" href="https://{{ $bunnyStorageHost }}" crossorigin>
    @endif
    @if($bunnyStreamHost !== '')
    <link rel="dns-prefetch" href="https://{{ $bunnyStreamHost }}">
    <link rel="preconnect" href="https://{{ $bunnyStreamHost }}" crossorigin>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 text-white">
    <div class="min-h-screen grid grid-cols-1 lg:grid-cols-[280px_1fr]">
        <aside class="bg-gray-900 border-r border-gray-800">
            <div class="p-5 border-b border-gray-800 flex items-center gap-2">
                @php
                    $settings = app(\App\Services\SettingsService::class);
                    $siteLogo = $settings->get('site_logo_url') ?: asset('logo.svg');
                @endphp
                <img
                    src="{{ $siteLogo }}"
                    alt="Talashow"
                    class="h-10 w-auto rounded-md shadow-lg shadow-black/20"
                    data-no-skeleton
                    onerror="this.onerror=null; this.src='{{ asset('logo.svg') }}';"
                />
                <div class="text-xs text-gray-400">Dashboard</div>
            </div>

            <nav class="p-3 space-y-1">
                <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-800' : '' }}">
                    Tableau de bord
                </a>
                @if(auth()->user()->hasPermission('series.manage'))
                    <a href="{{ route('admin.series') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.series*') ? 'bg-gray-800' : '' }}">
                        Séries
                    </a>
                @endif
                @if(auth()->user()->hasPermission('genres.manage'))
                    <a href="{{ route('admin.genres.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.genres*') ? 'bg-gray-800' : '' }}">
                        Genres
                    </a>
                @endif
                @if(auth()->user()->hasPermission('users.manage') || auth()->user()->hasRole('admin'))
                    <a href="{{ route('admin.users.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.users*') ? 'bg-gray-800' : '' }}">
                        Utilisateurs
                    </a>
                @endif
                @if(auth()->user()->hasPermission('payments.view'))
                    <a href="{{ route('admin.payments.transactions') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.payments.transactions') ? 'bg-gray-800' : '' }}">
                        Transactions
                    </a>
                    <a href="{{ route('admin.payments.subscriptions') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.payments.subscriptions') ? 'bg-gray-800' : '' }}">
                        Abonnements
                    </a>
                @endif
                @if(auth()->user()->hasPermission('settings.manage'))
                    <a href="{{ route('admin.settings.edit') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.settings*') ? 'bg-gray-800' : '' }}">
                        Paramètres
                    </a>
                    <a href="{{ route('admin.video-languages.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.video-languages*') ? 'bg-gray-800' : '' }}">
                        Langues vidéo
                    </a>
                    <a href="{{ route('admin.newsletter.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.newsletter*') ? 'bg-gray-800' : '' }}">
                        Newsletter
                    </a>
                    <a href="{{ route('admin.mail-templates.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.mail-templates*') ? 'bg-gray-800' : '' }}">
                        Templates Email
                    </a>
                    <a href="{{ route('admin.pages.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.pages*') ? 'bg-gray-800' : '' }}">
                        Pages
                    </a>
                @endif
                @if(auth()->user()->hasPermission('monitoring.view'))
                    <a href="{{ route('admin.monitoring.index') }}" class="block px-3 py-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.monitoring*') ? 'bg-gray-800' : '' }}">
                        Monitoring
                    </a>
                @endif
            </nav>
        </aside>

        <div>
            <header class="sticky top-0 z-40 bg-gray-950/85 backdrop-blur border-b border-gray-800">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm text-gray-300">
                        <button
                            id="ts-admin-back"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-white/90"
                            aria-label="Retour"
                            title="Retour"
                        >
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 18l-6-6 6-6"/>
                            </svg>
                            <span class="hidden sm:inline">Retour</span>
                        </button>
                        <div>
                            @yield('breadcrumb', 'Administration')
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-300">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button class="px-3 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-semibold">
                                Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            </header>
            <script>
                (function () {
                    const btn = document.getElementById('ts-admin-back');
                    if (!btn) return;
                    btn.addEventListener('click', function () {
                        try {
                            if (window.history.length > 1) {
                                window.history.back();
                                return;
                            }
                        } catch (e) {}
                        window.location.href = @json(route('admin.dashboard'));
                    });
                })();
            </script>

            <main>
                {{-- Toasts (admin) --}}
                <div id="toast-root" class="fixed top-4 right-4 z-[60] w-[92vw] max-w-sm space-y-3 pointer-events-none">
                    @if(session('success'))
                        <div class="js-toast pointer-events-auto bg-green-600/90 border border-green-500/40 text-white px-4 py-3 rounded-xl shadow-lg shadow-black/30 backdrop-blur">
                            <div class="flex items-start justify-between gap-3">
                                <div class="text-sm font-semibold">Succès</div>
                                <button type="button" class="js-toast-close text-white/80 hover:text-white transition" aria-label="Fermer">✕</button>
                            </div>
                            <div class="mt-1 text-sm text-white/95">{{ session('success') }}</div>
                        </div>
                    @endif

                    @if(session('error') || $errors->any())
                        <div class="js-toast pointer-events-auto bg-red-600/90 border border-red-500/40 text-white px-4 py-3 rounded-xl shadow-lg shadow-black/30 backdrop-blur">
                            <div class="flex items-start justify-between gap-3">
                                <div class="text-sm font-semibold">Action impossible</div>
                                <button type="button" class="js-toast-close text-white/80 hover:text-white transition" aria-label="Fermer">✕</button>
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

                @yield('content')
            </main>
        </div>
    </div>

    {{-- Scripts spécifiques aux pages admin (upload AJAX, etc.) --}}
    @stack('scripts')
</body>
</html>

