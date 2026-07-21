<?php

namespace App\Services\Video;

use App\Models\VideoAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Façade production autour de Bunny Stream (API + URLs de lecture / embed).
 */
class BunnyStreamService
{
    public function __construct(
        protected BunnyApiClient $client,
        protected BunnyStreamProvider $provider,
        protected BunnyEmbedTokenService $embedTokens,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function createVideo(string $title, ?string $collectionId = null, array $options = []): array
    {
        $meta = $options;
        if ($collectionId !== null && $collectionId !== ''
            && filter_var((string) config('services.bunny_stream.collections_enabled', true), FILTER_VALIDATE_BOOL)) {
            $meta['collectionId'] = $collectionId;
        }

        return $this->provider->createVideo($title, $meta);
    }

    /**
     * @return array<string, mixed>
     */
    public function uploadVideoBinary(string $videoGuid, string $localFilePath, array $query = []): array
    {
        unset($query);

        return $this->provider->uploadVideoBinary($videoGuid, $localFilePath, []);
    }

    /**
     * @return array<string, mixed>
     */
    public function createAndUploadVideo(string $title, string $localFilePath, ?string $collectionId = null, array $options = []): array
    {
        $created = $this->createVideo($title, $collectionId, $options);
        $guid = $this->provider->guidFromCreateResponse($created);
        if ($guid === null) {
            return array_merge($created, ['_error' => 'no_guid']);
        }
        $this->uploadVideoBinary($guid, $localFilePath);

        return $this->getVideo($guid);
    }

    /**
     * @return array<string, mixed>
     */
    public function getVideo(string $videoGuid): array
    {
        return $this->provider->getVideo($videoGuid);
    }

    /**
     * @return array<string, mixed>
     */
    public function updateVideo(string $videoGuid, array $payload): array
    {
        $response = $this->client->updateVideoMetadata($videoGuid, $payload);

        return is_array($response) ? $response : [];
    }

    public function deleteVideo(string $videoGuid): bool
    {
        return $this->provider->deleteVideo($videoGuid);
    }

    public function generateEmbedUrl(string $videoGuid, bool $signed = true, array $params = []): string
    {
        unset($params);
        $style = strtolower((string) config('video.bunny_iframe_url_style', 'play'));
        if ($signed) {
            return $style === 'embed'
                ? $this->embedTokens->generateSignedEmbedUrl($videoGuid)
                : $this->embedTokens->generateSignedPlayerUrl($videoGuid);
        }

        $libraryId = trim((string) config('services.bunny_stream.library_id'));
        if ($style === 'embed') {
            $base = rtrim((string) config('services.bunny_stream.embed_base', 'https://iframe.mediadelivery.net/embed'), '/');
        } else {
            $base = rtrim((string) config('services.bunny_stream.player_iframe_base', 'https://player.mediadelivery.net/play'), '/');
        }

        return "{$base}/{$libraryId}/{$videoGuid}";
    }

    public function generatePlayUrl(string $videoGuid): string
    {
        return $this->provider->getPlaybackHlsUrl($videoGuid);
    }

    public function generateThumbnailUrl(string $videoGuid): string
    {
        try {
            $payload = $this->getVideo($videoGuid);
        } catch (\Throwable $e) {
            Log::channel('video_migration')->warning('bunny_stream_thumbnail', [
                'guid' => $videoGuid,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
        $urls = $this->provider->getPlaybackUrls($videoGuid, $payload);

        return (string) ($urls['thumbnail'] ?? '');
    }

    public function verifyWebhook(Request $request): bool
    {
        $secret = (string) config('services.bunny_stream.webhook_secret');
        $raw = $request->getContent();

        return $this->embedTokens->verifyWebhook($request, $raw, $secret);
    }

    public function mapBunnyStatusToInternal(mixed $status): string
    {
        if ($status === 0 || $status === '0') {
            return 'pending';
        }
        if ($status === 1 || $status === '1') {
            return 'uploading';
        }
        if ($status === 4 || $status === '4') {
            return 'ready';
        }

        return 'processing';
    }

    public function syncVideoMetadata(VideoAsset $videoAsset): VideoAsset
    {
        $guid = (string) $videoAsset->bunny_video_guid;
        if ($guid === '') {
            return $videoAsset;
        }

        $payload = $this->getVideo($guid);
        $this->hydrateVideoAssetFromPayload($videoAsset, $payload);

        return $videoAsset->fresh() ?? $videoAsset;
    }

    /**
     * @param  array<string, mixed>  $videoPayload
     */
    public function hydrateVideoAssetFromPayload(VideoAsset $asset, array $videoPayload): void
    {
        $guid = (string) $asset->bunny_video_guid;
        if ($guid === '') {
            return;
        }

        $urls = $this->provider->getPlaybackUrls($guid, $videoPayload);
        $asset->bunny_status = (string) ($videoPayload['status'] ?? '');
        $asset->bunny_hls_url = $urls['hls'];
        $asset->bunny_thumbnail_url = $urls['thumbnail'];
        $asset->bunny_embed_url = $this->generateEmbedUrl($guid, true, []);
        $asset->encode_progress = isset($videoPayload['encodeProgress']) ? (int) $videoPayload['encodeProgress'] : null;

        if ($this->provider->hasTranscodeFailure($videoPayload)) {
            $asset->processing_state = 'failed';
        } elseif ($this->provider->isReady($videoPayload)) {
            $asset->processing_state = 'ready';
            $asset->published_at = $asset->published_at ?? now();
        } else {
            $asset->processing_state = 'processing';
        }

        $asset->save();
    }
}
