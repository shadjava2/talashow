# Bunny Stream — intégration et migration

Ce document décrit la couche vidéo centrée sur **Bunny Stream** (plus d’appel applicatif à Cloudflare Stream).

## Variables d’environnement

Voir `.env.example` : préfixes `BUNNY_STREAM_*`, `VIDEO_MIGRATION_LOCAL_BASE`, `VIDEO_PLAYBACK_DRIVER`, `VIDEO_PROVIDER`.

Points clés :

- **VIDEO_PLAYBACK_DRIVER** : `hls` (Video.js + `playlist.m3u8`, défaut) ou `bunny_embed` (iframe `BUNNY_STREAM_EMBED_BASE` + jeton si `BUNNY_STREAM_TOKEN_AUTH_ENABLED=true`).
- **Jeton embed** : selon la [documentation Bunny](https://docs.bunny.net/stream/token-authentication), `token = SHA256_HEX(token_security_key + video_id + expires)` en query `token` et `expires` (timestamp Unix secondes).
- **HLS signé (CDN)** : distinct du jeton embed ; activé via `BUNNY_STREAM_SIGNED_URLS` et `BUNNY_STREAM_TOKEN_SECURITY_KEY` (voir `BunnyUrlSigningService`).

## Dashboard Bunny

1. Créer une **Video Library**.
2. Noter **Library ID** et **API Key** (Stream API).
3. Configurer le **CDN hostname** (pull zone / hostname de livraison HLS).
4. Optionnel : **Token authentication** sur l’embed → renseigner la même clé côté `BUNNY_STREAM_TOKEN_SECURITY_KEY` et activer `BUNNY_STREAM_TOKEN_AUTH_ENABLED=true`.
5. Webhook : URL `POST {APP_URL}/webhooks/bunny/stream`, secret = `BUNNY_STREAM_WEBHOOK_SECRET` (signature `X-Bunny-Signature` = HMAC-SHA256 du corps brut).

## Flux d’upload (serveur)

1. `POST` création vidéo sur l’API Bunny (`BunnyApiClient::createVideo`).
2. `PUT` binaire sur l’URL vidéo (`uploadVideoPut`).
3. Mise à jour du statut via webhook ou commandes `video:bunny-sync` / `video:bunny-resync-metadata`.

Upload TUS côté navigateur : non implémenté dans ce dépôt ; le point d’extension naturel est un endpoint qui crée la vidéo Bunny puis renvoie l’URL TUS / le GUID au client (voir [TUS Bunny](https://docs.bunny.net/stream/tus-resumable-uploads)).

## Flux de lecture

- **HLS** : résolution via `VideoPlaybackResolverService` → URL signée CDN si besoin.
- **Embed** : `VIDEO_PLAYBACK_DRIVER=bunny_embed` + `external_video_id` ou `target_video_guid` (mapping) ; composant Blade `x-video.bunny-player`.

## Migration depuis d’anciennes URLs

La commande `php artisan video:migrate-cloudflare-to-bunny` **ne télécharge plus** de flux distants. Elle attend des fichiers :

`{VIDEO_MIGRATION_LOCAL_BASE}/{clé}.mp4`

où `clé` est dérivée de l’URL en base (UID sur 32 caractères hex, ou nom de fichier sans extension).

- Alias : `php artisan videos:migrate-cloudflare-to-bunny`
- Options : `--dry-run`, `--chunk`, `--only-pending`, `--force`

## Commandes utiles

| Commande | Rôle |
|----------|------|
| `video:migrate-cloudflare-to-bunny` | Import MP4 locaux → Bunny + mappings |
| `video:bunny-sync` | Resynchronise épisode(s) / tous les GUID connus |
| `video:bunny-resync-metadata` | Recharge `video_assets` depuis l’API |
| `video:cleanup-legacy-config` | Liste les clés `.env` Cloudflare à retirer |

## Tables

- `video_provider_mappings` : migration par langue / épisode.
- `video_assets` : actifs polymorphes (films, épisodes, etc.) — optionnel selon usage métier.
- `video_webhook_logs` : journal des webhooks Bunny.

## Dépannage

- **401 webhook** : vérifier `BUNNY_STREAM_WEBHOOK_SECRET` et l’en-tête `X-Bunny-Signature` (corps brut exact).
- **Embed refusé** : vérifier `BUNNY_STREAM_TOKEN_AUTH_ENABLED`, l’horloge serveur (`expires`) et la clé de sécurité Bunny.
- **Migration « fichier introuvable »** : vérifier `VIDEO_MIGRATION_LOCAL_BASE` et le nom `{clé}.mp4`.
