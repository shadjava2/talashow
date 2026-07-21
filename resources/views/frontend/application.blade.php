@extends('layouts.app')

@section('title', __('ui.application.title'))

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
    <div class="ts-surface relative overflow-hidden rounded-2xl p-8 md:p-12">
        <div class="absolute inset-0 pointer-events-none opacity-50"
             style="background: radial-gradient(900px 400px at 80% 10%, color-mix(in srgb, var(--ts-accent) 28%, transparent), transparent 60%);">
        </div>

        <div class="relative">
            <div class="flex items-center gap-3 mb-6">
                @php
                    $settings = app(\App\Services\SettingsService::class);
                    $siteLogo = $settings->get('site_logo_url') ?: asset('logo.svg');
                @endphp
                <img src="{{ $siteLogo }}" alt="Talashow" class="h-10 w-auto rounded-md" onerror="this.onerror=null; this.src='{{ asset('logo.svg') }}';">
                <span class="px-3 py-1 rounded-full text-xs font-semibold border"
                      style="background: var(--ts-accent-soft); color: var(--ts-accent); border-color: color-mix(in srgb, var(--ts-accent) 35%, transparent)">
                    {{ __('ui.application.badge_soon') }}
                </span>
            </div>

            <h1 class="text-4xl md:text-5xl font-black tracking-tight mb-4 ts-page-title">
                {{ __('ui.application.heading') }}
            </h1>
            <p class="ts-text-secondary text-lg max-w-2xl mb-8">
                {!! __('ui.application.subtitle_html') !!}
            </p>

            <div class="flex flex-wrap gap-3">
                <button type="button" class="ts-cta-accent px-6 py-3 rounded-lg font-semibold transition" data-newsletter-open>
                    {{ __('ui.application.notify_me') }}
                </button>
                <a href="{{ route('home') }}" class="ts-header-btn ts-header-btn--ghost px-6 py-3 rounded-lg font-semibold">
                    {{ __('ui.application.back_home') }}
                </a>
            </div>

            <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-xl p-4" style="background: var(--ts-btn-ghost-bg); border: 1px solid var(--ts-border)">
                    <div class="text-sm font-semibold mb-1" style="color: var(--ts-text-primary)">{{ __('ui.application.feature_1_title') }}</div>
                    <div class="text-sm ts-text-secondary">{{ __('ui.application.feature_1_desc') }}</div>
                </div>
                <div class="rounded-xl p-4" style="background: var(--ts-btn-ghost-bg); border: 1px solid var(--ts-border)">
                    <div class="text-sm font-semibold mb-1" style="color: var(--ts-text-primary)">{{ __('ui.application.feature_2_title') }}</div>
                    <div class="text-sm ts-text-secondary">{{ __('ui.application.feature_2_desc') }}</div>
                </div>
                <div class="rounded-xl p-4" style="background: var(--ts-btn-ghost-bg); border: 1px solid var(--ts-border)">
                    <div class="text-sm font-semibold mb-1" style="color: var(--ts-text-primary)">PWA</div>
                    <div class="text-sm ts-text-secondary">{{ __('ui.application.feature_3_desc') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
