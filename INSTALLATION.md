# Guide d'Installation - Talashow

## Prérequis

- PHP 8.1 ou supérieur
- Composer
- Node.js 18+ et npm
- MySQL 8.0+
- Extension PHP : pdo_mysql, mbstring, openssl, tokenizer, json, curl, fileinfo

## Installation Locale

### 1. Cloner/Extraire le projet

```bash
cd talashow
```

### 2. Installer les dépendances PHP

```bash
composer install
```

### 3. Installer les dépendances Node.js

```bash
npm install
```

### 4. Configuration

Copier le fichier `.env.example` vers `.env` :

```bash
cp .env.example .env
```

Éditer `.env` et configurer (ou utiliser le script `setup-env.bat` / `setup-env.sh`) :

```env
APP_NAME=Talashow
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=32768
DB_DATABASE=talashow
DB_USERNAME=shad
DB_PASSWORD=SDconceptsrdc@243
```

**Note** : Les informations de connexion sont déjà pré-configurées. Vous pouvez utiliser le script automatique :
- Windows : `setup-env.bat`
- Linux/Mac : `chmod +x setup-env.sh && ./setup-env.sh`

### 5. Générer la clé d'application

```bash
php artisan key:generate
```

### 6. Vérifier la base de données

La base de données `talashow` doit déjà exister dans votre serveur MySQL (port 32768).

Si elle n'existe pas, créez-la via Navicat ou MySQL :

```sql
CREATE DATABASE talashow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Ou exécuter le script SQL :

```bash
mysql -h 127.0.0.1 -P 32768 -u shad -p < database/create_database.sql
```

### 7. Exécuter les migrations

```bash
php artisan migrate
```

### 8. Créer le lien symbolique pour le stockage

```bash
php artisan storage:link
```

### 9. Compiler les assets

```bash
npm run dev
# ou pour production
npm run build
```

### 10. Lancer le serveur

```bash
php artisan serve
```

Le site sera accessible sur : http://localhost:8000

> Optionnel (dev/local uniquement) : si tu veux seed des données de démo, active explicitement :
> - `TALASHOW_ENABLE_SEEDING=true`
> - puis `php artisan db:seed`

## Configuration Stripe (Optionnel)

Pour activer les paiements :

1. Créer un compte sur https://stripe.com
2. Récupérer les clés API (mode test pour développement)
3. Ajouter dans `.env` :

```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
```

## Configuration OAuth Social (Optionnel)

### Google

1. Aller sur https://console.cloud.google.com
2. Créer un projet
3. Activer Google+ API
4. Créer des identifiants OAuth 2.0
5. Ajouter dans `.env` :

```env
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
```

### Facebook

1. Aller sur https://developers.facebook.com
2. Créer une application
3. Ajouter Facebook Login
4. Ajouter dans `.env` :

```env
FACEBOOK_CLIENT_ID=...
FACEBOOK_CLIENT_SECRET=...
```

## Structure des Dossiers

```
talashow/
├── app/                    # Code de l'application
│   ├── Http/
│   │   ├── Controllers/    # Contrôleurs
│   │   └── Middleware/     # Middleware
│   └── Models/             # Modèles Eloquent
├── database/
│   ├── migrations/         # Migrations
│   └── seeders/            # Seeders
├── public/                 # Fichiers publics
│   ├── videos/            # Vidéos (symbolic link)
│   └── images/            # Images
├── resources/
│   ├── views/             # Vues Blade
│   ├── css/               # Styles
│   └── js/                # JavaScript
└── routes/                # Routes
```

## Commandes Utiles

```bash
# Vider le cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimiser pour production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Créer un nouvel utilisateur admin
php artisan tinker
>>> $user = App\Models\User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password'), 'is_admin' => true]);
```

## Dépannage

### Erreur "Class not found"
```bash
composer dump-autoload
```

### Erreur de permissions (Linux/Mac)
```bash
chmod -R 755 storage bootstrap/cache
```

### Erreur de base de données
Vérifier que MySQL est démarré et que les identifiants dans `.env` sont corrects.

### Assets non chargés
```bash
npm run build
php artisan storage:link
```

## Support

Consulter la documentation Laravel : https://laravel.com/docs
