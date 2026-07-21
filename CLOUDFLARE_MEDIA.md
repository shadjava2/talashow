# Cloudflare Media (Images + Stream) — Intégration Talashow

Objectif : **en dev et en prod**, les médias ne doivent pas être stockés sur le serveur.
Talashow utilise :

-   **Cloudflare Images** pour `poster`, `cover`, `thumbnail`
-   **Cloudflare Stream** pour `video_url` (HLS)

## 1) Variables .env à renseigner

### Cloudflare Images

Dans Cloudflare Dashboard → **Images** :

-   **Account ID** (Images)
-   **API Token** (permission Images:Edit)
-   **Account Hash** (affiché dans Images → “Compte”)
-   Créer des **Variants**: `poster`, `cover`, `thumb`

```env
CLOUDFLARE_IMAGES_ACCOUNT_ID=cebcd32a102b2ee978acf9b5c2c422a5
CLOUDFLARE_IMAGES_API_TOKEN=
CLOUDFLARE_IMAGES_ACCOUNT_HASH=fghVNXrMeIQumh3PKjgo-w

CLOUDFLARE_IMAGES_VARIANT_POSTER=poster
CLOUDFLARE_IMAGES_VARIANT_COVER=cover
CLOUDFLARE_IMAGES_VARIANT_THUMB=thumb
```

### Cloudflare Stream (vidéos)

Dans Cloudflare Dashboard → **Stream** :

```env
CLOUDFLARE_ACCOUNT_ID=
CLOUDFLARE_API_TOKEN=
CLOUDFLARE_STREAM_URL=https://customer-xxxxx.cloudflarestream.com
```

## 2) Fonctionnement côté Talashow

### Upload images (Backoffice)

Quand tu uploades un poster/cover/thumbnail via le backoffice, Talashow :

1. envoie le fichier à **Cloudflare Images**
2. récupère `image_id`
3. stocke en base l’URL **imagedelivery.net** (variant adapté)

Format d’URL utilisé :
`https://imagedelivery.net/<ACCOUNT_HASH>/<IMAGE_ID>/<VARIANT>`

### Upload vidéos (Backoffice)

Pour les épisodes, l’upload vidéo est envoyé à **Cloudflare Stream** et `video_url` devient un `.m3u8` :
`https://customer-xxxxx.cloudflarestream.com/<VIDEO_ID>/manifest/video.m3u8`

## 3) Où se passe l’intégration dans le code

-   `app/Services/CloudflareImagesService.php`
-   `app/Services/CloudflareStreamService.php`
-   `app/Http/Controllers/Admin/AdminController.php` (poster/cover/thumbnail)
-   `app/Http/Controllers/Admin/VideoUploadController.php` (vidéos Stream)

## 4) Résultat

-   Même chemin en dev et prod : **upload via backoffice → Cloudflare → URL en base → affichage**
-   Zéro stockage média sur le serveur (sauf fichiers temporaires le temps de l’upload HTTP)
