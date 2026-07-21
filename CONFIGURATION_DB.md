# Configuration de la Base de Données

## Informations de Connexion Configurées

Configurez vos informations de connexion MySQL dans **votre fichier local** `.env` (ne jamais committer de mot de passe) :

- **Host** : `127.0.0.1`
- **Port** : `32768`
- **Database** : `talashow`
- **Username** : `shad`
- **Password** : *(à renseigner dans `.env`)*

## Fichiers Modifiés

1. **config/database.php** - Port par défaut mis à jour à `32768`
2. **.env** - Fichier de configuration créé avec vos identifiants
3. **database/create_database.sql** - Script mis à jour

## Création du Fichier .env

### Option 1 : Script Automatique (Windows)
```bash
setup-env.bat
```

### Option 2 : Script Automatique (Linux/Mac)
```bash
chmod +x setup-env.sh
./setup-env.sh
```

### Option 3 : Manuel
Créez un fichier `.env` à la racine du projet avec le contenu suivant :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=32768
DB_DATABASE=talashow
DB_USERNAME=shad
DB_PASSWORD=
```

## Vérification de la Connexion

Pour tester la connexion à la base de données :

```bash
php artisan tinker
>>> DB::connection()->getPdo();
```

Si la connexion fonctionne, vous verrez un objet PDO. Sinon, une erreur s'affichera.

## Prochaines Étapes

1. **Générer la clé d'application** :
   ```bash
   php artisan key:generate
   ```

2. **Vérifier que la base de données existe** :
   La base `talashow` doit déjà exister dans votre serveur MySQL.

3. **Exécuter les migrations** :
   ```bash
   php artisan migrate
   ```

> Optionnel (dev/local uniquement) : si tu veux seed des données de démo, active explicitement :
> - `TALASHOW_ENABLE_SEEDING=true`
> - puis `php artisan db:seed`

4. **Créer le lien symbolique** :
   ```bash
   php artisan storage:link
   ```

## Dépannage

### Erreur "Access denied"
- Vérifiez que l'utilisateur `shad` a les droits sur la base `talashow`
- Vérifiez le mot de passe dans `.env`

### Erreur "Unknown database"
- Assurez-vous que la base `talashow` existe
- Créez-la si nécessaire :
  ```sql
  CREATE DATABASE talashow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  ```

### Erreur de connexion
- Vérifiez que MySQL est démarré
- Vérifiez que le port `32768` est correct
- Testez la connexion avec Navicat ou un autre client MySQL
