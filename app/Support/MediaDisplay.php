<?php

namespace App\Support;

/**
 * Affichage des URLs médias (CDN, chemins locaux, URLs absolues déjà complètes).
 */
class MediaDisplay
{
    public static function url(?string $url, ?string $fallback = null): string
    {
        $u = trim((string) $url);
        if ($u === '') {
            return $fallback ?? asset('images/placeholders/placeholder.svg');
        }
        if (str_starts_with($u, 'https://') || str_starts_with($u, 'http://') || str_starts_with($u, '/')) {
            return $u;
        }

        return $fallback ?? asset('images/placeholders/placeholder.svg');
    }
}
