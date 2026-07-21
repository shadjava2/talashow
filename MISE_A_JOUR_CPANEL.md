# Mise à jour Talashow sur cPanel

Procédure pour déployer les dernières modifications (code, routes, assets) sur un site déjà en production sur cPanel.

---

## Lot actuel (langue FR/EN + admin 1ʳᵉ position + design)

### 1. En local (avant upload)

```bash
cd c:\Users\HP\Documents\VSDEV\talashow
npm run build
```

### 2. Fichiers à uploader (FTP / File Manager)

**Ne pas écraser** le `.env` du serveur.  
**Ne pas uploader** `bootstrap/cache/config.php` ni `bootstrap/cache/routes-v7.php` (risque chemins Windows → 500).

| Chemin local | Obligatoire |
|--------------|-------------|
| `public/build/` (tout le dossier) | Oui (CSS/JS) |
| `public/sw.js` | Oui (fix cache langue) |
| `app/Http/Middleware/SetLocale.php` | Oui (i18n) |
| `app/Http/Middleware/EncryptCookies.php` | Oui (i18n) |
| `routes/web.php` | Oui (lang + promote admin) |
| `app/Http/Controllers/Admin/AdminController.php` | Oui (1ʳᵉ position) |
| `app/Models/Series.php` | Oui (ordre épisodes) |
| `resources/views/admin/series/index.blade.php` | Oui |
| `resources/views/admin/series/create.blade.php` | Oui |
| `resources/views/admin/series/edit.blade.php` | Oui |
| `resources/views/admin/episodes/index.blade.php` | Oui |
| `resources/views/admin/episodes/edit.blade.php` | Oui |
| `resources/views/layouts/app.blade.php` | Oui (chrome dark) |
| `resources/views/components/layout/ambient-bg.blade.php` | Oui |
| `resources/views/frontend/home.blade.php` | Recommandé |
| `resources/views/frontend/browse.blade.php` | Recommandé |
| `resources/css/app.css` | Seulement si tu rebuild sur le serveur |
| `resources/js/app.js` | Seulement si tu rebuild sur le serveur |
| `resources/css/navigation-stable.css` | Seulement si tu rebuild sur le serveur |

Si DocumentRoot = dossier du domaine (pas Laravel) : recopier aussi le contenu de `public/` (surtout `build/` + `sw.js`) vers `public_html/` (ou le dossier du domaine).

### 3. Sur cPanel (Terminal / SSH)

```bash
cd ~/talashow
# adapter le chemin selon ta structure

# Si un mauvais cache Windows a déjà été uploadé :
rm -f bootstrap/cache/config.php bootstrap/cache/routes-v7.php

php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Pas de migrate nécessaire pour ce lot (sauf si tu as d'autres migrations en attente)
# php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4. Après déploiement (navigateur)

1. Hard refresh : `Ctrl+Shift+R` (ou vider le cache du site).
2. Tester langue : bascule FR → EN (menus doivent passer en anglais).
3. Tester admin : Séries → bouton **1ʳᵉ position**, puis Épisodes → **1ʳᵉ position**.
4. Si la langue colle encore : DevTools → Application → Service Workers → Unregister, puis recharger.

---

## Option A : Mise à jour via FTP / File Manager

### 1. En local (avant d’uploader)

```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

### 2. Fichiers à envoyer sur le serveur

- **Tout le projet** dans le dossier Laravel (ex. `talashow/`), **sauf** :
  - `.env` → ne pas écraser (garder celui du serveur)
  - `node_modules/` → ne pas uploader
  - `.git/` → optionnel
  - `storage/logs/*` → ne pas écraser les logs existants
  - `bootstrap/cache/*.php` générés en local → **ne pas uploader**

- Si tu utilises le layout **DocumentRoot séparé** (domaine = dossier public uniquement) :
  - Copier le **contenu** de `public/` (index.php, .htaccess, css, js, images, `build/`, `sw.js`, etc.) vers le dossier du domaine (ex. `tala-show.com/` ou `public_html/`).

### 3. Sur cPanel (Terminal ou SSH)

Se placer dans le dossier du projet Laravel (pas le DocumentRoot) :

```bash
cd ~/talashow
# ou : cd ~/public_html/talashow  (selon ta structure)
```

Puis :

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## Option B : Mise à jour via Git (si Git est installé sur cPanel)

```bash
cd ~/talashow
git pull origin main
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

Ensuite, si ton site utilise un DocumentRoot séparé, copier le contenu de `talashow/public/` vers le dossier du domaine (index.php, .htaccess, build assets).

---

## Rappels importants

| Action | À faire / À ne pas faire |
|--------|---------------------------|
| `.env` | Ne pas remplacer par la version locale ; garder celle du serveur. |
| `php artisan migrate` | Toujours avec `--force` en production. |
| `db:seed` | Ne pas lancer en production (pour ne pas écraser les réglages / données). |
| Cache | Après chaque déploiement : `config:cache`, `route:cache`, `view:cache`, `optimize`. |
| Permissions | Si erreurs 500 : `chmod -R 755 storage bootstrap/cache`. |

---

## En cas d’erreur après mise à jour

1. Vider les caches :  
   `php artisan optimize:clear`
2. Vérifier les logs :  
   `storage/logs/laravel.log`
3. Vérifier `.env` (APP_KEY, DB_*, APP_URL) et que le DocumentRoot pointe bien vers le bon `index.php` et `../talashow/` si layout séparé.

### `Connection refused` (MySQL)

- Vérifier dans `~/talashow/.env` : `DB_HOST=localhost`, `DB_DATABASE=talashow_talashow` (nom **exact** créé dans cPanel → MySQL), utilisateur et mot de passe cPanel.
- L’erreur qui cite `table_schema = 'talashow'` indique souvent un **mauvais nom de base** (il manque le préfixe `talashow_`).
- Tester : `php artisan db:show` ou une page du site après `php artisan config:clear`.

### Chemin Windows `C:\Users\HP\Documents\VSDEV\talashow\...`

- Cause : fichier **`bootstrap/cache/config.php`** (ou `routes-v7.php`) généré **en local sur Windows** puis envoyé sur le serveur Linux.
- **Ne jamais uploader** `bootstrap/cache/config.php` ni `routes-v7.php` depuis le PC.
- Sur le serveur :

```bash
cd ~/talashow
rm -f bootstrap/cache/config.php bootstrap/cache/routes-v7.php
php artisan optimize:clear
php artisan config:clear
# puis seulement après que la DB répond :
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
