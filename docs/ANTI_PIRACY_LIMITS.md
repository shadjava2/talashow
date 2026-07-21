# Anti-piratage vidéo — limites et niveau de protection réaliste

## Ce qui est impossible à 100 % dans le navigateur

- Empêcher **capture d’écran** ou **enregistrement d’écran** côté utilisateur (OS / extensions / autre appareil).
- Garantir qu’un utilisateur déterminé ne puisse **jamais** extraire un flux : la lecture se fait sur un appareil contrôlé par l’utilisateur.

## Mesures mises en œuvre (Talashow)

1. **Vidéo hors origin** : Bunny Stream + CDN — le serveur mutualisé ne sert pas les gros flux binaires.
2. **Contrôle d’accès** : déblocage épisode / abonnement vérifié côté serveur avant exposition du lecteur ; endpoint JSON `/playback` refusé si non autorisé (journalisation `playback_denied`).
3. **URLs signées / jetons Bunny** : support existant (`BUNNY_STREAM_TOKEN_AUTH_ENABLED`, clés token, TTL `BUNNY_STREAM_URL_EXPIRATION` / embed). À activer côté Bunny + `.env` pour réduire le partage de liens bruts.
4. **Watermark dynamique** (utilisateur connecté) : identifiant masqué, email masqué, IP partielle, horodatage ; léger mouvement périodique pour décourager la republication.
5. **Service worker** : ne cache pas les zones sensibles (admin, auth, paiement, webhooks, API, profil).
6. **Monitoring / audit** : événements de sécurité, logs admin, webhooks suspects.

## Stratégie « sérieuse »

Combiner **signed URLs + contrôle d’accès + watermark + monitoring + procédure de retrait (takedown)** +, si besoin, **DRM** côté fournisseur vidéo (niveau Bunny / autre) pour les contenus à très forte valeur.

## Application mobile native (futur)

Sur Android, `FLAG_SECURE` peut limiter les captures d’écran dans l’app ; sur iOS, protections équivalentes via politiques d’appareil / conteneur. Le web seul ne remplace pas cette couche.
