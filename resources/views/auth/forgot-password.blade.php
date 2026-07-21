@extends('layouts.app')

@section('title', 'Mot de passe oublié')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-6">
        <div class="text-center">
            @php
                $settings = app(\App\Services\SettingsService::class);
                $siteLogo = $settings->get('site_logo_url') ?: asset('logo.svg');
            @endphp
            <img src="{{ $siteLogo }}" alt="Talashow" class="mx-auto h-12 w-auto rounded-md shadow-lg shadow-black/20" onerror="this.onerror=null; this.src='{{ asset('logo.svg') }}';" />
            <h2 class="mt-6 text-3xl font-extrabold">Mot de passe oublié</h2>
            <p class="mt-2 text-sm text-gray-400">
                Entrez votre email et nous vous enverrons un lien de réinitialisation.
            </p>
        </div>

        @if (session('status'))
            <div class="bg-green-600/10 border border-green-600/30 text-green-200 rounded-lg px-4 py-3 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-600/10 border border-red-600/30 text-red-200 rounded-lg px-4 py-3 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4" data-ts-validate="form" novalidate>
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium mb-2">Email</label>
                <input id="email" name="email" type="email" required autocomplete="email"
                       class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-red-600"
                       value="{{ old('email') }}">
            </div>

            <button type="submit"
                    class="w-full flex justify-center py-3 px-4 rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition">
                Envoyer le lien
            </button>

            <div class="text-center text-sm text-gray-400">
                <a href="{{ route('login') }}" class="text-red-500 hover:text-red-400">Retour à la connexion</a>
            </div>
        </form>
    </div>
</div>
@endsection

