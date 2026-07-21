# Crons cPanel recommandés (Talashow / Laravel)

Remplacez `/home/USER/domains/VOTREDOMAINE/public_html` par le chemin absolu vers la racine du projet Laravel (là où se trouve `artisan`).

## 1. Planificateur Laravel (obligatoire)

Exécuter **chaque minute** :

```bash
* * * * * cd /home/USER/domains/VOTREDOMAINE/public_html && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Chemin PHP : utilisez souvent `/usr/local/bin/php` ou `/opt/cpanel/ea-php82/root/usr/bin/php` selon l’hébergeur (sélectionnez la même version que le site).

## 2. File d’attente (mutualisation sans worker permanent)

Toutes les **1 à 5 minutes** (exemple toutes les 2 minutes) :

```bash
*/2 * * * * cd /home/USER/domains/VOTREDOMAINE/public_html && /usr/bin/php artisan queue:work --stop-when-empty --max-time=50 >> storage/logs/queue-cron.log 2>&1
```

- `--stop-when-empty` évite un processus bloqué entre deux exécutions cron.
- Ajustez `--max-time` pour rester sous les limites d’exécution PHP du serveur.
- Si l’hébergeur impose des timeouts stricts, augmentez la fréquence du cron et réduisez `--max-time`.

## 3. Notes

- Les webhooks Bunny/Stripe doivent répondre vite : le traitement lourd Bunny est déjà mis en file (`ProcessBunnyStreamWebhookJob`).
- Après mise à jour du code, `php artisan queue:restart` (voir déploiement) signale aux workers de recharger.
