<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion Admin - Talashow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 text-white min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <div class="mb-6 text-center">
            @php
                $settings = app(\App\Services\SettingsService::class);
                $siteLogo = $settings->get('site_logo_url') ?: asset('logo.svg');
            @endphp
            <img
                src="{{ $siteLogo }}"
                alt="Talashow"
                class="mx-auto h-12 w-auto rounded-md shadow-lg shadow-black/20 mb-4"
                onerror="this.onerror=null; this.src='{{ asset('logo.svg') }}';"
            />
            <h1 class="text-2xl font-extrabold">Talashow Admin</h1>
            <p class="text-sm text-gray-400 mt-1">Accès réservé à l’équipe de gestion</p>
        </div>

        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <form method="POST" action="{{ route('admin.login.post') }}" class="space-y-4" data-ts-validate="form" novalidate>
                @csrf

                <div>
                    <label class="block text-sm mb-2">Email</label>
                    <input name="email" type="email" required value="{{ old('email') }}"
                           class="w-full px-4 py-3 bg-gray-950 border border-gray-800 rounded-lg focus:outline-none focus:border-red-600" />
                </div>

                <div>
                    <label class="block text-sm mb-2">Mot de passe</label>
                    <div class="relative">
                        <input id="admin_password" name="password" type="password" required
                               class="w-full px-4 py-3 pr-12 bg-gray-950 border border-gray-800 rounded-lg focus:outline-none focus:border-red-600" />
                        <button
                            type="button"
                            data-toggle-password="admin_password"
                            class="absolute right-2 top-1/2 -translate-y-1/2 p-2 rounded-lg hover:bg-white/10 transition text-gray-300"
                            aria-label="Afficher/Masquer le mot de passe"
                            aria-pressed="false"
                        >
                            <svg data-icon="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <svg data-icon="hide" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.269-2.943-9.543-7a9.956 9.956 0 012.108-3.592M9.88 9.88A3 3 0 0114.12 14.12M6.228 6.228A9.956 9.956 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.963 9.963 0 01-4.132 5.411M3 3l18 18"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-300">
                    <input type="checkbox" name="remember" class="rounded">
                    Se souvenir de moi
                </label>

                <button class="w-full py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">
                    Se connecter
                </button>
            </form>

            <p class="text-xs text-gray-500 mt-4">
                URL d’accès admin : <span class="text-gray-300 font-semibold">/talashow-admin</span>
            </p>
        </div>
    </div>
</body>
</html>

