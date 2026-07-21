@extends('layouts.app')

@section('title', __('ui.application.title'))

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
    <div class="bg-gradient-to-br from-white/10 to-white/5 border border-white/10 rounded-2xl p-8 md:p-12 overflow-hidden relative">
        <div class="absolute inset-0 pointer-events-none opacity-40"
             style="background: radial-gradient(900px 400px at 80% 10%, rgba(229,9,20,.35), rgba(229,9,20,0) 60%);">
        </div>

        <div class="relative">
            <div class="flex items-center gap-3 mb-6">
                @php
                    $settings = app(\App\Services\SettingsService::class);
                    $siteLogo = $settings->get('site_logo_url') ?: asset('logo.svg');
                @endphp
                <img src="{{ $siteLogo }}" alt="Talashow" class="h-10 w-auto rounded-md shadow-lg shadow-black/20" onerror="this.onerror=null; this.src='{{ asset('logo.svg') }}';">
                <span class="px-3 py-1 rounded-full bg-red-600/20 text-red-200 text-xs font-semibold border border-red-600/30">
                    {{ __('ui.application.badge_soon') }}
                </span>
            </div>

            <h1 class="text-4xl md:text-5xl font-black tracking-tight mb-4">
                {{ __('ui.application.heading') }}
            </h1>
            <p class="text-gray-300 text-lg max-w-2xl mb-8">
                {!! __('ui.application.subtitle_html') !!}
            </p>

            <div class="flex flex-wrap gap-3">
                <button type="button" class="px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition" data-newsletter-open>
                    {{ __('ui.application.notify_me') }}
                </button>
                <a href="{{ route('home') }}" class="px-6 py-3 bg-white/10 hover:bg-white/20 rounded-lg font-semibold transition">
                    {{ __('ui.application.back_home') }}
                </a>
            </div>

            <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-black/20 border border-white/10 rounded-xl p-4">
                    <div class="text-sm font-semibold mb-1">{{ __('ui.application.feature_1_title') }}</div>
                    <div class="text-sm text-gray-400">{{ __('ui.application.feature_1_desc') }}</div>
                </div>
                <div class="bg-black/20 border border-white/10 rounded-xl p-4">
                    <div class="text-sm font-semibold mb-1">{{ __('ui.application.feature_2_title') }}</div>
                    <div class="text-sm text-gray-400">{{ __('ui.application.feature_2_desc') }}</div>
                </div>
                <div class="bg-black/20 border border-white/10 rounded-xl p-4">
                    <div class="text-sm font-semibold mb-1">PWA</div>
                    <div class="text-sm text-gray-400">{{ __('ui.application.feature_3_desc') }}</div>
                </div>
            </div>

            {{-- Newsletter modal est désormais global dans layouts/app.blade.php --}}
        </div>
    </div>
</div>
@endsection

