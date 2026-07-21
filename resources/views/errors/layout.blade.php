<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#dc2626">

    <title>@yield('title', 'Erreur') - Talashow</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 text-white font-sans antialiased min-h-screen">
    <main class="min-h-screen flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-xl text-center">
            @php
                $settings = app(\App\Services\SettingsService::class);
                $siteLogo = $settings->get('site_logo_url') ?: asset('logo.svg');
            @endphp

            <div class="flex justify-center mb-6">
                <img
                    src="{{ $siteLogo }}"
                    alt="Talashow"
                    class="h-14 w-auto rounded-md shadow-lg shadow-black/20"
                    onerror="this.onerror=null; this.src='{{ asset('logo.svg') }}';"
                />
            </div>

            <div class="bg-gray-800/60 border border-gray-700/70 rounded-2xl p-8">
                <div class="text-sm font-semibold text-red-300 mb-2">@yield('code')</div>
                <h1 class="text-2xl md:text-3xl font-extrabold">@yield('headline')</h1>
                <p class="text-gray-300 mt-3 leading-relaxed">@yield('message')</p>

                <div class="mt-7 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="{{ route('home') }}"
                       class="px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">
                        Retour à l’accueil
                    </a>
                    <button type="button"
                            onclick="history.length > 1 ? history.back() : window.location='{{ route('home') }}';"
                            class="px-6 py-3 bg-white/10 hover:bg-white/20 rounded-lg font-semibold transition">
                        Page précédente
                    </button>
                </div>

                <p class="text-xs text-gray-400 mt-6">
                    Si le problème persiste, réessayez plus tard.
                </p>
            </div>
        </div>
    </main>
</body>
</html>

