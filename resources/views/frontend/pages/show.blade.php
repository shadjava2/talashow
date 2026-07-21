@extends('layouts.app')

@section('title', $page->titleForLocale())

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-6">
        <a href="{{ route('home') }}" class="text-sm text-gray-400 hover:text-white transition">{{ __('ui.nav.home') }}</a>
        <span class="text-gray-600 mx-2">/</span>
        <span class="text-sm text-gray-300">{{ $page->titleForLocale() }}</span>
    </div>

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700/60">
        <h1 class="text-3xl font-bold mb-4">{{ $page->titleForLocale() }}</h1>
        <div class="prose prose-invert max-w-none text-gray-200">
            {!! nl2br(e($page->contentForLocale() ?? '')) !!}
        </div>
        <div class="mt-6 text-xs text-gray-400">
            {{ __('ui.pages.last_updated') }}: {{ optional($page->updated_at)->format('d/m/Y H:i') }}
        </div>
    </div>
</div>
@endsection

