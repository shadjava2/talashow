# Services de Streaming Vidéo - Recommandations

## Services Gratuits (avec limitations)

### 1. Cloudflare Stream ⭐ RECOMMANDÉ POUR DÉBUT
- **Gratuit** : 100 000 minutes/mois
- **Prix après** : $1 pour 1 000 minutes supplémentaires
- **Avantages** :
  - CDN global intégré
  - Transcoding automatique
  - Player HTML5 intégré
  - Analytics inclus
  - Sécurité (DRM optionnel)
- **Inconvénients** :
  - Limite de 100K minutes/mois en gratuit
- **Lien** : https://www.cloudflare.com/products/cloudflare-stream/

### 2. Bunny.net
- **Gratuit** : 1 TB de bande passante/mois
- **Prix** : $0.01/GB après
- **Avantages** :
  - Très économique
  - CDN rapide
  - Support HLS/DASH
  - API simple
- **Inconvénients** :
  - Pas de transcoding automatique
- **Lien** : https://bunny.net/

### 3. Mux
- **Gratuit** : 1 000 minutes/mois pour test
- **Prix** : $0.04/minute après
- **Avantages** :
  - Qualité professionnelle
  - Analytics avancés
  - Transcoding automatique
- **Inconvénients** :
  - Plus cher que les autres
- **Lien** : https://mux.com/

## Services Payants (Production)

### 1. AWS S3 + CloudFront ⭐ SCALABLE
- **Prix** : Variable selon usage
- **Avantages** :
  - Infiniement scalable
  - Très fiable
  - Intégration facile avec Laravel
  - CDN global
- **Inconvénients** :
  - Configuration plus complexe
  - Coûts peuvent augmenter avec le trafic
- **Lien** : https://aws.amazon.com/s3/

### 2. Vimeo Pro
- **Prix** : $20/mois
- **Avantages** :
  - Solution complète
  - Player personnalisable
  - Analytics
  - Support client
- **Inconvénients** :
  - Limite de stockage
  - Moins flexible
- **Lien** : https://vimeo.com/

### 3. Wistia
- **Prix** : $99/mois
- **Avantages** :
  - Analytics avancés
  - Marketing tools
  - Player personnalisable
- **Inconvénients** :
  - Plus cher
  - Orienté marketing
- **Lien** : https://wistia.com/

## Recommandation pour Talashow

### Phase 1 : Développement/Test
**Cloudflare Stream** (Gratuit)
- 100K minutes/mois suffisent pour tester
- Facile à intégrer
- Qualité professionnelle

### Phase 2 : Production (Petit trafic)
**Bunny.net** ou **Cloudflare Stream**
- Bunny.net si budget serré
- Cloudflare Stream si besoin de transcoding automatique

### Phase 3 : Production (Grand trafic)
**AWS S3 + CloudFront**
- Scalable à l'infini
- Contrôle total
- Coûts optimisables

## Intégration dans Laravel

### Exemple avec Cloudflare Stream

```php
// Upload vidéo
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . config('services.cloudflare.api_token'),
])->post('https://api.cloudflare.com/client/v4/accounts/{account_id}/stream', [
    'file' => $videoFile,
]);

$videoId = $response->json()['result']['uid'];

// URL de streaming
$streamUrl = "https://customer-{code}.cloudflarestream.com/{$videoId}/manifest/video.m3u8";
```

### Exemple avec Bunny.net

```php
// Upload via FTP ou API
$videoUrl = "https://{pullzone}.b-cdn.net/{video_path}.mp4";

// Pour HLS
$hlsUrl = "https://{pullzone}.b-cdn.net/{video_path}/playlist.m3u8";
```

### Exemple avec AWS S3

```php
// Upload
Storage::disk('s3')->put("videos/{$filename}", $videoFile);

// URL CloudFront
$videoUrl = "https://{cloudfront-domain}.cloudfront.net/videos/{$filename}";
```

## Configuration dans .env

```env
# Cloudflare Stream
CLOUDFLARE_ACCOUNT_ID=
CLOUDFLARE_API_TOKEN=
CLOUDFLARE_STREAM_URL=

# Bunny.net
BUNNY_PULLZONE=
BUNNY_API_KEY=

# AWS
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=
AWS_CLOUDFRONT_URL=
```

## Notes Importantes

1. **Transcoding** : Les vidéos doivent être encodées en H.264 (MP4) ou HLS pour une compatibilité maximale
2. **DRM** : Pour protéger le contenu, considérer Cloudflare Stream ou AWS MediaPackage
3. **CDN** : Toujours utiliser un CDN pour la distribution
4. **Backup** : Garder une copie locale des vidéos originales
5. **Légalité** : S'assurer d'avoir les droits de diffusion des contenus
