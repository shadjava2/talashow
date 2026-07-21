<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Publication programmée: envoyer les notifications "série disponible"
        $schedule->command('talashow:send-series-release-notifications')->everyMinute()->withoutOverlapping();

        // Publication programmée: envoyer les notifications "épisode disponible" (+ newsletter si activé)
        $schedule->command('talashow:send-episode-publish-emails')->everyMinute()->withoutOverlapping();

        // Newsletter: envoyer les campagnes en lots (anti-timeout)
        $schedule->command('talashow:send-newsletter-campaigns')->everyMinute()->withoutOverlapping();

        $schedule->call(function () {
            if (! \Illuminate\Support\Facades\Schema::hasTable('system_heartbeats')) {
                return;
            }
            \App\Models\SystemHeartbeat::query()->updateOrCreate(
                ['key' => 'laravel_schedule'],
                ['beat_at' => now()]
            );
        })->everyMinute();

        $schedule->command('video:prune-playback-sessions')->dailyAt('03:15');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
