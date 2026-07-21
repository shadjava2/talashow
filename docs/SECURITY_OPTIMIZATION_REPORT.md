# Rapport sécurité & optimisation (Talashow)

Date de référence : généré lors de l’implémentation des missions 1–9.

## Fichiers principaux modifiés ou ajoutés

### Configuration & routes

- `.env.example` — valeurs production recommandées (`CACHE_DRIVER`, `SESSION_DRIVER`, `QUEUE_CONNECTION`, cookies sécurisés, `LOG_LEVEL`, Stripe/OAuth, `SECURITY_*`).
- `config/security.php` — CSP / HSTS.
- `config/session.php` — `same_site` via `SESSION_SAME_SITE` (déjà présent dans le squelette si applicable).
- `routes/web.php` — rate limits ciblés, routes Monitoring admin.
- `app/Providers/RouteServiceProvider.php` — définition des rate limiters nommés.
- `app/Http/Kernel.php` — `SecurityHeadersMiddleware`, `DetectAbusiveClientsMiddleware`.
- `app/Http/Middleware/VerifyCsrfToken.php` — exclusion `payment/webhook` (Stripe).
- `public/.htaccess` — deflate + expires pour assets statiques.
- `public/sw.js` — exclusions cache sensibles, bump `CACHE_NAME`.

### Contrôleurs & services

- `app/Http/Controllers/Payment/PaymentController.php` — webhook Stripe renforcé + journalisation.
- `app/Http/Controllers/Webhooks/BunnyStreamWebhookController.php` — log signature invalide.
- `app/Http/Controllers/Frontend/EpisodeController.php` — `playback_denied`, métadonnées watermark, log JSON lecture OK.
- `app/Http/Controllers/Admin/AdminAuthController.php` — verrouillage admin, audit connexion/déconnexion.
- `app/Http/Controllers/Admin/SettingsController.php` — audit changement paramètres.
- `app/Http/Controllers/Admin/UsersController.php` — audit rôle / actif / suppression.
- `app/Http/Controllers/Admin/MonitoringController.php` — **nouveau** tableau de bord.
- `app/Http/Controllers/Auth/AuthController.php` — log échec login public.
- `app/Http/Middleware/RequirePermission.php` — log refus permission.

### Modèles & audit

- `app/Models/SecurityEvent.php`, `AdminActivityLog.php`, `SystemHeartbeat.php` — **nouveaux**.
- `app/Services/SecurityAuditService.php` — **nouveau** helper central.
- `app/Support/PrivacyMask.php` — masquage email/IP pour UI watermark.
- `app/Observers/SeriesSecurityObserver.php`, `EpisodeSecurityObserver.php` — audit CRUD.
- `app/Providers/AppServiceProvider.php` — enregistrement des observers.
- `app/Models/User.php` — colonnes sécurité / 2FA masquées.
- `app/Console/Kernel.php` — heartbeat `laravel_schedule`.

### Vues

- `resources/views/admin/monitoring/index.blade.php` — **nouveau**.
- `resources/views/admin/layouts/app.blade.php` — lien Monitoring.
- `resources/views/frontend/episode/show.blade.php` — watermark + script.

### Migrations

- `2026_05_11_100000_create_laravel_queue_session_cache_tables.php`
- `2026_05_11_100100_create_security_events_table.php`
- `2026_05_11_100200_create_admin_activity_logs_table.php`
- `2026_05_11_100300_create_system_heartbeats_table.php`
- `2026_05_11_100400_add_admin_security_columns_to_users_table.php`
- `2026_05_11_100500_add_two_factor_columns_to_users_table.php`
- `2026_05_11_100600_add_monitoring_permission_and_assign_admin.php`
- `2026_05_11_100700_add_performance_indexes_talashow.php`

### Documentation

- `docs/DEPLOYMENT_PRODUCTION.md`
- `docs/CPANEL_CRON.md`
- `docs/LITESPEED_CACHE_RULES.md`
- `docs/ANTI_PIRACY_LIMITS.md`
- `docs/TWO_FACTOR_PLACEHOLDER.md`
- `docs/SECURITY_OPTIMIZATION_REPORT.md` (ce fichier)

### Seeders

- `database/seeders/RbacSeeder.php` — permission `monitoring.view` (dev).

## Commandes Artisan utiles

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan queue:restart
php artisan storage:link
```

## Crons cPanel

Voir `docs/CPANEL_CRON.md` (`schedule:run` chaque minute + `queue:work --stop-when-empty` périodique).

## Limites anti-piratage

Résumé dans `docs/ANTI_PIRACY_LIMITS.md` : pas de blocage total des captures ; mitigation = URLs signées Bunny + accès + watermark + monitoring.

## Recommandations Cloudflare

- TLS strict, HSTS déjà côté app en production.
- WAF / rate limiting au périmètre pour soulager le mutualisé.
- **Ne pas** mettre en cache les chemins admin/auth/paiement/webhooks (règles Page Rules / Cache Rules cohérentes avec `docs/LITESPEED_CACHE_RULES.md`).

## Recommandations Bunny Stream

- Activer **token authentication** / URLs signées (`BUNNY_STREAM_TOKEN_AUTH_ENABLED`, clés token, TTL court `BUNNY_STREAM_URL_EXPIRATION`).
- Garder le secret webhook et vérifier les logs `video_webhook_logs` + `security_events`.

## Checklist avant mise en production

- [ ] `.env` : `APP_DEBUG=false`, `LOG_LEVEL=warning`, `APP_URL` HTTPS.
- [ ] `php artisan migrate --force` (sessions/cache/jobs + audit).
- [ ] Builds front : `npm ci && npm run build`.
- [ ] Caches Laravel : `config:cache`, `route:cache`, `view:cache`, `event:cache`.
- [ ] Crons : `schedule:run` + file d’attente.
- [ ] Stripe : `STRIPE_WEBHOOK_SECRET` défini (sinon webhook 400 hors prod / log critique en prod).
- [ ] Bunny : clés + webhook secret ; tester lecture embed.
- [ ] Accès Monitoring avec compte admin / permission `monitoring.view`.
- [ ] Sauvegardes DB + fichiers (hébergeur hebdo → planifier export complémentaire si besoin).
