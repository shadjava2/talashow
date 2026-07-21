# Guide de Déploiement cPanel

## Prérequis

1. Compte cPanel avec accès SSH (recommandé)
2. PHP 8.1 ou supérieur
3. MySQL 8.0 ou supérieur
4. Composer installé
5. Node.js et npm (pour build des assets)

## Étapes de Déploiement

### 1. Préparation Locale

```bash
# Installer les dépendances
composer install --optimize-autoloader --no-dev
npm install
npm run build

# Générer la clé d'application
php artisan key:generate

# Optimiser l'application
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. Upload des Fichiers

Via FTP ou File Manager de cPanel, uploader tous les fichiers sauf :
- `.env` (à créer sur le serveur)
- `node_modules/`
- `.git/`
- `storage/logs/*` (garder le dossier vide)

### 3. Configuration sur cPanel

#### Créer la Base de Données

1. Aller dans "MySQL Databases"
2. Créer une nouvelle base de données : `talashow`
3. Créer un utilisateur MySQL
4. Accorder tous les privilèges à l'utilisateur
5. Noter les identifiants

#### Configurer le Fichier .env

Créer un fichier `.env` dans le répertoire racine avec :

```env
APP_NAME=Talashow
APP_ENV=production
APP_KEY=base64:VOTRE_CLE_GENEREE
APP_DEBUG=false
APP_URL=https://votre-domaine.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=talashow
DB_USERNAME=votre_utilisateur
DB_PASSWORD=votre_mot_de_passe

# PayPal (géré depuis le backoffice -> Paramètres)
# Tu peux laisser vide ici et tout configurer après déploiement.
# paypal_client_id / paypal_client_secret / paypal_mode / paypal_currency

# OAuth (optionnel)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
```

#### Configurer les Permissions

Via SSH ou File Manager, définir les permissions :

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### Cas cPanel fréquent : `public/` dans le dossier du domaine, code Laravel dans un autre dossier

Si tu veux un layout comme :

- `tala-show.com/` → **DocumentRoot** (public web)
- `talashow/` → **projet Laravel** (app, vendor, storage…)

Alors tu dois :

1. Uploader **tout le projet Laravel** dans `talashow/`
2. Copier le contenu de `talashow/public/` vers `tala-show.com/`
3. Dans `tala-show.com/index.php`, modifier les chemins `vendor` et `bootstrap` pour pointer vers `../talashow/` :

```php
require __DIR__ . '/../talashow/vendor/autoload.php';
$app = require_once __DIR__ . '/../talashow/bootstrap/app.php';
```

4. Copier aussi `talashow/public/.htaccess` vers `tala-show.com/.htaccess`
5. Lancer ensuite les commandes artisan depuis le dossier `talashow/` (pas depuis le dossier public)

### 4. Exécuter les Migrations

Via SSH ou Terminal de cPanel :

```bash
cd talashow  # exécuter depuis le dossier du projet Laravel (pas le DocumentRoot)
php artisan migrate --force
```

> Note importante : **ne pas exécuter** `db:seed` en production.
> Le projet est conçu pour que les **données existantes** (réglages, contenus, comptes) ne soient jamais écrasées.

### 5. Créer le Lien Symbolique

```bash
php artisan storage:link
```

### 6. Configuration Apache (.htaccess)

Le fichier `.htaccess` est déjà inclus. Vérifier que `mod_rewrite` est activé.

### 7. Configuration PHP

Dans cPanel, aller dans "Select PHP Version" et :
- Choisir PHP 8.1 ou supérieur
- Activer les extensions : `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `json`, `curl`, `fileinfo`

### 8. Optimisations Finales

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## Configuration SSL

1. Installer un certificat SSL via cPanel (Let's Encrypt gratuit)
2. Forcer HTTPS dans `.env` : `APP_URL=https://votre-domaine.com`
3. Rediriger HTTP vers HTTPS dans `.htaccess`

## Services de Streaming Vidéo

### Option 1 : Cloudflare Stream (Recommandé pour début)

1. Créer un compte sur Cloudflare
2. Activer Cloudflare Stream
3. Uploader les vidéos via l'interface
4. Utiliser les URLs de streaming dans `video_url`

### Option 2 : Bunny.net (Économique)

1. Créer un compte sur Bunny.net
2. Créer une Pull Zone
3. Uploader les vidéos via FTP ou API
4. Configurer le CDN

### Option 3 : AWS S3 + CloudFront (Scalable)

1. Créer un bucket S3
2. Configurer CloudFront
3. Uploader les vidéos via AWS CLI ou SDK
4. Utiliser les URLs CloudFront

## Maintenance

### Mises à jour

```bash
composer update
php artisan migrate
php artisan optimize
```

### Logs

Les logs sont dans `storage/logs/laravel.log`

### Backups

Configurer des backups automatiques de :
- Base de données MySQL
- Dossier `storage/`
- Fichier `.env`

## Support

Pour toute question, consulter la documentation Laravel : https://laravel.com/docs
