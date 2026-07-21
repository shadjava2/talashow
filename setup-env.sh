#!/bin/bash

echo "========================================"
echo "Configuration de l'environnement Talashow"
echo "========================================"
echo ""

if [ -f .env ]; then
    echo "Le fichier .env existe déjà. Aucune modification."
    echo "Pour le régénérer, supprimez .env puis relancez ce script."
    exit 0
fi

echo "Création du fichier .env..."

cat > .env << 'EOF'
APP_NAME=Talashow
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr
APP_FAKER_LOCALE=fr_FR

APP_MAINTENANCE_DRIVER=file
APP_MAINTENANCE_STORE=database

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=32768
DB_DATABASE=talashow
DB_USERNAME=shad
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"

# Stripe Payment
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

# OAuth Social Login
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
APPLE_CLIENT_ID=
APPLE_CLIENT_SECRET=

# Video Streaming
VIDEO_STORAGE_DISK=local
VIDEO_CDN_URL=
STREAMING_PROVIDER=local

# Subscription Plans
SUBSCRIPTION_WEEKLY_PRICE=16.99
SUBSCRIPTION_YEARLY_PRICE=149.99
COIN_UNLOCK_PRICE=0.10
EOF

echo ""
echo "✅ Fichier .env créé avec succès!"
echo ""
echo "Prochaines étapes:"
echo "1. Exécuter: php artisan key:generate"
echo "2. Exécuter: php artisan migrate --seed"
echo "3. Exécuter: php artisan storage:link"
echo ""
