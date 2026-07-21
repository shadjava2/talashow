<?php

namespace App\Services\Video;

use App\Models\Episode;
use App\Models\VideoAsset;
use App\Models\VideoProviderMapping;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class VideoMigrationService
{
    public function __construct(
        protected BunnyStreamProvider $bunny,
        protected BunnyStreamService $bunnyStream,
    ) {}

    /**
     * Clé fichier local attendue : {VIDEO_MIGRATION_LOCAL_BASE}/{clé}.mp4
     * Dérivée d’un UID 32 hex dans l’URL (ex. anciens manifestes) ou du nom de fichier sans extension.
     */
    public static function localMp4KeyFromSourceUrl(?string $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $url = trim($url);
        if (preg_match('#/([a-f0-9]{32})(?:/|\.|$|\?)#i', $url, $m)) {
            return strtolower($m[1]);
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return null;
        }

        $base = basename($path);
        $base = preg_replace('#\.(m3u8|mp4|mkv|mov|webm)$#i', '', $base) ?? $base;

        if ($base === '' || $base === '.' || strlen($base) > 200) {
            return null;
        }

        return $base;
    }

    public static function localMigrationPathForKey(string $key): ?string
    {
        $localBase = config('video.migration_local_base');
        if (! is_string($localBase) || $localBase === '' || ! is_dir($localBase)) {
            return null;
        }

        $path = rtrim($localBase, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$key.'.mp4';

        return is_file($path) ? $path : null;
    }

    public function ensureMapping(Episode $episode, string $lang, string $sourceUrl): VideoProviderMapping
    {
        $langNorm = strtolower(trim($lang));
        $existing = VideoProviderMapping::query()
            ->where('video_id', $episode->id)
            ->where('video_lang', $langNorm)
            ->first();

        if ($existing) {
            return $existing;
        }

        $key = self::localMp4KeyFromSourceUrl($sourceUrl) ?? '';

        $mapping = new VideoProviderMapping([
            'video_id' => $episode->id,
            'video_lang' => $langNorm,
            'content_type' => 'episode',
            'content_id' => $episode->id,
            'source_provider' => 'legacy_local',
            'source_asset_id' => $key,
            'source_playback_url' => $sourceUrl,
            'target_provider' => 'bunny',
            'target_library_id' => (string) config('services.bunny_stream.library_id'),
            'target_cdn_hostname' => trim((string) config('services.bunny_stream.cdn_hostname')),
            'migration_status' => VideoProviderMapping::STATUS_PENDING,
        ]);
        $mapping->save();

        return $mapping;
    }

    /**
     * @throws \Throwable
     */
    public function migrateEpisodeLanguage(Episode $episode, string $lang, string $sourceUrl, bool $force = false): void
    {
        $this->log([
            'phase' => 'start',
            'status' => 'running',
            'video_id' => $episode->id,
            'source_playback_url' => $sourceUrl,
            'video_lang' => $lang,
        ]);

        $mapping = $this->ensureMapping($episode, $lang, $sourceUrl);

        if ($mapping->target_video_guid && ! $force) {
            $this->log([
                'phase' => 'skip',
                'status' => 'idempotent',
                'video_id' => $episode->id,
                'target_video_guid' => $mapping->target_video_guid,
            ]);

            return;
        }

        $key = $mapping->source_asset_id ?: (string) self::localMp4KeyFromSourceUrl($sourceUrl);
        if ($key === '') {
            $mapping->update([
                'migration_status' => VideoProviderMapping::STATUS_FAILED,
                'migration_error' => 'Impossible de dériver une clé fichier depuis l’URL',
            ]);
            $this->log([
                'phase' => 'failed',
                'status' => 'failed',
                'video_id' => $episode->id,
                'error' => 'missing_local_key',
            ]);

            return;
        }

        if ($mapping->source_asset_id !== $key) {
            $mapping->update(['source_asset_id' => $key]);
        }

        $localPath = self::localMigrationPathForKey($key);
        if ($localPath === null) {
            $mapping->update([
                'migration_status' => VideoProviderMapping::STATUS_FAILED,
                'migration_error' => 'Fichier MP4 introuvable dans VIDEO_MIGRATION_LOCAL_BASE ('.$key.'.mp4)',
            ]);
            $this->log([
                'phase' => 'failed',
                'status' => 'failed',
                'video_id' => $episode->id,
                'error' => 'missing_local_mp4',
                'key' => $key,
            ]);

            return;
        }

        $title = 'Episode '.$episode->id.' ['.$lang.']';
        $created = $this->bunny->createVideo($title, []);

        $guid = $this->bunny->guidFromCreateResponse($created);
        if ($guid === null) {
            $mapping->update([
                'migration_status' => VideoProviderMapping::STATUS_FAILED,
                'migration_error' => 'Réponse Bunny sans guid',
            ]);
            $this->log([
                'phase' => 'failed',
                'status' => 'failed',
                'video_id' => $episode->id,
                'error' => 'bunny_no_guid',
            ]);

            return;
        }

        $mapping->update([
            'target_video_guid' => $guid,
            'target_library_id' => (string) config('services.bunny_stream.library_id'),
            'target_cdn_hostname' => trim((string) config('services.bunny_stream.cdn_hostname')),
            'migration_status' => VideoProviderMapping::STATUS_UPLOADING,
            'migration_error' => null,
        ]);

        $this->log([
            'phase' => 'uploading',
            'status' => 'uploading',
            'video_id' => $episode->id,
            'source_asset_id' => $key,
            'target_video_guid' => $guid,
        ]);

        try {
            $this->bunny->uploadVideoBinary($guid, $localPath, ['content_type' => 'video/mp4']);

            $mapping->refresh();
            $mapping->update([
                'migration_status' => VideoProviderMapping::STATUS_PROCESSING,
            ]);

            $this->log([
                'phase' => 'processing',
                'status' => 'processing',
                'video_id' => $episode->id,
                'target_video_guid' => $guid,
            ]);
        } catch (\Throwable $e) {
            $mapping->update([
                'migration_status' => VideoProviderMapping::STATUS_FAILED,
                'migration_error' => $e->getMessage(),
            ]);
            $this->log([
                'phase' => 'failed',
                'status' => 'failed',
                'video_id' => $episode->id,
                'target_video_guid' => $guid,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function applyBunnyVideoPayloadToEpisode(
        Episode $episode,
        ?string $videoLangKey,
        array $videoPayload,
        string $guid
    ): void {
        $urls = $this->bunny->getPlaybackUrls($guid, $videoPayload);
        $hls = $urls['hls'];
        $thumb = $urls['thumbnail'];

        if ($this->bunny->hasTranscodeFailure($videoPayload)) {
            $episode->update([
                'video_status' => 'failed',
                'external_video_id' => $guid,
            ]);

            return;
        }

        if (! $this->bunny->isReady($videoPayload)) {
            $episode->update([
                'video_status' => 'processing',
                'external_video_id' => $guid,
            ]);

            return;
        }

        $updates = [
            'video_provider' => 'bunny',
            'external_video_id' => $guid,
            'hls_url' => $hls,
            'playback_url' => $hls,
            'video_status' => 'ready',
            'video_url' => $hls,
            'video_type' => 'bunny',
        ];
        if ($thumb) {
            $updates['thumbnail'] = $thumb;
        }
        if ($videoLangKey !== null && $videoLangKey !== '') {
            $map = is_array($episode->video_urls) ? $episode->video_urls : [];
            $map[strtolower($videoLangKey)] = $hls;
            $updates['video_urls'] = $map;
        }
        $episode->update($updates);
    }

    /**
     * @param  array<string, mixed>  $videoPayload
     */
    public function syncMappingFromBunnyPayload(VideoProviderMapping $mapping, array $videoPayload): void
    {
        $guid = (string) ($mapping->target_video_guid ?? '');
        if ($guid === '') {
            return;
        }

        $urls = $this->bunny->getPlaybackUrls($guid, $videoPayload);
        $hls = $urls['hls'];

        $mapping->last_checked_at = now();

        if ($this->bunny->hasTranscodeFailure($videoPayload)) {
            $mapping->migration_status = VideoProviderMapping::STATUS_FAILED;
            $mapping->migration_error = 'Transcodage Bunny en erreur';
            $mapping->save();
            if ($mapping->video_id) {
                $ep = Episode::query()->find($mapping->video_id);
                if ($ep) {
                    $this->applyBunnyVideoPayloadToEpisode($ep, (string) $mapping->video_lang, $videoPayload, $guid);
                }
            }

            return;
        }

        if ($this->bunny->isReady($videoPayload)) {
            $mapping->migration_status = VideoProviderMapping::STATUS_READY;
            $mapping->target_hls_url = $hls;
            $mapping->migration_error = null;
            $mapping->migrated_at = now();
            $mapping->save();

            if ($mapping->video_id) {
                $ep = Episode::query()->find($mapping->video_id);
                if ($ep) {
                    $this->applyBunnyVideoPayloadToEpisode($ep, (string) $mapping->video_lang, $videoPayload, $guid);
                }
            }

            return;
        }

        $mapping->migration_status = VideoProviderMapping::STATUS_PROCESSING;
        $mapping->save();

        if ($mapping->video_id) {
            $ep = Episode::query()->find($mapping->video_id);
            if ($ep) {
                $this->applyBunnyVideoPayloadToEpisode($ep, (string) $mapping->video_lang, $videoPayload, $guid);
            }
        }
    }

    public function syncAllFromBunnyGuid(string $guid, ?array $videoPayload = null): void
    {
        $payload = $videoPayload ?? $this->bunny->getVideo($guid);

        foreach (VideoProviderMapping::query()->where('target_video_guid', $guid)->cursor() as $mapping) {
            $this->syncMappingFromBunnyPayload($mapping, $payload);
        }

        foreach (Episode::query()->where('external_video_id', $guid)->cursor() as $episode) {
            $this->applyBunnyVideoPayloadToEpisode($episode, null, $payload, $guid);
        }

        if (Schema::hasTable('video_assets')) {
            foreach (VideoAsset::query()->where('bunny_video_guid', $guid)->cursor() as $asset) {
                $this->bunnyStream->hydrateVideoAssetFromPayload($asset, $payload);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function log(array $context): void
    {
        Log::channel('video_migration')->info('video_migration', $context);
    }
}
