<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\SecurityEvent;
use App\Models\SystemHeartbeat;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PayPalService;
use App\Services\SecurityAuditService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MonitoringController extends Controller
{
    public function __construct(
        private SettingsService $settings,
        private PayPalService $paypal
    ) {}

    public function index()
    {
        $user = Auth::user();
        abort_unless($user && ($user->is_admin || $user->hasPermission('monitoring.view')), 403);

        $phpVersion = PHP_VERSION;
        $laravelVersion = app()->version();
        $env = config('app.env');
        $debug = (bool) config('app.debug');

        $diskFree = @disk_free_space(base_path());
        $diskTotal = @disk_total_space(base_path());
        $diskPct = ($diskFree !== false && $diskTotal && $diskTotal > 0)
            ? round((1 - ($diskFree / $diskTotal)) * 100, 1)
            : null;

        $logsBytes = 0;
        $logsDir = storage_path('logs');
        if (is_dir($logsDir)) {
            foreach (File::allFiles($logsDir) as $f) {
                $logsBytes += $f->getSize();
            }
        }

        $queueDriver = config('queue.default');
        $jobsPending = Schema::hasTable('jobs') ? (int) DB::table('jobs')->count() : null;
        $failedCount = Schema::hasTable('failed_jobs') ? (int) DB::table('failed_jobs')->count() : null;

        $lastSchedule = null;
        if (Schema::hasTable('system_heartbeats')) {
            $lastSchedule = SystemHeartbeat::query()->where('key', 'laravel_schedule')->value('beat_at');
        }

        $lastWebhook = null;
        if (Schema::hasTable('video_webhook_logs')) {
            $lastWebhook = DB::table('video_webhook_logs')->orderByDesc('id')->value('created_at');
        }

        $dbOk = false;
        try {
            DB::connection()->getPdo();
            $dbOk = true;
        } catch (\Throwable) {
            $dbOk = false;
        }

        $cacheOk = false;
        try {
            Cache::put('talashow_monitor_ping', 1, 5);
            $cacheOk = Cache::get('talashow_monitor_ping') === 1;
        } catch (\Throwable) {
            $cacheOk = false;
        }

        $bunnyOk = (bool) config('services.bunny_stream.library_id') && (bool) config('services.bunny_stream.api_key');
        $stripeOk = (bool) config('services.stripe.secret');
        $paypalOk = $this->paypal->isConfigured();

        $since = now()->subDay();
        $failedAdminLogins = Schema::hasTable('security_events')
            ? SecurityEvent::query()->where('type', 'admin_login_failed')->where('created_at', '>=', $since)->count()
            : 0;
        $failedAuthLogins = Schema::hasTable('security_events')
            ? SecurityEvent::query()->where('type', 'auth_login_failed')->where('created_at', '>=', $since)->count()
            : 0;

        $topIps = Schema::hasTable('security_events')
            ? SecurityEvent::query()
                ->selectRaw('ip, COUNT(*) as c')
                ->where('created_at', '>=', $since)
                ->whereNotNull('ip')
                ->groupBy('ip')
                ->orderByDesc('c')
                ->limit(8)
                ->get()
            : collect();

        $topRoutes = Schema::hasTable('security_events')
            ? SecurityEvent::query()
                ->selectRaw('route, COUNT(*) as c')
                ->where('created_at', '>=', $since)
                ->whereNotNull('route')
                ->groupBy('route')
                ->orderByDesc('c')
                ->limit(8)
                ->get()
            : collect();

        $recentSecurity = Schema::hasTable('security_events')
            ? SecurityEvent::query()->orderByDesc('id')->limit(25)->get()
            : collect();

        $recentAdmin = Schema::hasTable('admin_activity_logs')
            ? AdminActivityLog::query()->with('user')->orderByDesc('id')->limit(25)->get()
            : collect();

        $usersToday = Schema::hasTable('users')
            ? User::query()->whereDate('created_at', today())->count()
            : 0;

        $activeUsersToday = Schema::hasTable('users')
            ? User::query()->whereDate('updated_at', today())->count()
            : 0;

        $episodeViewsToday = 0;
        if (Schema::hasTable('episode_views')) {
            $episodeViewsToday = (int) DB::table('episode_views')
                ->where(function ($q) {
                    $q->whereDate('first_played_at', today())
                        ->orWhereDate('created_at', today());
                })
                ->count();
        }

        $recentPayments = Schema::hasTable('transactions')
            ? Transaction::query()->with('user')->orderByDesc('id')->limit(10)->get()
            : collect();

        $failedPayments = Schema::hasTable('transactions')
            ? Transaction::query()->where('status', 'failed')->orderByDesc('id')->limit(10)->get()
            : collect();

        $topEpisodes = Schema::hasTable('episodes')
            ? DB::table('episodes')->orderByDesc('views_count')->limit(5)->get(['id', 'title', 'views_count'])
            : collect();

        $failedJobsList = Schema::hasTable('failed_jobs')
            ? DB::table('failed_jobs')->orderByDesc('id')->limit(15)->get(['id', 'uuid', 'queue', 'failed_at'])
            : collect();

        $logTail = [];
        $logFile = storage_path('logs/laravel.log');
        if (is_readable($logFile) && filesize($logFile) < 2_000_000) {
            try {
                $logTail = collect(File::lines($logFile))->take(-80)->values()->all();
            } catch (\Throwable) {
                $logTail = [];
            }
        } elseif (is_readable($logFile)) {
            $logTail = ['(Fichier trop volumineux — utilisez l’action « vider logs » ou téléchargez le fichier.)'];
        }

        return view('admin.monitoring.index', compact(
            'phpVersion',
            'laravelVersion',
            'env',
            'debug',
            'diskFree',
            'diskTotal',
            'diskPct',
            'logsBytes',
            'queueDriver',
            'jobsPending',
            'failedCount',
            'lastSchedule',
            'lastWebhook',
            'dbOk',
            'cacheOk',
            'bunnyOk',
            'stripeOk',
            'paypalOk',
            'failedAdminLogins',
            'failedAuthLogins',
            'topIps',
            'topRoutes',
            'recentSecurity',
            'recentAdmin',
            'usersToday',
            'activeUsersToday',
            'episodeViewsToday',
            'recentPayments',
            'failedPayments',
            'topEpisodes',
            'failedJobsList',
            'logTail'
        ));
    }

    public function action(Request $request, string $action)
    {
        $user = Auth::user();
        abort_unless($user && ($user->is_admin || $user->hasPermission('monitoring.view')), 403);

        $allowed = ['clear-caches', 'queue-restart', 'retry-failed-job', 'prune-logs'];
        abort_unless(in_array($action, $allowed, true), 404);

        try {
            if ($action === 'clear-caches') {
                Artisan::call('config:clear');
                Artisan::call('route:clear');
                Artisan::call('view:clear');
                Artisan::call('cache:clear');
                SecurityAuditService::adminActivity('monitoring.cache_cleared', [], $request);

                return back()->with('success', 'Caches Laravel vidés (config, routes, vues, application).');
            }

            if ($action === 'queue-restart') {
                Artisan::call('queue:restart');
                SecurityAuditService::adminActivity('monitoring.queue_restart', [], $request);

                return back()->with('success', 'Signal queue:restart envoyé aux workers.');
            }

            if ($action === 'retry-failed-job') {
                $id = (int) $request->input('failed_job_id');
                abort_unless($id > 0, 422);
                if (! Schema::hasTable('failed_jobs')) {
                    return back()->with('error', 'Table failed_jobs absente.');
                }
                Artisan::call('queue:retry', [(string) $id]);
                SecurityAuditService::adminActivity('monitoring.failed_job_retry', ['job_id' => $id], $request);

                return back()->with('success', 'Job relancé si présent.');
            }

            if ($action === 'prune-logs') {
                $deleted = 0;
                $logsPath = storage_path('logs');
                if (is_dir($logsPath)) {
                    foreach (File::glob($logsPath.'/*.log') ?: [] as $path) {
                        if (is_string($path) && str_contains(basename($path), 'laravel') && is_file($path)) {
                            $size = filesize($path) ?: 0;
                            if ($size > 5_000_000) {
                                File::put($path, '');
                                $deleted++;
                            }
                        }
                    }
                }
                SecurityAuditService::adminActivity('monitoring.logs_pruned', ['files_reset' => $deleted], $request);

                return back()->with('success', 'Fichiers de log volumineux vidés (seuil 5 Mo).');
            }
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Action impossible : '.$e->getMessage());
        }

        return back();
    }

    public function connectivity(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && ($user->is_admin || $user->hasPermission('monitoring.view')), 403);

        $results = [];

        try {
            $this->paypal->getAccessToken();
            $results['paypal'] = 'OK (token)';
        } catch (\Throwable $e) {
            $results['paypal'] = 'Erreur : '.Str::limit($e->getMessage(), 120);
        }

        $results['stripe'] = config('services.stripe.secret') ? 'Clé secrète présente' : 'Non configuré';
        $results['bunny'] = (config('services.bunny_stream.library_id') && config('services.bunny_stream.api_key'))
            ? 'Identifiants présents'
            : 'Incomplet';

        SecurityAuditService::adminActivity('monitoring.connectivity_test', ['results' => $results], $request);

        return back()->with('connectivity', $results);
    }
}
