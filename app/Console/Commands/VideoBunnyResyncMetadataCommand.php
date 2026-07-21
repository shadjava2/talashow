<?php

namespace App\Console\Commands;

use App\Models\VideoAsset;
use App\Services\Video\BunnyStreamService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class VideoBunnyResyncMetadataCommand extends Command
{
    protected $signature = 'video:bunny-resync-metadata {--chunk=50 : Taille des lots}';

    protected $description = 'Recharge les métadonnées Bunny pour chaque enregistrement video_assets';

    public function handle(BunnyStreamService $bunny): int
    {
        if (! Schema::hasTable('video_assets')) {
            $this->warn('Table video_assets absente (migrations non exécutées ?).');

            return self::SUCCESS;
        }

        if (! config('services.bunny_stream.library_id') || ! config('services.bunny_stream.api_key')) {
            $this->error('BUNNY_STREAM_LIBRARY_ID et BUNNY_STREAM_API_KEY sont requis.');

            return self::FAILURE;
        }

        $chunk = max(1, (int) $this->option('chunk'));
        $ok = 0;
        $fail = 0;

        VideoAsset::query()
            ->whereNotNull('bunny_video_guid')
            ->where('bunny_video_guid', '!=', '')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) use ($bunny, &$ok, &$fail) {
                foreach ($rows as $asset) {
                    try {
                        $bunny->syncVideoMetadata($asset);
                        $ok++;
                    } catch (\Throwable $e) {
                        $fail++;
                        $this->warn("Asset #{$asset->id}: {$e->getMessage()}");
                    }
                }
            });

        $this->info("Terminé — OK: {$ok}, erreurs: {$fail}");

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }
}
