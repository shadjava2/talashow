<?php

namespace App\Console\Commands;

use App\Models\Episode;
use App\Models\VideoProviderMapping;
use App\Services\Video\VideoMigrationService;
use Illuminate\Console\Command;

class VideoBunnySyncCommand extends Command
{
    protected $signature = 'video:bunny-sync {episode? : ID épisode (optionnel)}';

    protected $description = 'Resynchronise l’état Bunny (API) vers les épisodes / mappings pour un ou tous les GUID connus';

    public function handle(VideoMigrationService $migration): int
    {
        if (! config('services.bunny_stream.library_id') || ! config('services.bunny_stream.api_key')) {
            $this->error('BUNNY_STREAM_LIBRARY_ID et BUNNY_STREAM_API_KEY sont requis.');

            return self::FAILURE;
        }

        $episodeId = $this->argument('episode');
        $guids = [];

        if ($episodeId !== null && $episodeId !== '') {
            $ep = Episode::query()->find((int) $episodeId);
            if (! $ep) {
                $this->error('Épisode introuvable.');

                return self::FAILURE;
            }
            if (is_string($ep->external_video_id) && $ep->external_video_id !== '') {
                $guids[] = (string) $ep->external_video_id;
            }
            $guids = array_merge($guids, VideoProviderMapping::query()
                ->where('video_id', $ep->id)
                ->pluck('target_video_guid')
                ->filter()
                ->map(fn ($g) => (string) $g)
                ->all());
        } else {
            $guids = array_values(array_unique(array_filter(array_merge(
                VideoProviderMapping::query()->whereNotNull('target_video_guid')->pluck('target_video_guid')->all(),
                Episode::query()->whereNotNull('external_video_id')->pluck('external_video_id')->all(),
            ))));
        }

        $guids = array_values(array_unique(array_filter($guids)));
        if ($guids === []) {
            $this->warn('Aucun GUID Bunny à synchroniser.');

            return self::SUCCESS;
        }

        $ok = 0;
        $fail = 0;
        foreach ($guids as $guid) {
            try {
                $migration->syncAllFromBunnyGuid((string) $guid);
                $ok++;
                $this->line("OK {$guid}");
            } catch (\Throwable $e) {
                $fail++;
                $this->warn("Échec {$guid}: {$e->getMessage()}");
            }
        }

        $this->info("Terminé — OK: {$ok}, erreurs: {$fail}");

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }
}
