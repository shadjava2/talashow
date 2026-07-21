@extends('layouts.app')

@section('title', $series->titleForLocale())

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="bg-gray-900/40 border border-gray-700/60 rounded-2xl overflow-hidden">
        <div class="p-6 sm:p-8 flex flex-col sm:flex-row gap-6">
            <div class="shrink-0 w-full sm:w-48">
                <div class="relative aspect-[2/3] rounded-xl overflow-hidden border border-white/10 bg-black/20">
                    <div class="absolute inset-0 skeleton"></div>
                    <img
                        src="{{ $series->poster }}"
                        alt="{{ $series->titleForLocale() }}"
                        class="absolute inset-0 w-full h-full object-cover opacity-0 transition-opacity duration-200 js-skeleton-img"
                        onload="this.classList.remove('opacity-0'); this.parentElement?.querySelector('.skeleton')?.classList.add('hidden')"
                        onerror="this.onerror=null; this.src='{{ asset('/images/placeholders/placeholder.svg') }}'; this.parentElement?.querySelector('.skeleton')?.classList.add('hidden')"
                    >
                </div>
            </div>

            <div class="min-w-0 flex-1">
                <div class="text-xs font-semibold text-red-400 uppercase tracking-wider">Bientôt disponible</div>
                <h1 class="text-3xl sm:text-4xl font-bold mt-2">{{ $series->titleForLocale() }}</h1>

                <div class="mt-3 text-gray-300">
                    @if($series->published_at)
                        Disponible le <span class="font-semibold text-white">{{ $series->published_at->format('d/m/Y à H:i') }}</span>.
                    @else
                        Cette série n’est pas encore disponible.
                    @endif
                </div>

                @if($series->published_at)
                    <div
                        id="ts-countdown"
                        class="mt-4 inline-flex items-center gap-2 px-4 py-3 rounded-xl bg-black/25 border border-white/10 text-white"
                        data-target-utc="{{ $series->published_at->copy()->utc()->toIso8601String() }}"
                    >
                        <span class="text-xs text-gray-300">Compte à rebours :</span>
                        <span id="ts-countdown-value" class="font-extrabold tracking-wide">—</span>
                    </div>
                    <div class="mt-2 text-xs text-gray-400">
                        Le compte à rebours est calculé en UTC pour éviter les problèmes de fuseau horaire.
                    </div>
                @endif

                <p class="mt-4 text-gray-300 leading-relaxed">
                    {{ $series->descriptionForLocale() }}
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    @auth
                        @if(isset($alreadyRequested) && $alreadyRequested)
                            <div class="px-5 py-3 rounded-xl font-semibold bg-green-600/15 border border-green-600/30 text-green-100">
                                Vous serez notifié
                            </div>
                        @else
                            <form method="POST" action="{{ route('series.notify', $series->slug) }}">
                                @csrf
                                <button type="submit" class="px-5 py-3 bg-red-600 hover:bg-red-700 rounded-xl font-semibold transition">
                                    Notifier moi
                                </button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="px-5 py-3 bg-red-600 hover:bg-red-700 rounded-xl font-semibold transition">
                            Se connecter pour être notifié
                        </a>
                    @endauth

                    <a href="{{ route('browse') }}" class="px-5 py-3 bg-white/10 hover:bg-white/20 rounded-xl font-semibold transition">
                        Explorer
                    </a>
                </div>

                <div class="mt-4 text-xs text-gray-400">
                    Astuce: une fois disponible, vous recevrez un email automatique (si vous avez cliqué “Notifier moi”).
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if($series->published_at)
<script>
(() => {
  const root = document.getElementById('ts-countdown');
  const out = document.getElementById('ts-countdown-value');
  if (!root || !out) return;

  const targetUtc = root.getAttribute('data-target-utc');
  const targetMs = targetUtc ? Date.parse(targetUtc) : NaN;
  if (!Number.isFinite(targetMs)) { out.textContent = '—'; return; }

  const pad2 = (n) => String(n).padStart(2, '0');
  const fmt = (ms) => {
    const total = Math.max(0, Math.floor(ms / 1000));
    const d = Math.floor(total / 86400);
    const h = Math.floor((total % 86400) / 3600);
    const m = Math.floor((total % 3600) / 60);
    const s = total % 60;
    if (d > 0) return `${d}j ${pad2(h)}:${pad2(m)}:${pad2(s)}`;
    return `${pad2(h)}:${pad2(m)}:${pad2(s)}`;
  };

  const tick = () => {
    const diff = targetMs - Date.now();
    out.textContent = fmt(diff);

    if (diff <= 0) {
      out.textContent = 'Disponible';
      // Refresh auto 1 fois (anti-loop)
      const key = 'talashow_scheduled_series_refresh_done';
      if (sessionStorage.getItem(key) !== '1') {
        sessionStorage.setItem(key, '1');
        window.setTimeout(() => window.location.reload(), 900);
      }
    }
  };

  tick();
  window.setInterval(tick, 1000);
})();
</script>
@endif
@endpush

