# LiteSpeed / cache HTTP — règles et exclusions

## Objectif

- Mettre en cache longtemps les **assets statiques** (notamment `public/build/*` hashés par Vite).
- **Ne pas** mettre en cache de façon agressive : back-office, authentification, paiements, webhooks, pages personnalisées.

## Fichier `public/.htaccess`

Le projet inclut **deflate** + **expires** pour types statiques courants. Les exclusions fines par chemin dépendent souvent du panneau LiteSpeed (contextes) plutôt que d’Apache seul.

## Recommandations panneau LiteSpeed (cPanel)

1. Activer la compression Brotli/Gzip au niveau du virtual host si disponible.
2. Définir un cache public long pour les extensions : `.css`, `.js`, `.woff2`, images, **en priorité** sous `/build/`.
3. **Exclure du cache page** (LSCache « dynamic » ou désactivation cache) pour les préfixes :
   - `/talashow-admin`
   - `/login`, `/register`, `/verify-otp`, `/forgot-password`, `/reset-password`
   - `/payment` (y compris recharge / donation / callbacks)
   - `/webhooks`
   - `/api`
   - `/profile`
4. Ne jamais mettre en cache les **requêtes POST** (paiements, formulaires, admin).
5. Si vous utilisez **Cloudflare** devant LiteSpeed : respecter les mêmes exclusions (voir rapport sécurité).

## Service Worker

Le fichier `public/sw.js` ignore le cache navigateur pour les chemins sensibles (admin, auth, paiement, webhooks, API, profil). Incrémenter `CACHE_NAME` lors d’un changement majeur de stratégie.
