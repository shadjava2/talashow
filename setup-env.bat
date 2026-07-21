@echo off
echo ========================================
echo Configuration de l'environnement Talashow
echo ========================================
echo.

if exist .env (
    echo Le fichier .env existe deja. Suppression de l'etape (aucune modification).
    echo Si vous souhaitez le regenerer, supprimez .env puis relancez ce script.
    pause
    exit /b
)

echo Creation du fichier .env...
(
echo APP_NAME=Talashow
echo APP_ENV=local
echo APP_KEY=
echo APP_DEBUG=true
echo APP_TIMEZONE=UTC
echo APP_URL=http://localhost:8000
echo APP_LOCALE=fr
echo APP_FALLBACK_LOCALE=fr
echo APP_FAKER_LOCALE=fr_FR
echo.
echo APP_MAINTENANCE_DRIVER=file
echo APP_MAINTENANCE_STORE=database
echo.
echo BCRYPT_ROUNDS=12
echo.
echo LOG_CHANNEL=stack
echo LOG_STACK=single
echo LOG_DEPRECATIONS_CHANNEL=null
echo LOG_LEVEL=debug
echo.
echo DB_CONNECTION=mysql
echo DB_HOST=127.0.0.1
echo DB_PORT=32768
echo DB_DATABASE=talashow
echo DB_USERNAME=shad
echo DB_PASSWORD=
echo.
echo SESSION_DRIVER=database
echo SESSION_LIFETIME=120
echo SESSION_ENCRYPT=false
echo SESSION_PATH=/
echo SESSION_DOMAIN=null
echo.
echo BROADCAST_CONNECTION=log
echo FILESYSTEM_DISK=local
echo QUEUE_CONNECTION=database
echo.
echo CACHE_STORE=database
echo CACHE_PREFIX=
echo.
echo MEMCACHED_HOST=127.0.0.1
echo.
echo REDIS_CLIENT=phpredis
echo REDIS_HOST=127.0.0.1
echo REDIS_PASSWORD=null
echo REDIS_PORT=6379
echo.
echo MAIL_MAILER=smtp
echo MAIL_HOST=mailpit
echo MAIL_PORT=1025
echo MAIL_USERNAME=null
echo MAIL_PASSWORD=null
echo MAIL_ENCRYPTION=null
echo MAIL_FROM_ADDRESS="hello@example.com"
echo MAIL_FROM_NAME="${APP_NAME}"
echo.
echo AWS_ACCESS_KEY_ID=
echo AWS_SECRET_ACCESS_KEY=
echo AWS_DEFAULT_REGION=us-east-1
echo AWS_BUCKET=
echo AWS_USE_PATH_STYLE_ENDPOINT=false
echo.
echo VITE_APP_NAME="${APP_NAME}"
echo.
echo # Stripe Payment
echo STRIPE_KEY=
echo STRIPE_SECRET=
echo STRIPE_WEBHOOK_SECRET=
echo.
echo # OAuth Social Login
echo GOOGLE_CLIENT_ID=
echo GOOGLE_CLIENT_SECRET=
echo FACEBOOK_CLIENT_ID=
echo FACEBOOK_CLIENT_SECRET=
echo APPLE_CLIENT_ID=
echo APPLE_CLIENT_SECRET=
echo.
echo # Video Streaming
echo VIDEO_STORAGE_DISK=local
echo VIDEO_CDN_URL=
echo STREAMING_PROVIDER=local
echo.
echo # Subscription Plans
echo SUBSCRIPTION_WEEKLY_PRICE=16.99
echo SUBSCRIPTION_YEARLY_PRICE=149.99
echo COIN_UNLOCK_PRICE=0.10
) > .env

echo.
echo Fichier .env cree avec succes!
echo.
echo Prochaines etapes:
echo 1. Executer: php artisan key:generate
echo 2. Executer: php artisan migrate --seed
echo 3. Executer: php artisan storage:link
echo.
pause
