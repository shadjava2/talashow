<?php

namespace App\Services\Video;

use App\Models\Episode;

/**
 * Extrait le GUID vidéo Bunny Stream depuis des URLs saisies en admin (lecteur /play, iframe /embed, ou chemin CDN vz-*.b-cdn.net/{guid}/…).
 */
class BunnyStreamPlaybackUrlParser
{
    private const UUID = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

    /**
     * @return array{guid: string, library_id: ?string}|null
     */
    public function parseFirstGuidFromEpisode(Episode $episode, string $legacyUrl): ?array
    {
        $candidates = [];
        foreach ([$legacyUrl, (string) $episode->video_url, (string) $episode->hls_url, (string) $episode->playback_url] as $u) {
            $u = trim($u);
            if ($u !== '') {
                $candidates[] = $u;
            }
        }
        $urls = $episode->video_urls;
        if (is_array($urls)) {
            foreach ($urls as $u) {
                $u = trim((string) $u);
                if ($u !== '') {
                    $candidates[] = $u;
                }
            }
        }
        foreach ($candidates as $url) {
            $parsed = $this->parseFromUrl($url);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return null;
    }

    /**
     * @return array{guid: string, library_id: ?string}|null
     */
    public function parseFromUrl(string $url): ?array
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (preg_match('#mediadelivery\.net/play/(\d+)/('.self::UUID.')#i', $url, $m)) {
            return ['guid' => strtolower($m[2]), 'library_id' => $m[1]];
        }

        if (preg_match('#mediadelivery\.net/embed/(\d+)/('.self::UUID.')#i', $url, $m)) {
            return ['guid' => strtolower($m[2]), 'library_id' => $m[1]];
        }

        if (preg_match('#b-cdn\.net/('.self::UUID.')(?:/|\?|$)#i', $url, $m)) {
            return ['guid' => strtolower($m[1]), 'library_id' => null];
        }

        return null;
    }
}
