#!/bin/bash
# Do NOT use set -e - we handle errors manually for resilience

# ── 1. Ensure .env exists ────────────────────────────────────────────────────
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        echo "Creating .env from .env.example..."
        cp .env.example .env
    else
        echo "Creating empty .env..."
        touch .env
    fi
fi

# ── 2. Write Railway/Render env vars into .env if not already present ────────
# This ensures Laravel reads them even if APP_KEY is only in the OS environment
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

write_env_var "APP_KEY" "$APP_KEY"
write_env_var "APP_URL" "$APP_URL"
write_env_var "APP_ENV" "${APP_ENV:-production}"
write_env_var "APP_DEBUG" "${APP_DEBUG:-false}"

# ── 3. Generate APP_KEY if still missing ────────────────────────────────────
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null && [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# ── 4. Storage & bootstrap directories ──────────────────────────────────────
mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache database
touch storage/logs/laravel.log
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# ── 5. SQLite database setup ─────────────────────────────────────────────────
DB_CONN="${DB_CONNECTION:-sqlite}"
if [ "$DB_CONN" = "sqlite" ]; then
    DB_PATH="${DB_DATABASE:-database/database.sqlite}"
    # Handle absolute vs relative path
    if [[ "$DB_PATH" != /* ]]; then
        DB_PATH="/var/www/html/${DB_PATH}"
    fi
    echo "Using SQLite database at: $DB_PATH"
    mkdir -p "$(dirname "$DB_PATH")"
    touch "$DB_PATH"
    chown www-data:www-data "$DB_PATH" "$(dirname "$DB_PATH")"
    chmod 664 "$DB_PATH"
    chmod 775 "$(dirname "$DB_PATH")"
fi

# ── 6. Run migrations ────────────────────────────────────────────────────────
echo "Running database migrations..."
php artisan migrate --force
if [ $? -ne 0 ]; then
    echo "WARNING: Migrations failed - app may not work correctly"
fi

# ── 7. Seed database if requested ────────────────────────────────────────────
if [ "${RUN_SEEDER:-false}" = "true" ]; then
    echo "Running database seeders..."
    php artisan db:seed --force || echo "WARNING: Seeder failed"
fi

# ── 8. Clear and cache config for production ─────────────────────────────────
echo "Caching configuration for production..."
php artisan config:clear || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# ── 9. Configure Apache to listen on $PORT (Railway assigns a dynamic port) ──
TARGET_PORT="${PORT:-80}"
echo "Configuring Apache on port ${TARGET_PORT}..."

# Remove any existing extra Listen directives we added before
grep -v "^Listen ${TARGET_PORT}$" /etc/apache2/ports.conf > /tmp/ports.conf.tmp \
    && mv /tmp/ports.conf.tmp /etc/apache2/ports.conf

if [ "$TARGET_PORT" != "80" ]; then
    echo "Listen ${TARGET_PORT}" >> /etc/apache2/ports.conf
    # Update VirtualHost to also serve on TARGET_PORT
    if grep -q "<VirtualHost \*:80>" /etc/apache2/sites-available/000-default.conf; then
        sed -i "s|<VirtualHost \*:80>|<VirtualHost *:80 *:${TARGET_PORT}>|g" \
            /etc/apache2/sites-available/000-default.conf
    elif ! grep -q "\*:${TARGET_PORT}" /etc/apache2/sites-available/000-default.conf; then
        sed -i "s|<VirtualHost \*:\([0-9]*\)>|<VirtualHost *:\1 *:${TARGET_PORT}>|g" \
            /etc/apache2/sites-available/000-default.conf
    fi
fi

echo "--------------------------------------------"
echo "Starting Apache on port 80 and ${TARGET_PORT}"
echo "APP_ENV: ${APP_ENV:-production}"
echo "DB_CONNECTION: ${DB_CONN}"
echo "--------------------------------------------"
exec apache2-foreground
