<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Upload vers Bunny Storage (zone) + URL publique via pull zone CDN.
 *
 * @see https://docs.bunny.net/reference/storage-api
 */
class BunnyStorageService
{
    public function isConfigured(): bool
    {
        return $this->zoneName() !== ''
            && $this->apiKey() !== ''
            && $this->cdnBaseUrl() !== '';
    }

    protected function zoneName(): string
    {
        return trim((string) config('services.bunny_storage.zone_name'));
    }

    protected function apiKey(): string
    {
        return (string) config('services.bunny_storage.api_key');
    }

    protected function region(): string
    {
        $r = strtolower(trim((string) config('services.bunny_storage.region', 'de')));

        return $r !== '' ? $r : 'de';
    }

    /**
     * Hôte API HTTP Storage (pas la pull zone CDN).
     *
     * @see https://docs.bunny.net/reference/put_-storagezonename-path-filename
     * Falkenstein (souvent choisi pour l’UE) = storage.bunnycdn.com sans préfixe — pas de.de.storage.
     */
    protected function storageApiHost(): string
    {
        $r = $this->region();

        return match ($r) {
            'de', 'fsn', 'fra', 'eu', 'falkenstein' => 'storage.bunnycdn.com',
            'ny' => 'ny.storage.bunnycdn.com',
            'la' => 'la.storage.bunnycdn.com',
            'uk', 'lon', 'london' => 'uk.storage.bunnycdn.com',
            'sg' => 'sg.storage.bunnycdn.com',
            'syd', 'sy', 'sydney' => 'syd.storage.bunnycdn.com',
            'br', 'sa' => 'br.storage.bunnycdn.com',
            'jh' => 'jh.storage.bunnycdn.com',
            'se', 'sto', 'stockholm' => 'se.storage.bunnycdn.com',
            default => $r.'.storage.bunnycdn.com',
        };
    }

    protected function cdnBaseUrl(): string
    {
        return rtrim(trim((string) config('services.bunny_storage.cdn_url')), '/');
    }

    protected function verifySsl(): bool
    {
        $v = config('services.bunny_storage.verify_ssl', true);
        if (is_bool($v)) {
            return $v;
        }

        $parsed = filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $parsed ?? true;
    }

    /**
     * @param  array<string, mixed>  $meta  Métadonnées ignorées (compat ancien upload) ; réservé au logging.
     * @return array{success: true, id: string, url: string}|array{success: false, message: string}
     */
    public function upload(UploadedFile $file, string $directory = 'media', array $meta = []): array
    {
        if (! $this->isConfigured()) {
            Log::warning('bunny_storage_not_configured');

            return ['success' => false, 'message' => 'Bunny Storage n’est pas configuré.'];
        }

        $directory = trim($directory, '/');
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if ($ext === '') {
            $ext = strtolower((string) $file->guessExtension()) ?: 'bin';
        }
        $safeName = Str::uuid()->toString().'.'.$ext;
        $remotePath = ($directory !== '' ? $directory.'/' : '').$safeName;

        $host = $this->storageApiHost();
        $pathParts = explode('/', str_replace('\\', '/', $remotePath));
        $encodedPath = implode('/', array_map('rawurlencode', $pathParts));
        $putUrl = 'https://'.$host.'/'.$this->zoneName().'/'.$encodedPath;

        try {
            $realPath = $file->getRealPath();
            if ($realPath === false || $realPath === '') {
                $realPath = $file->getPathname();
            }
            $contents = @file_get_contents($realPath);
            if ($contents === false) {
                return ['success' => false, 'message' => 'Impossible de lire le fichier sur le serveur (chemin temporaire).'];
            }
            if ($contents === '') {
                return ['success' => false, 'message' => 'Fichier vide : rien n’a été envoyé vers Bunny. Réessaie avec un autre export (PNG/JPG) ou un fichier sous 10 Mo.'];
            }

            $mime = $file->getMimeType() ?: 'application/octet-stream';
            $response = Http::withHeaders([
                'AccessKey' => $this->apiKey(),
            ])
                ->withOptions(['verify' => $this->verifySsl()])
                ->withBody($contents, $mime)
                ->timeout(120)
                ->put($putUrl);

            if (! $response->successful()) {
                Log::error('bunny_storage_upload_failed', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                    'meta' => $meta,
                    'put_host' => $host,
                ]);

                $message = $this->bunnyFailureMessage($response->status(), $response->body());

                return ['success' => false, 'message' => $message];
            }

            $publicUrl = $this->cdnBaseUrl().'/'.str_replace('\\', '/', $remotePath);

            return [
                'success' => true,
                'id' => $remotePath,
                'url' => $publicUrl,
            ];
        } catch (\Throwable $e) {
            Log::error('bunny_storage_upload_exception', ['error' => $e->getMessage(), 'meta' => $meta]);

            return ['success' => false, 'message' => 'Erreur vers Bunny : '.$e->getMessage()];
        }
    }

    protected function bunnyFailureMessage(int $status, string $body): string
    {
        $snippet = mb_substr(trim(strip_tags($body)), 0, 180);

        $base = match ($status) {
            401 => 'Bunny a refusé l’authentification (401). Utilise le mot de passe « FTP & API » exact de la zone Storage (pas la clé Stream).',
            403 => 'Accès refusé par Bunny (403). Vérifie le mot de passe API et les droits de la zone.',
            404 => 'Zone ou URL d’API incorrecte (404). Vérifie le nom de la zone, la région dans Paramètres (doit correspondre à la page FTP/API Bunny) et dans Bunny : Edge Storage → ta zone → onglet FTP & API pour l’hôte exact.',
            default => 'Bunny Storage a répondu HTTP '.$status.'.',
        };

        if ($snippet !== '') {
            $base .= ' Réponse : '.$snippet;
        }

        return $base;
    }
}
