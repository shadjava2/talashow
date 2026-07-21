@extends('admin.layouts.app')

@section('title', 'Monitoring')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Monitoring</h1>
            <p class="text-sm text-gray-400 mt-1">Santé système, sécurité, business — aucun secret affiché.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <form method="post" action="{{ route('admin.monitoring.action', ['action' => 'clear-caches']) }}" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-500 text-sm font-semibold">Vider caches</button>
            </form>
            <form method="post" action="{{ route('admin.monitoring.action', ['action' => 'queue-restart']) }}" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-lg bg-slate-700 hover:bg-slate-600 text-sm font-semibold">Queue restart</button>
            </form>
            <form method="post" action="{{ route('admin.monitoring.action', ['action' => 'prune-logs']) }}" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-lg bg-slate-700 hover:bg-slate-600 text-sm font-semibold">Logs &gt;5Mo</button>
            </form>
            <form method="post" action="{{ route('admin.monitoring.connectivity') }}" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-700 hover:bg-emerald-600 text-sm font-semibold">Test connexions</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-100">{{ session('error') }}</div>
    @endif
    @if(session('connectivity'))
        <div class="rounded-lg border border-white/10 bg-white/5 px-4 py-3 text-sm text-gray-200 space-y-1">
            <div class="font-semibold text-white mb-2">Tests connectivité</div>
            @foreach(session('connectivity') as $k => $v)
                <div><span class="text-gray-400">{{ $k }}:</span> {{ $v }}</div>
            @endforeach
        </div>
    @endif

    @php
        $badge = fn ($ok) => $ok
            ? 'bg-emerald-500/20 text-emerald-200 border-emerald-500/40'
            : 'bg-red-500/20 text-red-200 border-red-500/40';
        $warn = 'bg-amber-500/20 text-amber-100 border-amber-500/40';
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <div class="rounded-xl border border-white/10 bg-gray-900/60 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">PHP</div>
            <div class="text-lg font-semibold mt-1">{{ $phpVersion }}</div>
        </div>
        <div class="rounded-xl border border-white/10 bg-gray-900/60 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Laravel</div>
            <div class="text-lg font-semibold mt-1">{{ $laravelVersion }}</div>
        </div>
        <div class="rounded-xl border border-white/10 bg-gray-900/60 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Environnement</div>
            <div class="text-lg font-semibold mt-1 flex items-center gap-2">
                {{ $env }}
                <span class="text-xs px-2 py-0.5 rounded border {{ $debug ? $warn : $badge(true) }}">DEBUG {{ $debug ? 'ON' : 'OFF' }}</span>
            </div>
        </div>
        <div class="rounded-xl border border-white/10 bg-gray-900/60 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Disque (projet)</div>
            <div class="text-lg font-semibold mt-1">
                @if($diskPct !== null)
                    {{ $diskPct }}% utilisé
                @else
                    n/d
                @endif
            </div>
        </div>
        <div class="rounded-xl border border-white/10 bg-gray-900/60 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Logs storage</div>
            <div class="text-lg font-semibold mt-1">{{ number_format($logsBytes / 1024 / 1024, 2) }} Mo</div>
        </div>
        <div class="rounded-xl border border-white/10 bg-gray-900/60 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Queue</div>
            <div class="text-lg font-semibold mt-1">{{ $queueDriver }}</div>
            <div class="text-xs text-gray-400 mt-1">Jobs en attente : {{ $jobsPending ?? 'n/d' }} — Failed : {{ $failedCount ?? 'n/d' }}</div>
        </div>
        <div class="rounded-xl border border-white/10 bg-gray-900/60 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Dernier cron (schedule)</div>
            <div class="text-sm font-semibold mt-1">{{ $lastSchedule ? $lastSchedule : '—' }}</div>
        </div>
        <div class="rounded-xl border border-white/10 bg-gray-900/60 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Dernier webhook Bunny (log)</div>
            <div class="text-sm font-semibold mt-1">{{ $lastWebhook ? $lastWebhook : '—' }}</div>
        </div>
        <div class="rounded-xl border border-white/10 bg-gray-900/60 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Base de données</div>
            <div class="mt-1"><span class="text-xs px-2 py-1 rounded border {{ $badge($dbOk) }}">{{ $dbOk ? 'OK' : 'Erreur' }}</span></div>
        </div>
        <div class="rounded-xl border border-white/10 bg-gray-900/60 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Cache applicatif</div>
            <div class="mt-1"><span class="text-xs px-2 py-1 rounded border {{ $badge($cacheOk) }}">{{ $cacheOk ? 'OK' : 'Erreur' }}</span></div>
        </div>
        <div class="rounded-xl border border-white/10 bg-gray-900/60 p-4">
            <div class="text-xs text-gray-400 uppercase tracking-wide">Intégrations</div>
            <div class="text-xs mt-2 space-y-1">
                <div>Bunny: <span class="{{ $bunnyOk ? 'text-emerald-300' : 'text-red-300' }}">{{ $bunnyOk ? 'prêt' : 'incomplet' }}</span></div>
                <div>Stripe: <span class="{{ $stripeOk ? 'text-emerald-300' : 'text-amber-200' }}">{{ $stripeOk ? 'clé secrète' : 'non défini' }}</span></div>
                <div>PayPal: <span class="{{ $paypalOk ? 'text-emerald-300' : 'text-amber-200' }}">{{ $paypalOk ? 'configuré' : 'incomplet' }}</span></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="rounded-xl border border-white/10 bg-gray-900/40 p-4">
            <h2 class="text-lg font-semibold mb-3">Sécurité (24h)</h2>
            <div class="text-sm text-gray-300 space-y-2 mb-4">
                <div>Échecs login admin : <span class="text-white font-semibold">{{ $failedAdminLogins }}</span></div>
                <div>Échecs login public : <span class="text-white font-semibold">{{ $failedAuthLogins }}</span></div>
            </div>
            <h3 class="text-sm font-semibold text-gray-400 mb-2">IP les plus actives (security_events)</h3>
            <ul class="text-sm space-y-1 text-gray-300">
                @forelse($topIps as $row)
                    <li>{{ $row->ip ?? '—' }} — {{ $row->c }} evt.</li>
                @empty
                    <li class="text-gray-500">Aucune donnée</li>
                @endforelse
            </ul>
            <h3 class="text-sm font-semibold text-gray-400 mt-4 mb-2">Routes (security_events)</h3>
            <ul class="text-sm space-y-1 text-gray-300 break-all">
                @forelse($topRoutes as $row)
                    <li>{{ $row->route }} — {{ $row->c }}</li>
                @empty
                    <li class="text-gray-500">Aucune donnée</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-xl border border-white/10 bg-gray-900/40 p-4">
            <h2 class="text-lg font-semibold mb-3">Business (jour)</h2>
            <div class="text-sm text-gray-300 space-y-2">
                <div>Inscriptions : <span class="text-white font-semibold">{{ $usersToday }}</span></div>
                <div>Comptes mis à jour aujourd’hui : <span class="text-white font-semibold">{{ $activeUsersToday }}</span></div>
                <div>Vues épisode (first_played / création) : <span class="text-white font-semibold">{{ $episodeViewsToday }}</span></div>
            </div>
            <h3 class="text-sm font-semibold text-gray-400 mt-4 mb-2">Derniers paiements</h3>
            <ul class="text-xs space-y-1 text-gray-300">
                @forelse($recentPayments as $tx)
                    <li>#{{ $tx->id }} {{ $tx->status }} {{ $tx->amount }} {{ $tx->currency }} — {{ optional($tx->user)->email ? \App\Support\PrivacyMask::email($tx->user->email) : '—' }}</li>
                @empty
                    <li class="text-gray-500">Aucun</li>
                @endforelse
            </ul>
            <h3 class="text-sm font-semibold text-gray-400 mt-4 mb-2">Top épisodes (views_count)</h3>
            <ul class="text-xs space-y-1 text-gray-300">
                @forelse($topEpisodes as $ep)
                    <li>#{{ $ep->id }} — {{ \Illuminate\Support\Str::limit($ep->title, 40) }} ({{ $ep->views_count }})</li>
                @empty
                    <li class="text-gray-500">—</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="rounded-xl border border-white/10 bg-gray-900/40 p-4">
            <h2 class="text-lg font-semibold mb-3">Événements sécurité récents</h2>
            <div class="overflow-x-auto text-xs">
                <table class="min-w-full text-left text-gray-300">
                    <thead><tr class="text-gray-500 border-b border-white/10"><th class="py-2 pr-2">Date</th><th class="py-2 pr-2">Niveau</th><th class="py-2 pr-2">Type</th><th class="py-2">Route</th></tr></thead>
                    <tbody>
                        @foreach($recentSecurity as $ev)
                            <tr class="border-b border-white/5">
                                <td class="py-1 pr-2 whitespace-nowrap">{{ $ev->created_at }}</td>
                                <td class="py-1 pr-2">{{ $ev->level }}</td>
                                <td class="py-1 pr-2">{{ $ev->type }}</td>
                                <td class="py-1 break-all">{{ $ev->route }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="rounded-xl border border-white/10 bg-gray-900/40 p-4">
            <h2 class="text-lg font-semibold mb-3">Journal admin</h2>
            <div class="overflow-x-auto text-xs">
                <table class="min-w-full text-left text-gray-300">
                    <thead><tr class="text-gray-500 border-b border-white/10"><th class="py-2 pr-2">Date</th><th class="py-2 pr-2">Action</th><th class="py-2">User</th></tr></thead>
                    <tbody>
                        @foreach($recentAdmin as $log)
                            <tr class="border-b border-white/5">
                                <td class="py-1 pr-2 whitespace-nowrap">{{ $log->created_at }}</td>
                                <td class="py-1 pr-2">{{ $log->action }}</td>
                                <td class="py-1">{{ $log->user_id ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-white/10 bg-gray-900/40 p-4">
        <h2 class="text-lg font-semibold mb-3">Failed jobs</h2>
        <div class="overflow-x-auto text-xs">
            <table class="min-w-full text-left text-gray-300">
                <thead><tr class="text-gray-500 border-b border-white/10"><th class="py-2 pr-2">ID</th><th class="py-2 pr-2">Queue</th><th class="py-2 pr-2">Échec</th><th class="py-2">Action</th></tr></thead>
                <tbody>
                    @foreach($failedJobsList as $fj)
                        <tr class="border-b border-white/5">
                            <td class="py-1 pr-2">{{ $fj->id }}</td>
                            <td class="py-1 pr-2">{{ $fj->queue }}</td>
                            <td class="py-1 pr-2 whitespace-nowrap">{{ $fj->failed_at }}</td>
                            <td class="py-1">
                                <form method="post" action="{{ route('admin.monitoring.action', ['action' => 'retry-failed-job']) }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="failed_job_id" value="{{ $fj->id }}" />
                                    <button type="submit" class="text-amber-300 hover:underline">Relancer</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-xl border border-white/10 bg-gray-900/40 p-4">
        <h2 class="text-lg font-semibold mb-3">Fin du fichier laravel.log</h2>
        <pre class="text-[11px] leading-relaxed text-gray-300 whitespace-pre-wrap max-h-96 overflow-y-auto bg-black/40 p-3 rounded-lg border border-white/5">@foreach($logTail as $line){{ $line }}
@endforeach</pre>
    </div>
</div>
@endsection
