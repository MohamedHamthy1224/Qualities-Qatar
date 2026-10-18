#!/bin/bash
set -e

# Ensure .env file exists so artisan commands (like key:generate) work
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
    else
        touch .env
    fi
fi

# Ensure storage and bootstrap/cache directories exist with write permissions
mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache database
touch storage/logs/laravel.log

# Ensure SQLite database exists with proper permissions
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    touch database/database.sqlite
    chown -R www-data:www-data database
    chmod -R 775 database
    chmod 664 database/database.sqlite
fi

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Generate app key if not already configured in environment or .env
if [ -z "$APP_KEY" ]; then
    if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
        echo "Generating application encryption key..."
        php artisan key:generate --force
    fi
fi

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force || true

# Seed database if requested
if [ "$RUN_SEEDER" = "true" ]; then
    echo "Running database seeders..."
    php artisan db:seed --force || true
fi

# Cache config, routes, views for optimal production performance
echo "Caching Laravel configuration and routes..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Reconfigure Apache to bind to the dynamic port passed by Railway / Render (e.g. 8080)
TARGET_PORT="${PORT:-80}"
echo "Configuring Apache to listen on port ${TARGET_PORT}..."
sed -i "s/Listen 80/Listen ${TARGET_PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${TARGET_PORT}>/g" /etc/apache2/sites-available/000-default.conf

echo "Starting Apache web server on port ${TARGET_PORT}..."
exec apache2-foreground
