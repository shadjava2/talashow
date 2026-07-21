@extends('layouts.app')

@section('title', 'Vérification OTP')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-6">
        <div class="text-center">
            @php
                $settings = app(\App\Services\SettingsService::class);
                $siteLogo = $settings->get('site_logo_url') ?: asset('logo.svg');
            @endphp
            <img src="{{ $siteLogo }}" alt="Talashow" class="mx-auto h-12 w-auto rounded-md shadow-lg shadow-black/20" onerror="this.onerror=null; this.src='{{ asset('logo.svg') }}';" />
            <h2 class="mt-6 text-3xl font-extrabold">Vérification</h2>
            <p class="mt-2 text-sm text-gray-400">
                Un code OTP a été envoyé à <span class="text-gray-200 font-semibold">{{ $emailMasked }}</span>.
            </p>
        </div>

        <form method="POST" action="{{ route('otp.verify') }}" class="space-y-4" data-ts-validate="form" novalidate>
            @csrf
            <div>
                <label for="otp" class="block text-sm font-medium mb-2">Code OTP</label>
                <input id="otp" name="otp" inputmode="numeric" autocomplete="one-time-code" maxlength="6" minlength="6" pattern="[0-9]{6}" required
                       class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:border-red-600 tracking-widest text-center text-lg"
                       placeholder="••••••" value="{{ old('otp') }}">
                <p class="text-xs text-gray-400 mt-2">Saisissez le code à 6 chiffres. Il expire dans 10 minutes.</p>
            </div>

            <button type="submit"
                    class="w-full flex justify-center py-3 px-4 rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition">
                Valider et créer mon compte
            </button>
        </form>

        <div class="flex items-center justify-between text-sm">
            <form method="POST" action="{{ route('otp.resend') }}" data-ts-validate="form" novalidate>
                @csrf
                <button type="submit" class="text-gray-300 hover:text-white transition">
                    Renvoyer le code
                </button>
            </form>
            <a href="{{ route('register') }}" class="text-red-500 hover:text-red-400 transition">
                Modifier mon email
            </a>
        </div>
    </div>
</div>
@endsection

