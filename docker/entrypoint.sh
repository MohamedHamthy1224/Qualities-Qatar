#!/bin/bash
# Entrypoint for Railway/Render deployment
# APP_KEY is baked into the image at build time - no key generation needed here

# ── 1. Override .env values with Railway environment variables ───────────────
# If Railway sets APP_KEY, APP_URL etc. as env vars, write them into .env
write_env_var() {
    local key="$1"
    local value="$2"
    if [ -n "$value" ]; then
        if grep -q "^${key}=" .env 2>/dev/null; then
            sed -i "s|^${key}=.*|${key}=${value}|" .env
        else
            echo "${key}=${value}" >> .env
        fi
    fi
}

# Override with any Railway-provided environment variables
[ -n "$APP_KEY" ]   && write_env_var "APP_KEY"   "$APP_KEY"
[ -n "$APP_URL" ]   && write_env_var "APP_URL"   "$APP_URL"
[ -n "$APP_ENV" ]   && write_env_var "APP_ENV"   "$APP_ENV"
[ -n "$APP_DEBUG" ] && write_env_var "APP_DEBUG" "$APP_DEBUG"

# ── 2. Ensure storage is writable (Railway may reset filesystem) ─────────────
mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache
touch storage/logs/laravel.log
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# ── 3. Ensure SQLite database exists ─────────────────────────────────────────
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    touch database/database.sqlite
    chown www-data:www-data database database/database.sqlite
    chmod 775 database
    chmod 664 database/database.sqlite
fi

# ── 4. Run migrations (create tables) ────────────────────────────────────────
echo "==> Running migrations..."
php artisan migrate --force
echo "==> Migrations done (exit: $?)"

# ── 5. Seed if requested ──────────────────────────────────────────────────────
if [ "${RUN_SEEDER:-false}" = "true" ]; then
    echo "==> Seeding database..."
    php artisan db:seed --force || echo "WARNING: Seed failed"
fi

# ── 6. Configure Apache to listen on Railway's dynamic $PORT ─────────────────
TARGET_PORT="${PORT:-80}"
echo "==> Configuring Apache for port ${TARGET_PORT}..."

if [ "$TARGET_PORT" != "80" ]; then
    # Only add if not already present
    if ! grep -q "^Listen ${TARGET_PORT}$" /etc/apache2/ports.conf; then
        echo "Listen ${TARGET_PORT}" >> /etc/apache2/ports.conf
    fi
    # Update VirtualHost if not already updated
    if grep -q "<VirtualHost \*:80>" /etc/apache2/sites-available/000-default.conf; then
        sed -i "s|<VirtualHost \*:80>|<VirtualHost *:80 *:${TARGET_PORT}>|g" \
            /etc/apache2/sites-available/000-default.conf
    fi
fi

echo "================================================"
echo " QUALITIES Qatar - Starting"
echo " Port: ${TARGET_PORT}"
echo " DB:   ${DB_CONNECTION:-sqlite}"
echo "================================================"
exec apache2-foreground
