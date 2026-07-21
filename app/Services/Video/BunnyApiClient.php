<?php

namespace App\Services\Video;

use App\Services\Video\Exceptions\BunnyApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BunnyApiClient
{
    public function __construct(
        protected string $libraryId,
        protected string $accessKey,
        protected bool $verifySsl = true,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (string) config('services.bunny_stream.library_id'),
            (string) config('services.bunny_stream.api_key'),
            (bool) config('services.bunny_stream.verify_ssl', true),
        );
    }

    protected function apiRoot(): string
    {
        $base = (string) config('services.bunny_stream.api_base', 'https://video.bunnycdn.com');

        return rtrim($base, '/');
    }

    /**
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function jsonClient(int $timeout = 120)
    {
        return Http::baseUrl($this->apiRoot())
            ->withHeaders([
                'AccessKey' => $this->accessKey,
                'Accept' => 'application/json',
            ])
            ->withOptions(['verify' => $this->verifySsl])
            ->timeout($timeout)
            ->connectTimeout(30)
            ->retry(3, 500, function ($exception) {
                return $exception instanceof ConnectionException;
            }, false);
    }

    protected function logContext(string $phase, array $extra = []): array
    {
        return array_merge([
            'phase' => $phase,
            'library_id' => $this->libraryId,
        ], $extra);
    }

    /**
     * @throws BunnyApiException
     */
    protected function throwUnlessSuccessful(Response $response, string $phase): void
    {
        if ($response->successful()) {
            return;
        }

        Log::channel('video_migration')->warning('bunny_api_error', $this->logContext($phase, [
            'status' => $response->status(),
            'body' => mb_substr($response->body(), 0, 2000),
        ]));

        throw BunnyApiException::fromResponse($response);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws BunnyApiException
     */
    public function createVideo(string $title, array $meta = []): array
    {
        $payload = array_merge(['title' => $title], $meta);
        $response = $this->jsonClient()->post("/library/{$this->libraryId}/videos", $payload);
        $this->throwUnlessSuccessful($response, 'create_video');

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws BunnyApiException
     */
    public function getVideo(string $videoGuid): array
    {
        $response = $this->jsonClient()->get("/library/{$this->libraryId}/videos/{$videoGuid}");
        $this->throwUnlessSuccessful($response, 'get_video');

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws BunnyApiException
     */
    public function updateVideoMetadata(string $videoGuid, array $payload): array
    {
        $response = $this->jsonClient()->post("/library/{$this->libraryId}/videos/{$videoGuid}", $payload);
        $this->throwUnlessSuccessful($response, 'update_video');

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    public function deleteVideo(string $videoGuid): bool
    {
        $response = $this->jsonClient(60)->delete("/library/{$this->libraryId}/videos/{$videoGuid}");

        return $response->successful();
    }

    /**
     * Upload binaire (PUT). $body peut être une ressource fopen, une chaîne ou un StreamInterface.
     *
     * @param  resource|string|\Psr\Http\Message\StreamInterface  $body
     *
     * @throws BunnyApiException
     */
    public function uploadVideoPut(string $videoGuid, $body, string $contentType = 'application/octet-stream'): void
    {
        $timeout = (int) config('services.bunny_stream.upload_timeout', 3600);
        $url = $this->apiRoot()."/library/{$this->libraryId}/videos/{$videoGuid}";

        $response = Http::withHeaders([
            'AccessKey' => $this->accessKey,
            'Accept' => 'application/json',
            'Content-Type' => $contentType,
        ])
            ->withOptions(['verify' => $this->verifySsl])
            ->timeout($timeout)
            ->connectTimeout(60)
            ->retry(2, 2000, function ($exception) {
                return $exception instanceof ConnectionException;
            }, false)
            ->withBody($body, $contentType)
            ->put($url);

        $this->throwUnlessSuccessful($response, 'upload_put');
    }
}
