# Configuration Cloudflare Stream - Guide Complet

## ✅ Configuration Effectuée

Votre compte Cloudflare Stream peut être configuré dans le projet (via `.env`, **ne jamais committer** de token) :

- **Account ID** : `<VOTRE_CLOUDFLARE_ACCOUNT_ID>`
- **API Token** : `<VOTRE_CLOUDFLARE_API_TOKEN>`
- **Stream URL** : `https://customer-<VOTRE_CODE>.cloudflarestream.com`

> Sécurité : si un token a déjà été partagé dans une capture/Chat, **révoquez-le** dans Cloudflare et générez-en un nouveau.

## 📋 Fonctionnalités Implémentées

### 1. Service CloudflareStreamService

- ✅ Upload de vidéos (fichier local)
- ✅ Upload depuis URL
- ✅ Récupération des informations vidéo
- ✅ Suppression de vidéos
- ✅ Vérification du statut (ready/processing)

### 2. Contrôleur d'Upload

- ✅ Upload via formulaire admin
- ✅ Upload depuis URL
- ✅ Vérification du statut de traitement

### 3. Lecteur Vidéo

- ✅ Support HLS (format Cloudflare Stream)
- ✅ Sélecteur de qualité automatique
- ✅ Compatible mobile et desktop

## 🚀 Utilisation

### Upload d'une Vidéo via Backoffice

1. **Accéder au backoffice** : `/admin/series/{seriesId}/episodes`
2. **Créer ou éditer un épisode**
3. **Uploader la vidéo** :
   - Via formulaire : Sélectionner le fichier vidéo (max 20GB)
   - Via URL : Coller l'URL de la vidéo à importer

### Upload Programmatique

```php
use App\Services\CloudflareStreamService;

$streamService = new CloudflareStreamService();

// Upload depuis fichier
$result = $streamService->uploadVideo($videoFile, [
    'meta' => [
        'episode_id' => 1,
        'series_id' => 1,
    ],
]);

if ($result['success']) {
    $videoUrl = $result['playback_url'];
    // Utiliser $videoUrl dans l'épisode
}
```

### Vérifier le Statut d'une Vidéo

```php
$status = $streamService->getVideoStatus($videoId);
$isReady = $streamService->isVideoReady($videoId);
```

## 📝 Format des URLs

### URL de Lecture (HLS)

```
https://customer-fghVNXrMeIQu.cloudflarestream.com/{video_id}/manifest/video.m3u8
```

### URL MP4 Directe

```
https://customer-fghVNXrMeIQu.cloudflarestream.com/{video_id}/video.mp4
```

## 🎬 Formats Vidéo Supportés

Cloudflare Stream accepte :

- MP4 (H.264)
- MOV
- AVI
- MKV
- Et autres formats courants

**Note** : Cloudflare transcodera automatiquement en HLS pour la diffusion.

## ⚙️ Configuration dans .env

```env
CLOUDFLARE_ACCOUNT_ID=
CLOUDFLARE_API_TOKEN=
CLOUDFLARE_STREAM_URL=

VIDEO_STORAGE_DISK=cloudflare
STREAMING_PROVIDER=cloudflare
```

## 🔒 Sécurité

### Protection du Contenu

Pour protéger vos vidéos, vous pouvez activer les URLs signées :

```php
$result = $streamService->uploadVideo($file, [
    'requireSignedURLs' => true,
]);
```

### Origines Autorisées

Les origines autorisées sont configurées dans le service pour limiter l'accès.

## 📊 Limites Gratuites

- **100 000 minutes/mois** de streaming
- **1 000 minutes** de stockage
- **Pas de limite** de bande passante

Après la limite gratuite : **$1 pour 1 000 minutes supplémentaires**

## 🐛 Dépannage

### Vidéo ne se charge pas

1. Vérifier que le statut est "ready"
2. Vérifier l'URL dans la base de données
3. Vérifier les CORS si nécessaire

### Upload échoue

1. Vérifier la taille du fichier (max 20GB)
2. Vérifier le format vidéo
3. Vérifier les logs : `storage/logs/laravel.log`

### Statut "processing"

Les vidéos peuvent prendre quelques minutes à être traitées. Utiliser la fonction `checkStatus()` pour vérifier.

## 📚 Documentation Cloudflare

- API Documentation : https://developers.cloudflare.com/stream/
- Dashboard : https://dash.cloudflare.com/
- Pricing : https://www.cloudflare.com/products/cloudflare-stream/

## 🎯 Prochaines Étapes

1. **Tester l'upload** : Créer un épisode et uploader une vidéo test
2. **Vérifier la lecture** : S'assurer que les vidéos se chargent correctement
3. **Configurer les métadonnées** : Ajouter des tags et descriptions aux vidéos
4. **Optimiser** : Utiliser les thumbnails générés automatiquement

## 💡 Astuces

- Les vidéos sont automatiquement optimisées par Cloudflare
- Les thumbnails sont générés automatiquement
- Le transcoding est automatique (pas besoin de pré-traiter)
- Le CDN global assure une diffusion rapide partout
