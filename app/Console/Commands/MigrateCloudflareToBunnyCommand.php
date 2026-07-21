<?php

namespace App\Console\Commands;

use App\Models\Episode;
use App\Models\VideoProviderMapping;
use App\Services\Video\BunnyStreamProvider;
use App\Services\Video\VideoMigrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MigrateCloudflareToBunnyCommand extends Command
{
    protected $signature = 'video:migrate-cloudflare-to-bunny
                            {--chunk=25 : Nombre d’entrées (épisode × langue) par lot}
                            {--only-pending : Uniquement sans mapping « ready »}
                            {--force : Réinitialise les mappings en échec et relance}
                            {--dry-run : Affiche le plan sans appeler Bunny}';

    protected $description = 'Import MP4 locaux vers Bunny (VIDEO_MIGRATION_LOCAL_BASE) — plus aucun appel Cloudflare Stream';

    public function handle(VideoMigrationService $migration, BunnyStreamProvider $bunny): int
    {
        if (! config('services.bunny_stream.library_id') || ! config('services.bunny_stream.api_key')) {
            $this->error('BUNNY_STREAM_LIBRARY_ID et BUNNY_STREAM_API_KEY sont requis.');

            return self::FAILURE;
        }

        $chunk = max(1, (int) $this->option('chunk'));
        $onlyPending = (bool) $this->option('only-pending');
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');

        $pairs = $this->collectWorkItems($onlyPending, $force);
        $this->info('Entrées à traiter : '.count($pairs));

        if ($dryRun) {
            foreach ($pairs as $item) {
                $key = VideoMigrationService::localMp4KeyFromSourceUrl($item['url']) ?? '';
                $hasFile = VideoMigrationService::localMigrationPathForKey($key) !== null;
                $this->line("épisode {$item['episode']->id} [{$item['lang']}] clé={$key} fichier_local=".($hasFile ? 'oui' : 'non').' action='.$item['action']);
            }

            return self::SUCCESS;
        }

        $ok = 0;
        $fail = 0;
        $skipped = 0;

        foreach (array_chunk($pairs, $chunk) as $batch) {
            foreach ($batch as $item) {
                /** @var Episode $episode */
                $episode = $item['episode'];
                $lang = $item['lang'];
                $url = $item['url'];
                $action = $item['action'];

                try {
                    $mapping = $migration->ensureMapping($episode, $lang, $url);
                    if ($mapping->migration_status === VideoProviderMapping::STATUS_READY && ! $force) {
                        $skipped++;
                        continue;
                    }

                    if ($action === 'sync' && $mapping->target_video_guid) {
                        $payload = $bunny->getVideo((string) $mapping->target_video_guid);
                        $migration->syncMappingFromBunnyPayload($mapping->fresh(), $payload);
                        $ok++;
                        continue;
                    }

                    $migration->migrateEpisodeLanguage($episode, $lang, $url, false);
                    $ok++;
                } catch (\Throwable $e) {
                    $fail++;
                    Log::channel('video_migration')->error('video_migration', [
                        'phase' => 'command',
                        'status' => 'exception',
                        'video_id' => $episode->id,
                        'error' => $e->getMessage(),
                    ]);
                    $this->warn("Échec épisode {$episode->id} [{$lang}]: {$e->getMessage()}");
                }
            }
        }

        $this->info("Terminé — OK: {$ok}, ignorés: {$skipped}, erreurs: {$fail}");

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return list<array{episode: Episode, lang: string, url: string, action: string}>
     */
    protected function collectWorkItems(bool $onlyPending, bool $force): array
    {
        $out = [];

        Episode::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->chunkById(100, function ($episodes) use (&$out, $onlyPending, $force) {
                foreach ($episodes as $episode) {
                    /** @var Episode $episode */
                    $urls = is_array($episode->video_urls) ? $episode->video_urls : [];
                    if ($urls === [] && $episode->video_url) {
                        $urls[''] = (string) $episode->video_url;
                    }

                    foreach ($urls as $lang => $url) {
                        $url = trim((string) $url);
                        if ($url === '') {
                            continue;
                        }

                        $langNorm = strtolower(trim((string) $lang));

                        $mapping = VideoProviderMapping::query()
                            ->where('video_id', $episode->id)
                            ->where('video_lang', $langNorm)
                            ->first();

                        if ($force && $mapping && $mapping->migration_status === VideoProviderMapping::STATUS_FAILED) {
                            $mapping->update([
                                'target_video_guid' => null,
                                'migration_status' => VideoProviderMapping::STATUS_PENDING,
                                'migration_error' => null,
                            ]);
                            $mapping = $mapping->fresh();
                        }

                        if ($onlyPending && $mapping && $mapping->migration_status === VideoProviderMapping::STATUS_READY) {
                            continue;
                        }

                        $key = VideoMigrationService::localMp4KeyFromSourceUrl($url) ?? '';
                        $localPath = $key !== '' ? VideoMigrationService::localMigrationPathForKey($key) : null;

                        $action = 'upload';
                        if ($mapping && $mapping->target_video_guid && $mapping->migration_status !== VideoProviderMapping::STATUS_READY) {
                            $action = 'sync';
                            $out[] = [
                                'episode' => $episode,
                                'lang' => $langNorm,
                                'url' => $url,
                                'action' => $action,
                            ];
                            continue;
                        }

                        if ($localPath === null) {
                            continue;
                        }

                        if ($mapping && $mapping->migration_status === VideoProviderMapping::STATUS_READY && ! $force) {
                            continue;
                        }

                        $out[] = [
                            'episode' => $episode,
                            'lang' => $langNorm,
                            'url' => $url,
                            'action' => $action,
                        ];
                    }
                }
            });

        return $out;
    }
}
