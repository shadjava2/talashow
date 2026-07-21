# 2FA / TOTP — emplacement prévu

Le projet inclut des colonnes optionnelles sur `users` :

- `two_factor_secret`
- `two_factor_recovery_codes`
- `two_factor_confirmed_at`

Aucun flux de connexion 2FA n’est activé par défaut (pour ne pas casser l’existant). Prochaine étape possible :

1. Écran admin « Sécurité » pour enregistrer un secret TOTP (RFC 6238) sans dépendance lourde, ou intégration d’une librairie éprouvée.
2. Middleware `EnsureTwoFactorPassed` après login admin uniquement.
3. Codes de récupération stockés chiffrés (cast / accessor).

Tant que 2FA n’est pas implémenté, laisser ces colonnes à `NULL`.
