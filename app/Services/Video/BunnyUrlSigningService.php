<?php

namespace App\Services\Video;

/**
 * Signature d’URL CDN Bunny (token + expiration) lorsque activée.
 *
 * @see https://docs.bunny.net/docs/cdn-token-authentication
 */
class BunnyUrlSigningService
{
    public function maybeSignHlsUrl(string $url, ?int $expiresAtUnix = null): string
    {
        if (! config('services.bunny_stream.signed_urls')) {
            return $url;
        }

        $securityKey = (string) config('services.bunny_stream.token_security_key');
        if ($securityKey === '') {
            return $url;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return $url;
        }

        if ($expiresAtUnix !== null) {
            $expires = max(time() + 60, $expiresAtUnix);
        } else {
            $ttl = (int) config('services.bunny_stream.signed_url_ttl_seconds', 3600);
            $expires = time() + max(60, $ttl);
        }

        // Algorithme documenté Bunny : hash SHA256 sur securityKey + path + expires
        $hashable = $securityKey.$path.$expires;
        $token = hash('sha256', $hashable);

        $sep = str_contains($url, '?') ? '&' : '?';

        return $url.$sep.'token='.$token.'&expires='.$expires;
    }
}
