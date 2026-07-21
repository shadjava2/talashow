<?php

namespace App\Console\Commands;

use App\Models\VideoProviderMapping;
use App\Services\Video\BunnyStreamProvider;
use App\Services\Video\VideoMigrationService;
use Illuminate\Console\Command;

class SyncBunnyStatusCommand extends Command
{
    protected $signature = 'videos:sync-bunny-status {--only-processing : Uniquement uploading / processing}';

    protected $description = 'Synchronise le statut Bunny pour les migrations en cours';

    public function handle(VideoMigrationService $migration, BunnyStreamProvider $bunny): int
    {
        if (! config('services.bunny_stream.library_id') || ! config('services.bunny_stream.api_key')) {
            $this->error('BUNNY_STREAM_LIBRARY_ID et BUNNY_STREAM_API_KEY sont requis.');

            return self::FAILURE;
        }

        $q = VideoProviderMapping::query()
            ->where('target_provider', 'bunny')
            ->whereNotNull('target_video_guid');

        if ($this->option('only-processing')) {
            $q->whereIn('migration_status', [
                VideoProviderMapping::STATUS_UPLOADING,
                VideoProviderMapping::STATUS_PROCESSING,
                VideoProviderMapping::STATUS_PENDING,
            ]);
        }

        $updated = 0;
        $errors = 0;

        $q->orderBy('id')->chunkById(50, function ($rows) use ($migration, $bunny, &$updated, &$errors) {
            foreach ($rows as $mapping) {
                /** @var VideoProviderMapping $mapping */
                $guid = (string) $mapping->target_video_guid;
                if ($guid === '') {
                    continue;
                }
                try {
                    $payload = $bunny->getVideo($guid);
                    $migration->syncMappingFromBunnyPayload($mapping, $payload);
                    $updated++;
                } catch (\Throwable $e) {
                    $errors++;
                    $mapping->last_checked_at = now();
                    $mapping->migration_error = $e->getMessage();
                    $mapping->save();
                    $this->warn("GUID {$guid}: {$e->getMessage()}");
                }
            }
        });

        $this->info("Synchronisé: {$updated}, erreurs API: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
