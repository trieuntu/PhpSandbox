#!/bin/sh
set -e

echo "[entrypoint] Starting PHP Sandbox app container..."

# ── 1. Generate APP_KEY if not provided ────────────────────────────────────
if [ -z "$APP_KEY" ]; then
    echo "[entrypoint] Generating application key..."
    php artisan key:generate --force --no-interaction
fi

# ── 2. Wait for MySQL to be ready ──────────────────────────────────────────
DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"
echo "[entrypoint] Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
for i in $(seq 1 30); do
    if php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; then
        echo "[entrypoint] MySQL is ready."
        break
    fi
    echo "[entrypoint] MySQL not ready (attempt ${i}/30)..."
    sleep 2
done

# ── 3. Run migrations ─────────────────────────────────────────────────────
echo "[entrypoint] Running migrations..."
php artisan migrate --force --no-interaction

# ── 4. Ensure storage symlink exists ──────────────────────────────────────
if [ ! -L public/storage ]; then
    php artisan storage:link --force --no-interaction 2>/dev/null || true
fi

# ── 5. Optimize for production ────────────────────────────────────────────
if [ "${APP_ENV}" = "production" ]; then
    echo "[entrypoint] Caching config/routes/views for production..."
    php artisan optimize --no-interaction || true
fi

# ── 6. Copy public assets to shared volume (for nginx) ────────────────────
# The public/ dir is baked into the image; copy once to the shared volume.
if [ -d /var/www/html/public ] && [ "${SKIP_PUBLIC_SYNC:-0}" != "1" ]; then
    cp -rn /var/www/html/public/. /var/www/public/ 2>/dev/null || true
fi

echo "[entrypoint] Startup complete. Launching: $@"
exec "$@"
