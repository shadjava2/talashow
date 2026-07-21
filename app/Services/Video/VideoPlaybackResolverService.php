<?php

namespace App\Services\Video;

use App\Models\Episode;
use App\Models\VideoProviderMapping;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

class VideoPlaybackResolverService
{
    public function __construct(
        protected BunnyUrlSigningService $signing,
        protected BunnyEmbedTokenService $embedTokens,
        protected BunnyStreamPlaybackUrlParser $playbackUrlParser,
    ) {}

    /**
     * @return array{
     *     provider: string,
     *     mode: string,
     *     hls_url: string,
     *     embed_url: string,
     *     poster: ?string,
     *     subtitles: array<int, mixed>,
     *     is_ready: bool
     * }
     */
    public function resolve(Episode $episode, string $lang, string $legacyHlsUrl, ?string $posterUrl = null, ?int $bunnyExpiresAtUnix = null): array
    {
        $bunnyExpires = $bunnyExpiresAtUnix ?? App::make(VideoSecurityService::class)->bunnyExpiresAtUnix();

        $langNorm = strtolower(trim($lang));
        $defaultProvider = (string) config('video.default_provider', 'bunny');
        $playbackDriver = (string) config('video.playback_driver', 'hls');

        $mapping = null;
        if (Schema::hasTable('video_provider_mappings')) {
            $mapping = VideoProviderMapping::query()
                ->where('video_id', $episode->id)
                ->where('video_lang', $langNorm)
                ->first();
        }

        $bunnyReady = $mapping
            && $mapping->migration_status === VideoProviderMapping::STATUS_READY
            && is_string($mapping->target_hls_url)
            && $mapping->target_hls_url !== '';

        $poster = $posterUrl ?? $episode->thumbnail;

        $guid = $this->resolveBunnyVideoGuid($episode, $mapping);
        $embedLibraryOverride = null;
        $guidFromParsedUrl = false;

        if ($guid === '' && $playbackDriver === 'bunny_embed') {
            $parsed = $this->playbackUrlParser->parseFirstGuidFromEpisode($episode, $legacyHlsUrl);
            if ($parsed !== null) {
                $guid = $parsed['guid'];
                $embedLibraryOverride = $parsed['library_id'];
                $guidFromParsedUrl = true;
            }
        }

        $embedMode = $playbackDriver === 'bunny_embed' && $guid !== '';

        if ($embedMode) {
            $ready = $guidFromParsedUrl || $this->resolveBunnyReady($episode, $mapping, $bunnyReady);
            $hls = '';
            if ($bunnyReady && $mapping) {
                $hls = $this->signing->maybeSignHlsUrl((string) $mapping->target_hls_url, $bunnyExpires);
            } elseif ($this->isBunnyNativeEpisode($episode)) {
                $raw = (string) ($episode->hls_url ?: $episode->playback_url ?: $episode->video_url ?: '');
                if ($raw !== '') {
                    $hls = $this->signing->maybeSignHlsUrl($raw, $bunnyExpires);
                }
            }

            $iframeStyle = strtolower((string) config('video.bunny_iframe_url_style', 'play'));
            $iframeUrl = $iframeStyle === 'embed'
                ? $this->embedTokens->generateSignedEmbedUrl($guid, $bunnyExpires, $embedLibraryOverride)
                : $this->embedTokens->generateSignedPlayerUrl($guid, $bunnyExpires, $embedLibraryOverride);

            return [
                'provider' => 'bunny',
                'mode' => 'embed',
                'hls_url' => $hls,
                'embed_url' => $iframeUrl,
                'poster' => $poster,
                'subtitles' => [],
                'is_ready' => $ready,
            ];
        }

        if ($defaultProvider === 'bunny' && $bunnyReady) {
            $hls = $this->signing->maybeSignHlsUrl((string) $mapping->target_hls_url, $bunnyExpires);

            return [
                'provider' => 'bunny',
                'mode' => 'hls',
                'hls_url' => $hls,
                'embed_url' => '',
                'poster' => $poster,
                'subtitles' => [],
                'is_ready' => true,
            ];
        }

        $isBunnyNative = $this->isBunnyNativeEpisode($episode);

        if ($defaultProvider === 'bunny' && $isBunnyNative) {
            $hls = (string) ($episode->hls_url ?: $episode->playback_url ?: $episode->video_url ?: '');
            if ($hls !== '') {
                $hls = $this->signing->maybeSignHlsUrl($hls, $bunnyExpires);
            }
            $ready = $this->episodeBunnyLooksReady($episode);

            return [
                'provider' => 'bunny',
                'mode' => 'hls',
                'hls_url' => $hls,
                'embed_url' => $guid !== '' ? $this->signedBunnyIframeUrl($guid, $bunnyExpires) : '',
                'poster' => $poster,
                'subtitles' => [],
                'is_ready' => $ready && $hls !== '',
            ];
        }

        $hls = $legacyHlsUrl;
        if ($hls === '') {
            $hls = (string) ($episode->hls_url ?: $episode->playback_url ?: $episode->video_url ?: '');
        }

        return [
            'provider' => 'hls',
            'mode' => 'hls',
            'hls_url' => $hls,
            'embed_url' => '',
            'poster' => $poster,
            'subtitles' => [],
            'is_ready' => $hls !== '',
        ];
    }

    protected function signedBunnyIframeUrl(string $guid, ?int $bunnyExpiresAtUnix = null): string
    {
        $iframeStyle = strtolower((string) config('video.bunny_iframe_url_style', 'play'));

        $bunnyExpires = $bunnyExpiresAtUnix ?? App::make(VideoSecurityService::class)->bunnyExpiresAtUnix();

        return $iframeStyle === 'embed'
            ? $this->embedTokens->generateSignedEmbedUrl($guid, $bunnyExpires)
            : $this->embedTokens->generateSignedPlayerUrl($guid, $bunnyExpires);
    }

    protected function isBunnyNativeEpisode(Episode $episode): bool
    {
        return in_array($episode->video_provider, ['bunny', 'bunny_stream'], true)
            || in_array($episode->video_type, ['bunny', 'bunny_stream'], true);
    }

    protected function resolveBunnyVideoGuid(Episode $episode, ?VideoProviderMapping $mapping): string
    {
        if ($mapping && is_string($mapping->target_video_guid) && $mapping->target_video_guid !== '') {
            return (string) $mapping->target_video_guid;
        }

        if ($this->isBunnyNativeEpisode($episode) && is_string($episode->external_video_id) && $episode->external_video_id !== '') {
            return (string) $episode->external_video_id;
        }

        return '';
    }

    protected function resolveBunnyReady(Episode $episode, ?VideoProviderMapping $mapping, bool $bunnyReady): bool
    {
        if ($bunnyReady) {
            return true;
        }

        return $this->isBunnyNativeEpisode($episode) && $this->episodeBunnyLooksReady($episode);
    }

    protected function episodeBunnyLooksReady(Episode $episode): bool
    {
        $st = strtolower((string) ($episode->video_status ?? ''));

        return in_array($st, ['ready', 'finished', '4', 'complete', 'completed'], true);
    }
}
