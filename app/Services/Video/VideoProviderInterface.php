<?php

namespace App\Services\Video;

use Illuminate\Http\UploadedFile;

/**
 * Contrat commun pour l’upload / la résolution côté fournisseur de stream.
 */
interface VideoProviderInterface
{
    public function identifier(): string;

    /**
     * @return array{
     *     success: bool,
     *     video_id?: string,
     *     playback_url?: string,
     *     thumbnail?: string|null,
     *     status?: string,
     *     error?: string
     * }
     */
    public function uploadFromFile(UploadedFile $videoFile, array $metadata = []): array;

    /**
     * @return array{
     *     success: bool,
     *     video_id?: string,
     *     playback_url?: string,
     *     thumbnail?: string|null,
     *     status?: string,
     *     error?: string
     * }
     */
    public function uploadFromRemoteUrl(string $videoUrl, array $metadata = []): array;

    public function getPlaybackHlsUrl(string $externalId): string;
}
