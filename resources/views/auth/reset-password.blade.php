@extends('layouts.app')

@section('title', 'Réinitialiser le mot de passe')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-6">
        <div class="text-center">
            @php
                $settings = app(\App\Services\SettingsService::class);
                $siteLogo = $settings->get('site_logo_url') ?: asset('logo.svg');
            @endphp
            <img src="{{ $siteLogo }}" alt="Talashow" class="mx-auto h-12 w-auto rounded-md shadow-lg shadow-black/20" onerror="this.onerror=null; this.src='{{ asset('logo.svg') }}';" />
            <h2 class="mt-6 text-3xl font-extrabold">Nouveau mot de passe</h2>
            <p class="mt-2 text-sm text-gray-400">Choisissez un nouveau mot de passe sécurisé.</p>
        </div>

        @if ($errors->any())
            <div class="bg-red-600/10 border border-red-600/30 text-red-200 rounded-lg px-4 py-3 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4" data-ts-validate="form" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="block text-sm font-medium mb-2">Email</label>
                <input id="email" name="email" type="email" required autocomplete="email"
                       class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-red-600"
                       value="{{ old('email', $email) }}">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium mb-2">Mot de passe</label>
                <div class="relative">
                    <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"
                           class="w-full px-4 py-3 pr-12 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-red-600">
                    <button type="button" data-toggle-password="password"
                            class="absolute right-2 top-1/2 -translate-y-1/2 p-2 rounded-lg hover:bg-white/10 transition text-gray-300"
                            aria-label="Afficher/Masquer le mot de passe" aria-pressed="false">
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

            <div>
                <label for="password_confirmation" class="block text-sm font-medium mb-2">Confirmer le mot de passe</label>
                <div class="relative">
                    <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" data-ts-match="#password"
                           class="w-full px-4 py-3 pr-12 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-red-600">
                    <button type="button" data-toggle-password="password_confirmation"
                            class="absolute right-2 top-1/2 -translate-y-1/2 p-2 rounded-lg hover:bg-white/10 transition text-gray-300"
                            aria-label="Afficher/Masquer le mot de passe" aria-pressed="false">
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

            <button type="submit"
                    class="w-full flex justify-center py-3 px-4 rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition">
                Mettre à jour le mot de passe
            </button>
        </form>
    </div>
</div>
@endsection

