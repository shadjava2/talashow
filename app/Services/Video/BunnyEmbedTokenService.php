<?php

namespace App\Services\Video;

use Illuminate\Http\Request;

/**
 * Jetons d’accès pour l’iframe Bunny (embed), distincts du token CDN HLS.
 *
 * @see https://docs.bunny.net/stream/token-authentication
 */
class BunnyEmbedTokenService
{
    /**
     * SHA256 hex sur (token_security_key + video_id + expiration).
     */
    public function generateEmbedToken(string $videoGuid, int $expires): string
    {
        $key = (string) config('services.bunny_stream.token_security_key');

        return hash('sha256', $key.$videoGuid.$expires);
    }

    public function generateSignedEmbedUrl(string $videoGuid, ?int $expires = null, ?string $libraryIdOverride = null): string
    {
        $libraryId = $this->resolveLibraryId($libraryIdOverride);
        $base = rtrim((string) config('services.bunny_stream.embed_base', 'https://iframe.mediadelivery.net/embed'), '/');
        $url = "{$base}/{$libraryId}/{$videoGuid}";

        return $this->appendEmbedTokenQuery($url, $videoGuid, $expires);
    }

    /**
     * Lecteur page Bunny standard : /play/{libraryId}/{videoId}
     *
     * @see https://docs.bunny.net/stream/embedding
     */
    public function generateSignedPlayerUrl(string $videoGuid, ?int $expires = null, ?string $libraryIdOverride = null): string
    {
        $libraryId = $this->resolveLibraryId($libraryIdOverride);
        $base = rtrim((string) config('services.bunny_stream.player_iframe_base', 'https://player.mediadelivery.net/play'), '/');
        $url = "{$base}/{$libraryId}/{$videoGuid}";

        return $this->appendEmbedTokenQuery($url, $videoGuid, $expires);
    }

    protected function resolveLibraryId(?string $override): string
    {
        $fromOverride = trim((string) $override);

        return $fromOverride !== ''
            ? $fromOverride
            : trim((string) config('services.bunny_stream.library_id'));
    }

    protected function appendEmbedTokenQuery(string $url, string $videoGuid, ?int $expires): string
    {
        if (! filter_var((string) config('services.bunny_stream.embed_token_auth_enabled', false), FILTER_VALIDATE_BOOL)) {
            return $url;
        }

        $securityKey = (string) config('services.bunny_stream.token_security_key');
        if ($securityKey === '') {
            return $url;
        }

        $ttl = (int) config('services.bunny_stream.embed_url_expiration', 3600);
        $expiresAt = $expires ?? (time() + max(60, $ttl));
        $token = $this->generateEmbedToken($videoGuid, $expiresAt);
        $sep = str_contains($url, '?') ? '&' : '?';

        return $url.$sep.'token='.$token.'&expires='.$expiresAt;
    }

    public function verifyWebhook(Request $request, string $rawBody, string $secret): bool
    {
        if ($secret === '') {
            return true;
        }

        $sig = (string) $request->header('X-Bunny-Signature', '');
        $expected = hash_hmac('sha256', $rawBody, $secret);

        return $sig !== '' && hash_equals($expected, $sig);
    }
}
