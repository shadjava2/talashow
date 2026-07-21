@extends('layouts.app')

@section('title', __('ui.profile.title'))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-3xl font-bold mb-6 ts-page-title">{{ __('ui.profile.title') }}</h1>

    @php
        $favorites = $favorites ?? collect();
        $likedSeries = $likedSeries ?? collect();
        $recentlyWatched = $recentlyWatched ?? collect();

        $imgUrl = fn ($u) => \App\Support\MediaDisplay::url($u);
    @endphp

    <div class="ts-surface ts-surface--pad">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <p class="text-lg font-semibold" style="color: var(--ts-text-primary)">{{ auth()->user()->name }}</p>
                <p class="ts-text-muted text-sm">{{ auth()->user()->email }}</p>
            </div>
            <div class="text-right">
                <p class="ts-text-muted text-sm">{{ __('ui.nav.coins') }}</p>
                <p class="text-2xl font-bold text-red-600">{{ auth()->user()->total_coins }}</p>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('payment.recharge') }}" class="ts-cta-accent px-4 py-2.5 rounded-lg font-semibold transition">
                {{ __('ui.profile.recharge') }}
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="ts-header-btn ts-header-btn--ghost px-4 py-2.5 rounded-lg font-semibold">
                    {{ __('ui.profile.logout') }}
                </button>
            </form>
        </div>
    </div>

    <div class="mt-8 space-y-10">
        {{-- Favoris --}}
        <section>
            <div class="flex items-end justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-xl font-bold" style="color: var(--ts-text-primary)">{{ __('ui.profile.favorites_title') }}</h2>
                    <p class="text-sm ts-text-secondary">{{ __('ui.profile.favorites_subtitle') }}</p>
                </div>
            </div>

            @if($favorites->count() === 0)
                <div class="ts-surface ts-surface--pad ts-text-secondary">
                    {{ __('ui.profile.favorites_empty') }}
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    @foreach($favorites as $fav)
                        @php
                            $s = $fav->series;
                            if (!$s) continue;
                            $img = $imgUrl($s->poster ?: $s->cover_image ?: asset('images/placeholders/placeholder.svg'));
                        @endphp
                        <a href="{{ route('series.show', $s->slug) }}" class="group ts-surface overflow-hidden hover:border-red-600/40 transition">
                            <div class="aspect-[2/3] overflow-hidden" style="background: var(--ts-btn-ghost-bg)">
                                <img src="{{ $img }}" alt="{{ $s->titleForLocale() }}" class="w-full h-full object-cover skeleton group-hover:scale-[1.02] transition" loading="lazy" decoding="async" />
                            </div>
                            <div class="p-3">
                                <div class="text-sm font-semibold line-clamp-2" style="color: var(--ts-text-primary)">{{ $s->titleForLocale() }}</div>
                                <div class="mt-1 text-[11px] ts-text-muted flex items-center gap-2">
                                    <span>❤️ {{ number_format((int) ($s->likes_count ?? 0), 0, ',', ' ') }}</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- J'aime --}}
        <section>
            <div class="flex items-end justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-xl font-bold" style="color: var(--ts-text-primary)">{{ __('ui.profile.likes_title') }}</h2>
                    <p class="text-sm ts-text-secondary">{{ __('ui.profile.likes_subtitle') }}</p>
                </div>
            </div>

            @if($likedSeries->count() === 0)
                <div class="ts-surface ts-surface--pad ts-text-secondary">
                    {{ __('ui.profile.likes_empty') }}
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    @foreach($likedSeries as $like)
                        @php
                            $s = $like->series;
                            if (!$s) continue;
                            $img = $imgUrl($s->poster ?: $s->cover_image ?: asset('images/placeholders/placeholder.svg'));
                        @endphp
                        <a href="{{ route('series.show', $s->slug) }}" class="group ts-surface overflow-hidden hover:border-red-600/40 transition">
                            <div class="aspect-[2/3] overflow-hidden" style="background: var(--ts-btn-ghost-bg)">
                                <img src="{{ $img }}" alt="{{ $s->titleForLocale() }}" class="w-full h-full object-cover skeleton group-hover:scale-[1.02] transition" loading="lazy" decoding="async" />
                            </div>
                            <div class="p-3">
                                <div class="text-sm font-semibold line-clamp-2" style="color: var(--ts-text-primary)">{{ $s->titleForLocale() }}</div>
                                <div class="mt-1 text-[11px] ts-text-muted flex items-center gap-2">
                                    <span>❤️ {{ number_format((int) ($s->likes_count ?? 0), 0, ',', ' ') }}</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Récemment lu --}}
        <section>
            <div class="flex items-end justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-xl font-bold" style="color: var(--ts-text-primary)">{{ __('ui.profile.recent_title') }}</h2>
                    <p class="text-sm ts-text-secondary">{{ __('ui.profile.recent_subtitle') }}</p>
                </div>
            </div>

            @if($recentlyWatched->count() === 0)
                <div class="ts-surface ts-surface--pad ts-text-secondary">
                    {{ __('ui.profile.recent_empty') }}
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($recentlyWatched as $h)
                        @php
                            $ep = $h->episode;
                            $s = $ep?->series;
                            if (!$ep || !$s) continue;
                            $img = $imgUrl($ep->thumbnail ?: $s->poster ?: asset('images/placeholders/placeholder.svg'));
                            $dur = (int) ($h->duration ?: $ep->duration ?: 0);
                            $wat = (int) ($h->watch_time ?: 0);
                            $pct = $dur > 0 ? max(0, min(100, (int) round(($wat / $dur) * 100))) : 0;
                        @endphp
                        <a href="{{ route('episode.show', [$s->slug, $ep->id]) }}" class="group ts-surface overflow-hidden hover:border-red-600/40 transition flex">
                            <div class="w-40 shrink-0" style="background: var(--ts-btn-ghost-bg)">
                                <img src="{{ $img }}" alt="{{ $ep->titleForLocale() }}" class="w-full h-full object-cover skeleton" loading="lazy" decoding="async" />
                            </div>
                            <div class="p-4 flex-1 min-w-0">
                                <div class="text-sm ts-text-muted truncate">{{ $s->titleForLocale() }}</div>
                                <div class="text-base font-semibold line-clamp-2" style="color: var(--ts-text-primary)">{{ $ep->titleForLocale() }}</div>
                                <div class="mt-2">
                                    <div class="h-1.5 w-full rounded-full overflow-hidden" style="background: var(--ts-btn-ghost-bg)">
                                        <div class="h-full bg-red-600 rounded-full" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <div class="mt-2 text-xs ts-text-muted flex items-center justify-between gap-2">
                                        <span>{{ $pct }}% • {{ $h->watched_at ? $h->watched_at->diffForHumans() : '—' }}</span>
                                        <span class="font-semibold group-hover:text-red-600" style="color: var(--ts-text-primary)">{{ __('ui.profile.resume') }}</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
