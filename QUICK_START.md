# Guide de Démarrage Rapide - Talashow

## 🚀 Installation en 5 minutes

### 1. Installer les dépendances
```bash
composer install
npm install
```

### 2. Configurer l'environnement
**Option A : Script automatique (recommandé)**
```bash
# Windows
setup-env.bat

# Linux/Mac
chmod +x setup-env.sh && ./setup-env.sh
```

**Option B : Manuel**
```bash
cp .env.example .env
# Éditer .env avec vos informations de connexion
```

Puis générer la clé :
```bash
php artisan key:generate
```

### 3. Vérifier la base de données
La base de données `talashow` doit déjà exister. Les informations de connexion sont pré-configurées :
- Host: 127.0.0.1
- Port: 32768
- Database: talashow
- Username: shad

Si la base n'existe pas, créez-la via Navicat ou MySQL.

### 4. Lancer les migrations
```bash
php artisan migrate
php artisan storage:link
```

### 5. Compiler les assets
```bash
npm run dev
```

### 6. Démarrer le serveur
```bash
php artisan serve
```

✅ **C'est prêt !** Ouvrez http://localhost:8000

> Optionnel (dev/local uniquement) : si tu veux seed des données de démo, active explicitement :
> - `TALASHOW_ENABLE_SEEDING=true`
> - puis `php artisan db:seed`

## 📝 Prochaines Étapes

1. **Configurer Stripe** (pour les paiements)
   - Obtenir les clés sur https://stripe.com
   - Ajouter dans `.env` : `STRIPE_KEY` et `STRIPE_SECRET`

2. **Configurer un service de streaming vidéo**
   - Voir `VIDEO_STREAMING_SERVICES.md`
   - Recommandation : Cloudflare Stream (gratuit pour débuter)

3. **Uploader des vidéos**
   - Via le backoffice : `/admin/series`
   - Ou directement dans `storage/app/public/videos/`

4. **Personnaliser le design**
   - Modifier `resources/css/app.css`
   - Modifier `tailwind.config.js`

## 🎯 Fonctionnalités Principales

✅ Authentification (Email + OAuth Social)
✅ Système d'abonnement (Hebdomadaire/Annuel)
✅ Système de pièces pour débloquer les épisodes
✅ Lecteur vidéo avec progression sauvegardée
✅ PWA (installable sur mobile)
✅ Backoffice complet
✅ Design responsive moderne

## 📚 Documentation Complète

- `INSTALLATION.md` - Guide d'installation détaillé
- `DEPLOYMENT.md` - Guide de déploiement cPanel
- `VIDEO_STREAMING_SERVICES.md` - Services de streaming recommandés
- `README.md` - Documentation générale

## 🆘 Problèmes Courants

**Erreur "Class not found"**
```bash
composer dump-autoload
```

**Assets non chargés**
```bash
npm run build
```

**Erreur de permissions (Linux)**
```bash
chmod -R 755 storage bootstrap/cache
```

## 💡 Astuces

- Utilisez `php artisan tinker` pour tester rapidement
- Les logs sont dans `storage/logs/laravel.log`
- Activez le mode debug dans `.env` : `APP_DEBUG=true` (désactiver en production)
