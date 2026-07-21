# Migration Cloudflare Stream → Bunny Stream (Talashow)

Ce document décrit la bascule progressive, les variables d’environnement, les commandes Artisan et le rollback.

## Configuration depuis l’admin (recommandé en prod)

Dans **Talashow Admin → Paramètres**, la section **Bunny Stream** reprend les mêmes idées que Cloudflare (valeurs **surchargent le `.env`** si elles sont renseignées) :

| Champ admin | Équivalent Bunny (dash.bunny.net) |
|-------------|-----------------------------------|
| **Library ID** | *Stream* → ta *Video library* → identifiant numérique |
| **CDN hostname** | Hostname de diffusion HLS (souvent `vz-xxxx.b-cdn.net`), **sans** `https://` |
| **Stream API key** | Clé API de la library (header `AccessKey` vers `video.bunnycdn.com`) |
| **Lecture principale** | `cloudflare` ou `bunny` → même rôle que `VIDEO_PROVIDER` |
| **Secret webhook** | Pour sécuriser `POST /webhooks/bunny/stream` (HMAC) |
| **Token security key** | Si **URLs HLS signées** = oui (token CDN Bunny) |

**Images** : les champs *Cloudflare Images* restent utiles pour posters / covers tant que vous ne basculez pas les images vers un autre produit (ex. Bunny Optimizer).

---

## Variables `.env` (principales)

### Pilotage lecture

| Variable | Rôle |
|----------|------|
| `VIDEO_PROVIDER` | `cloudflare` ou `bunny` — fournisseur **prioritaire** pour la résolution HLS. |
| `VIDEO_FALLBACK_PROVIDER` | Réservé / documenté pour évolutions (actuellement le fallback Cloudflare est implicite si Bunny n’est pas prêt). |
| `VIDEO_MIGRATION_LOCAL_BASE` | Dossier optionnel contenant `{cloudflare_uid}.mp4` pour la migration sans re-téléchargement depuis Cloudflare. |

### Bunny Stream

| Variable | Rôle |
|----------|------|
| `BUNNY_STREAM_LIBRARY_ID` | ID numérique de la Video Library |
| `BUNNY_STREAM_API_KEY` | Clé API (header `AccessKey`) |
| `BUNNY_STREAM_CDN_HOSTNAME` | Hostname pull zone / CDN (ex. `vz-xxxxx.b-cdn.net`, **sans** `https://`) |
| `BUNNY_STREAM_SIGNED_URLS` | `true` / `false` — active la signature des URLs HLS |
| `BUNNY_STREAM_TOKEN_KEY` | Référence token (si requis par votre config CDN) |
| `BUNNY_STREAM_TOKEN_SECURITY_KEY` | Clé de sécurité pour le hash des tokens |
| `BUNNY_STREAM_WEBHOOK_SECRET` | Secret HMAC (header `X-Bunny-Signature` = `hash_hmac('sha256', raw_body, secret)`) |
| `BUNNY_STREAM_UPLOAD_TIMEOUT` | Timeout upload PUT (secondes, défaut 3600) |
| `BUNNY_STREAM_SIGNED_URL_TTL` | TTL des URLs signées (secondes) |

### Cloudflare Stream (aliases + rétro-compat)

Les clés `CLOUDFLARE_STREAM_*` sont lues en priorité, avec repli sur `CLOUDFLARE_ACCOUNT_ID`, `CLOUDFLARE_API_TOKEN`, `CLOUDFLARE_STREAM_URL`.

- `CLOUDFLARE_STREAM_ACCOUNT_ID`
- `CLOUDFLARE_STREAM_API_TOKEN`
- `CLOUDFLARE_STREAM_URL` (URL customer complète)
- `CLOUDFLARE_STREAM_SUBDOMAIN` (alternative : construit `https://{sub}.cloudflarestream.com`)
- `CLOUDFLARE_STREAM_CUSTOMER_CODE` (alternative : `https://customer-{code}.cloudflarestream.com`)
- `CLOUDFLARE_STREAM_SIGNING_KEY` (référence future / signed URLs CF)

## Architecture ajoutée

- **Interface** `App\Services\Video\VideoProviderInterface`
- **Providers** : `CloudflareStreamProvider`, `BunnyStreamProvider`
- **Client HTTP** : `BunnyApiClient` (base `https://video.bunnycdn.com`, retries, timeouts upload)
- **Résolution lecture** : `VideoPlaybackResolverService` (Bunny si `VIDEO_PROVIDER=bunny` + mapping `ready`, sinon URL Cloudflare d’origine)
- **Migration** : `VideoMigrationService` + table `video_provider_mappings`
- **Signature CDN** : `BunnyUrlSigningService` (si `BUNNY_STREAM_SIGNED_URLS=true`)

## Migrations SQL

1. `video_provider_mappings` — suivi par épisode + langue (`video_id` = `episodes.id`, `video_lang`).
2. Colonnes `episodes` : `video_provider`, `external_video_id`, `playback_url`, `hls_url`, `video_status`.

```bash
php artisan migrate
```

## Webhook Bunny

- **URL** : `POST /webhooks/bunny/stream`
- **CSRF** : exclu via `VerifyCsrfToken` (`webhooks/bunny/*`)
- Si `BUNNY_STREAM_WEBHOOK_SECRET` est défini, envoyer le header `X-Bunny-Signature` (HMAC-SHA256 du corps brut).

## Commandes Artisan

```bash
# Migration par lot (stream MP4 Cloudflare → Bunny, sans charger tout le fichier en mémoire)
php artisan videos:migrate-cloudflare-to-bunny --chunk=25 --only-pending --force

# Synchroniser les statuts Bunny (processing → ready)
php artisan videos:sync-bunny-status --only-processing
```

Options :

- `--only-pending` : ignore les mappings déjà `ready`.
- `--force` : remet les mappings en `failed` à `pending` (sans `target_video_guid`) pour relancer.
- Si un `target_video_guid` existe déjà, la commande de migration appelle la synchro API au lieu de recréer une vidéo Bunny (**idempotence**).

## Ordre d’exécution recommandé

1. Renseigner Bunny + conserver Cloudflare actif.
2. `php artisan migrate`
3. `VIDEO_PROVIDER=cloudflare` (ou laisser par défaut) — valider que rien ne casse.
4. Lancer `videos:migrate-cloudflare-to-bunny` par petits `--chunk`.
5. Planifier `videos:sync-bunny-status` en cron (ex. toutes les 5 minutes) pendant la transition.
6. Configurer le webhook Bunny vers `/webhooks/bunny/stream`.
7. Quand un volume suffisant est `ready`, passer `VIDEO_PROVIDER=bunny`.
8. Surveiller `storage/logs/video_migration-*.log`.

## Stratégie de rollback

1. Remettre `VIDEO_PROVIDER=cloudflare` dans `.env` (rechargement PHP-FPM / worker).
2. Les URLs Cloudflare restent dans `video_urls` / `source_playback_url` tant que vous ne les avez pas écrasées manuellement.
3. Si des épisodes ont été mis à jour avec des HLS Bunny uniquement, restaurer une sauvegarde BDD ou réinjecter les URLs Cloudflare depuis l’admin.

## Check-list post-déploiement

- [ ] `BUNNY_STREAM_*` et `CLOUDFLARE_*` corrects en prod
- [ ] Cron `videos:sync-bunny-status`
- [ ] Webhook Bunny joignable (HTTPS public)
- [ ] Logs `video_migration` rotatifs / monitoring
- [ ] Lecture test FR/EN (multi-langues `video_urls`)
- [ ] Upload admin avec `VIDEO_PROVIDER=bunny`

## Pièges Safari / HLS / CORS

- **CORS** : autoriser l’origine du site sur la pull zone Bunny (si politique stricte).
- **Safari** : HLS natif pour `.m3u8` ; vérifier certificats et chaîne TLS sur le CDN.
- **Signed URLs** : le token doit couvrir le manifest **et** les segments ; vérifier le chemin signé (path) selon la doc Bunny pour votre zone.
- **Mixed content** : HLS en HTTPS si le site est en HTTPS.

## Bascule finale

1. Finaliser les migrations `ready` (sync + spot-check lecture).
2. `VIDEO_PROVIDER=bunny` + déploiement.
3. Garder les credentials Cloudflare quelques semaines pour secours.
4. Nettoyage optionnel des assets Cloudflare après validation métier.

## Tests PHPUnit

Les tests sous `tests/Feature` et `tests/Unit` liés à Bunny utilisent `RefreshDatabase`. Pour une base SQLite en mémoire, décommentez dans `phpunit.xml` :

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

Puis : `php vendor/bin/phpunit` (ou équivalent après `composer install`).
