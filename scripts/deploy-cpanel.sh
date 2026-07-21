#!/usr/bin/env bash
# =============================================================================
# Talashow — déploiement cPanel (après git push)
# Structure attendue :
#   ~/talashow/          → code Laravel (git clone)
#   ~/public_html/       → DocumentRoot (tala-show.com)
# =============================================================================
set -euo pipefail

APP_DIR="${APP_DIR:-$HOME/talashow}"
WEB_DIR="${WEB_DIR:-$HOME/public_html}"
BRANCH="${BRANCH:-main}"
REPO_URL="${REPO_URL:-https://github.com/shadjava2/talashow.git}"

echo "==> Talashow deploy"
echo "    APP_DIR=$APP_DIR"
echo "    WEB_DIR=$WEB_DIR"
echo "    BRANCH=$BRANCH"

# --- 1) Git : clone ou pull -------------------------------------------------
if [ ! -d "$APP_DIR/.git" ]; then
  if [ -d "$APP_DIR" ] && [ "$(ls -A "$APP_DIR" 2>/dev/null | head -1)" ]; then
    echo "!! $APP_DIR existe déjà sans .git."
    echo "   Option A : cd $APP_DIR && git init && git remote add origin $REPO_URL && git fetch && git checkout -f $BRANCH"
    echo "   Option B : renommer l’ancien dossier et recloner."
    exit 1
  fi
  echo "==> Clone $REPO_URL → $APP_DIR"
  git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
else
  echo "==> git fetch + reset hard $BRANCH"
  cd "$APP_DIR"
  git fetch origin
  git checkout "$BRANCH"
  git reset --hard "origin/$BRANCH"
fi

cd "$APP_DIR"

# Ne jamais écraser .env
if [ ! -f "$APP_DIR/.env" ]; then
  echo "!! Aucun .env dans $APP_DIR — copie .env.example puis configure DB/APP_URL."
  exit 1
fi

# Nettoyage caches Windows uploadés par erreur
rm -f bootstrap/cache/config.php bootstrap/cache/routes-v7.php 2>/dev/null || true
# Dossier Windows créé par erreur (File Manager)
rm -rf "$APP_DIR/C:" 2>/dev/null || true

# --- 2) Composer ------------------------------------------------------------
echo "==> composer install"
if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --optimize-autoloader --no-interaction
else
  echo "!! composer introuvable. Installe Composer ou lance-le via le chemin PHP cPanel."
  exit 1
fi

# --- 3) Assets front (Vite) -------------------------------------------------
echo "==> build front"
if command -v npm >/dev/null 2>&1; then
  # Vite/Tailwind sont en devDependencies → install complet requis pour le build
  npm ci 2>/dev/null || npm install
  npm run build
elif [ -d "$APP_DIR/public/build" ]; then
  echo "    npm absent — utilisation de public/build déjà présent."
else
  echo "!! npm absent et public/build manquant."
  echo "   Sur ton PC : npm run build puis commit public/build, ou installe Node sur le serveur."
  exit 1
fi

# --- 4) Sync public → public_html ------------------------------------------
echo "==> sync public → $WEB_DIR"
mkdir -p "$WEB_DIR"

# Sync fichiers publics (garde .well-known, cgi-bin, logs)
if command -v rsync >/dev/null 2>&1; then
  rsync -a \
    --exclude 'cgi-bin' \
    --exclude '.well-known' \
    --exclude 'error_log' \
    --exclude 'maintenance' \
    "$APP_DIR/public/" "$WEB_DIR/"
else
  # Fallback sans rsync
  cp -a "$APP_DIR/public/." "$WEB_DIR/"
fi

# index.php adapté DocumentRoot séparé (critique)
if [ -f "$APP_DIR/scripts/cpanel/index.php" ]; then
  echo "==> installation index.php cPanel"
  cp -f "$APP_DIR/scripts/cpanel/index.php" "$WEB_DIR/index.php"
fi

# --- 5) Laravel caches ------------------------------------------------------
echo "==> artisan clear + cache"
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Migrations uniquement si tu le veux (décommente si besoin)
# php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "✅ Déploiement terminé — https://tala-show.com"
echo "   Hard refresh navigateur (Ctrl+Shift+R)."
echo "   Si langue/UI collent : désinscrire le Service Worker une fois."
