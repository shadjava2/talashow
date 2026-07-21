<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command(
    'videos:migrate-cloudflare-to-bunny {--chunk=25} {--only-pending} {--force} {--dry-run}',
    function () {
        $this->warn('Alias : la commande s’appelle désormais video:migrate-cloudflare-to-bunny (import MP4 locaux uniquement).');

        return $this->call('video:migrate-cloudflare-to-bunny', [
            '--chunk' => $this->option('chunk'),
            '--only-pending' => $this->option('only-pending'),
            '--force' => $this->option('force'),
            '--dry-run' => $this->option('dry-run'),
        ]);
    }
)->purpose('Alias vers video:migrate-cloudflare-to-bunny');
