<?php

namespace App\Services\Video;

use App\Services\Video\Exceptions\BunnyApiException;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\UploadedFile;

class BunnyStreamProvider implements VideoProviderInterface
{
    public function __construct(
        protected BunnyApiClient $client
    ) {}

    public function identifier(): string
    {
        return 'bunny';
    }

    protected function cdnHostname(): string
    {
        $host = (string) config('services.bunny_stream.cdn_hostname');
        $host = trim($host);
        $host = preg_replace('#^https?://#i', '', $host) ?? $host;
        $host = rtrim($host, '/');

        return $host;
    }

    /**
     * @return array<string, mixed>
     */
    public function createVideo(string $title, array $meta = []): array
    {
        return $this->client->createVideo($title, $meta);
    }

    /**
     * @return array<string, mixed>
     */
    public function uploadVideoBinary(string $videoGuid, string $localPath, array $options = []): array
    {
        $fh = fopen($localPath, 'rb');
        if ($fh === false) {
            throw new \RuntimeException('Impossible de lire le fichier vidéo: '.$localPath);
        }
        try {
            return $this->uploadFromStream($videoGuid, $fh, $options);
        } finally {
            if (is_resource($fh)) {
                fclose($fh);
            }
        }
    }

    /**
     * @param  resource  $streamResource
     * @return array<string, mixed>
     */
    public function uploadFromStream(string $videoGuid, $streamResource, array $options = []): array
    {
        $contentType = $options['content_type'] ?? 'application/octet-stream';
        $stream = Utils::streamFor($streamResource);
        $this->client->uploadVideoPut($videoGuid, $stream, $contentType);

        return $this->getVideo($videoGuid);
    }

    /**
     * @return array<string, mixed>
     */
    public function getVideo(string $videoGuid): array
    {
        try {
            return $this->client->getVideo($videoGuid);
        } catch (BunnyApiException $e) {
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>|null  $existingPayload  Réponse `getVideo` déjà chargée (évite un appel API doublon).
     * @return array{hls: string, thumbnail: ?string, raw: array<string, mixed>}
     */
    public function getPlaybackUrls(string $videoGuid, ?array $existingPayload = null): array
    {
        $payload = $existingPayload ?? $this->getVideo($videoGuid);
        $host = $this->cdnHostname();
        $hls = $host !== ''
            ? 'https://'.$host.'/'.$videoGuid.'/playlist.m3u8'
            : '';

        $thumbName = $payload['thumbnailFileName'] ?? $payload['thumbnailFilename'] ?? null;
        $thumb = null;
        if (is_string($thumbName) && $thumbName !== '' && $host !== '') {
            $thumb = 'https://'.$host.'/'.$videoGuid.'/'.$thumbName;
        }

        return [
            'hls' => $hls,
            'thumbnail' => $thumb,
            'raw' => $payload,
        ];
    }

    public function deleteVideo(string $videoGuid): bool
    {
        return $this->client->deleteVideo($videoGuid);
    }

    public function isReady(array $videoPayload): bool
    {
        $status = $videoPayload['status'] ?? null;
        if (is_numeric($status) && (int) $status === 4) {
            return true;
        }
        $progress = $videoPayload['encodeProgress'] ?? null;
        if (is_numeric($progress) && (int) $progress >= 100) {
            return true;
        }

        return false;
    }

    public function getStatus(array $videoPayload): string
    {
        if ($this->looksFailed($videoPayload)) {
            return 'failed';
        }
        if ($this->isReady($videoPayload)) {
            return 'ready';
        }
        $status = $videoPayload['status'] ?? null;
        if ($status === 0 || $status === '0') {
            return 'pending';
        }
        if ($status === 1 || $status === '1') {
            return 'uploading';
        }

        return 'processing';
    }

    /**
     * @param  array<string, mixed>  $videoPayload
     */
    protected function looksFailed(array $videoPayload): bool
    {
        $messages = $videoPayload['transcodingMessages'] ?? [];
        if (! is_array($messages) || $messages === []) {
            return false;
        }
        foreach ($messages as $m) {
            if (is_array($m) && (($m['severity'] ?? null) === 3 || ($m['type'] ?? null) === 'Error')) {
                return true;
            }
        }

        return false;
    }

    public function uploadFromFile(UploadedFile $videoFile, array $metadata = []): array
    {
        try {
            $title = $metadata['title'] ?? $videoFile->getClientOriginalName() ?: 'upload';
            $meta = $metadata['bunny'] ?? [];
            $created = $this->createVideo((string) $title, $meta);
            $guid = $this->extractGuid($created);
            if ($guid === null) {
                return ['success' => false, 'error' => 'Réponse Bunny sans guid'];
            }
            $this->uploadVideoBinary($guid, $videoFile->getRealPath(), [
                'content_type' => $videoFile->getMimeType() ?: 'video/mp4',
            ]);
            $info = $this->getVideo($guid);
            $urls = $this->getPlaybackUrls($guid);

            return [
                'success' => true,
                'video_id' => $guid,
                'playback_url' => $urls['hls'],
                'thumbnail' => $urls['thumbnail'],
                'status' => $this->getStatus($info),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function uploadFromRemoteUrl(string $videoUrl, array $metadata = []): array
    {
        try {
            $title = $metadata['title'] ?? 'remote-import';
            $meta = $metadata['bunny'] ?? [];
            $created = $this->createVideo((string) $title, $meta);
            $guid = $this->extractGuid($created);
            if ($guid === null) {
                return ['success' => false, 'error' => 'Réponse Bunny sans guid'];
            }

            $ctx = stream_context_create([
                'http' => ['timeout' => 7200],
                'ssl' => ['verify_peer' => true],
            ]);
            $fh = @fopen($videoUrl, 'rb', false, $ctx);
            if ($fh === false) {
                return ['success' => false, 'error' => 'Impossible d’ouvrir l’URL source en lecture'];
            }
            try {
                $this->uploadFromStream($guid, $fh, ['content_type' => 'video/mp4']);
            } finally {
                if (is_resource($fh)) {
                    fclose($fh);
                }
            }

            $info = $this->getVideo($guid);
            $urls = $this->getPlaybackUrls($guid);

            return [
                'success' => true,
                'video_id' => $guid,
                'playback_url' => $urls['hls'],
                'thumbnail' => $urls['thumbnail'],
                'status' => $this->getStatus($info),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getPlaybackHlsUrl(string $externalId): string
    {
        $host = $this->cdnHostname();
        if ($host === '') {
            return '';
        }

        return 'https://'.$host.'/'.$externalId.'/playlist.m3u8';
    }

    /**
     * @param  array<string, mixed>  $created
     */
    protected function extractGuid(array $created): ?string
    {
        $guid = $created['guid'] ?? $created['videoId'] ?? $created['id'] ?? null;

        return is_string($guid) && $guid !== '' ? $guid : (is_numeric($guid) ? (string) $guid : null);
    }

    public function guidFromCreateResponse(array $created): ?string
    {
        return $this->extractGuid($created);
    }

    public function hasTranscodeFailure(array $videoPayload): bool
    {
        return $this->looksFailed($videoPayload);
    }
}
