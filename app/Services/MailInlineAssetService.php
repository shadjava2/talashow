<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;

class MailInlineAssetService
{
    public function __construct(private SettingsService $settings)
    {
    }

    public function inlineAllImages(Email $email, string $html): string
    {
        // 1) Embed logo (special-case) then replace occurrences
        $r = $this->embedLogo($email);
        $cid = $r['cid'] ?? null;
        $logoUrl = (string) ($r['logoUrl'] ?? '');
        if ($cid) {
            $html = str_replace($logoUrl, $cid, $html);
            $html = str_replace(asset('logo.svg'), $cid, $html);
        }

        // 2) Embed all external images referenced by <img src="...">
        // Conservative regex to avoid mangling HTML.
        preg_match_all('/<img\b[^>]*\bsrc\s*=\s*([\"\'])(.*?)\1[^>]*>/i', $html, $m);
        $srcs = $m[2] ?? [];
        if (!$srcs) return $html;

        $srcs = array_values(array_unique(array_filter(array_map('trim', $srcs))));

        $totalBudget = 1_200_000; // total embedded bytes budget
        $perImageMax = 450_000;
        $maxImages = 6;
        $embedded = 0;

        foreach ($srcs as $src) {
            if ($src === '' || Str::startsWith($src, ['cid:', 'data:'])) continue;
            if (!Str::startsWith($src, ['http://', 'https://'])) continue;
            if ($cid && $src === $logoUrl) continue;
            if ($embedded >= $maxImages) continue;

            try {
                // Skip huge images quickly (best-effort, not all servers support HEAD/Content-Length)
                try {
                    $head = Http::timeout(2)->head($src);
                    $cl = (int) ($head->header('Content-Length') ?? 0);
                    if ($cl > 0 && $cl > $perImageMax) {
                        continue;
                    }
                } catch (\Throwable) {
                    // ignore
                }

                $res = Http::timeout(3)->accept('image/*')->get($src);
                if (!$res->successful()) continue;

                $ct = (string) $res->header('Content-Type');
                $mime = $ct !== '' ? trim(explode(';', $ct)[0]) : null;
                $body = $res->body();
                if (!is_string($body) || $body === '') continue;
                $len = strlen($body);
                if ($len > $perImageMax) continue;
                if (($totalBudget - $len) <= 0) continue;

                $name = 'talashow-img-' . substr(sha1($src), 0, 10);
                $newCid = $email->embed($body, $name, $mime ?? 'image/*');
                $html = str_replace($src, $newCid, $html);
                $totalBudget -= $len;
                $embedded++;
            } catch (\Throwable) {
                // ignore
            }
        }

        return $html;
    }

    /**
     * Embed the site logo into the email and return the CID string (ex: "cid:...").
     * Falls back gracefully if remote download fails.
     */
    public function embedLogo(Email $email): array
    {
        $logoUrl = (string) ($this->settings->get('site_logo_url') ?: asset('logo.svg'));

        $bytes = null;
        $mime = null;

        // Try remote fetch (logo hébergé sur CDN)
        if (Str::startsWith($logoUrl, ['http://', 'https://'])) {
            try {
                $res = Http::timeout(4)->accept('image/*')->get($logoUrl);
                if ($res->successful()) {
                    $ct = (string) $res->header('Content-Type');
                    $body = $res->body();
                    // Hard limit to avoid huge attachments
                    if (is_string($body) && strlen($body) > 0 && strlen($body) <= 600_000) {
                        $bytes = $body;
                        $mime = $ct !== '' ? trim(explode(';', $ct)[0]) : null;
                    }
                }
            } catch (\Throwable) {
                // ignore
            }
        }

        // Fallback to local asset
        if ($bytes === null) {
            $path = public_path('logo.svg');
            if (is_file($path)) {
                $bytes = file_get_contents($path) ?: null;
                $mime = 'image/svg+xml';
            }
        }

        if ($bytes === null) {
            return [
                'logoUrl' => $logoUrl,
                'cid' => null,
            ];
        }

        $cid = $email->embed($bytes, 'talashow-logo', $mime ?? 'image/*');

        return [
            'logoUrl' => $logoUrl,
            'cid' => $cid,
        ];
    }
}

