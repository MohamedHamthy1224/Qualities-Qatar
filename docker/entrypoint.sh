#!/bin/bash
set -e

# If PORT is provided by hosting provider (Render, Railway, Fly, etc.), reconfigure Apache
if [ -n "$PORT" ]; then
    sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
    sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:$PORT>/" /etc/apache2/sites-available/000-default.conf
fi

# Ensure storage directories exist with right permissions
mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache database
touch storage/logs/laravel.log

# Ensure SQLite database exists if DB_CONNECTION is sqlite
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    touch database/database.sqlite
    chown -R www-data:www-data database
    chmod 775 database
    chmod 664 database/database.sqlite
fi

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Generate app key if missing
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is not set. Generating key..."
    php artisan key:generate --force || true
fi

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force || true

# Seed database if requested
if [ "$RUN_SEEDER" = "true" ]; then
    echo "Running database seeders..."
    php artisan db:seed --force || true
fi

# Optimize Laravel cache for production
echo "Caching configuration and routes..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "Starting Apache web server on port ${PORT:-80}..."
exec apache2-foreground
