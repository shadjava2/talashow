## Déploiement cPanel — public dans `tala-show.com/` et code dans `talashow/`

Tu as indiqué vouloir :

- Mettre le **dossier public** dans `tala-show.com/`
- Mettre **le reste du projet Laravel** dans `talashow/`

### Structure recommandée

- `/home/<cpanel_user>/talashow/`  → racine Laravel (app/, bootstrap/, config/, vendor/, storage/, …)
- `/home/<cpanel_user>/tala-show.com/` → DocumentRoot (index.php, .htaccess, assets)

### Étapes

1. **Uploader** tout le projet dans `/home/<cpanel_user>/talashow/`
2. **Copier** le contenu de `/home/<cpanel_user>/talashow/public/` vers `/home/<cpanel_user>/tala-show.com/`
3. Dans `/home/<cpanel_user>/tala-show.com/index.php`, remplacer les chemins :

```php
require __DIR__ . '/../talashow/vendor/autoload.php';
$app = require_once __DIR__ . '/../talashow/bootstrap/app.php';
```

4. Copier `/home/<cpanel_user>/talashow/public/.htaccess` vers `/home/<cpanel_user>/tala-show.com/.htaccess`
5. Créer `/home/<cpanel_user>/talashow/.env` et renseigner au minimum :

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tala-show.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=<cpanel_user>_talashow
DB_USERNAME=<cpanel_user>_talashow
DB_PASSWORD=********
```

6. Depuis cPanel Terminal/SSH :

```bash
cd ~/talashow
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
```

> Note : ne lance pas `db:seed` en production (cPanel). Le projet doit conserver tes données déjà configurées.

### Configuration “sans mise à jour code”

Ensuite, tu configures dans le backoffice (DB) :
- Cloudflare Images/Stream
- PayPal
- Tarifs
- Liens sociaux + email/téléphone + pages légales

