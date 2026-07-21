<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VideoCleanupLegacyConfigCommand extends Command
{
    protected $signature = 'video:cleanup-legacy-config';

    protected $description = 'Rappel : retirer du .env les clés Cloudflare Stream (plus utilisées par l’application)';

    public function handle(): int
    {
        $this->info('Le lecteur et la migration vidéo ne consomment plus Cloudflare Stream.');
        $this->line('Vous pouvez supprimer de votre .env / secrets :');
        foreach ([
            'CLOUDFLARE_STREAM_CUSTOMER_CODE',
            'CLOUDFLARE_STREAM_API_TOKEN',
            'CLOUDFLARE_STREAM_ACCOUNT_ID',
            'CLOUDFLARE_STREAM_SIGNING_KEY',
            'CLOUDFLARE_STREAM_SUBDOMAIN',
            'CLOUDFLARE_ACCOUNT_ID',
            'CLOUDFLARE_API_TOKEN',
            'CLOUDFLARE_STREAM_URL',
            'CLOUDFLARE_VERIFY_SSL',
        ] as $key) {
            $this->line('  - '.$key);
        }

        return self::SUCCESS;
    }
}
