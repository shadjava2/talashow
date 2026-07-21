# Déploiement production (Laravel / cPanel)

## Prérequis

- PHP 8.1+ avec extensions courantes (openssl, pdo_mysql, mbstring, tokenizer, xml, ctype, json, fileinfo, curl).
- Composer 2.x, Node 18+ (build Vite).
- MySQL/MariaDB.
- Tables `sessions`, `cache`, `jobs`, `failed_jobs` (migrations `2026_05_11_100000_*`).

## Variables `.env` (résumé)

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://votredomaine.tld`
- `LOG_LEVEL=warning`
- `CACHE_DRIVER=database` (ou `redis` si disponible)
- `SESSION_DRIVER=database`, `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`
- `QUEUE_CONNECTION=database`
- `SECURITY_CSP_MODE=enforce` (ou `report` pour test)

## Commandes (dans le répertoire du projet, hors Git)

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan queue:restart
```

## Après déploiement

- Configurer les crons cPanel (voir `docs/CPANEL_CRON.md`).
- Vérifier les permissions d’écriture sur `storage/` et `bootstrap/cache/`.
- Vérifier le module Monitoring (`/talashow-admin/monitoring`) avec un compte disposant de la permission `monitoring.view` (rôle admin après migration `2026_05_11_100600_*`).
