#!/bin/sh
set -e

echo "Waiting for MySQL..."
until php -r 'try { new PDO(sprintf("mysql:host=%s;port=%s", getenv("DB_HOST") ?: "mysql", getenv("DB_PORT") ?: "3306"), getenv("DB_USERNAME") ?: "root", getenv("DB_PASSWORD") ?: "secret"); } catch (Throwable $e) { exit(1); }'; do
    sleep 2
done
echo "MySQL is ready."

if [ ! -f artisan ]; then
    echo "Laravel is not installed yet. Skipping app bootstrap."
    exec "$@"
fi

if [ ! -f .env ]; then
    cp .env.example .env
fi

composer install --no-interaction --prefer-dist --no-progress

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --ansi --no-interaction
fi

chmod -R 777 storage bootstrap/cache
php artisan storage:link --force --ansi || true

if [ "$1" = "apache2-foreground" ] && [ ! -f public/build/manifest.json ]; then
    echo "Building frontend assets..."
    npm install --no-fund --no-audit
    npm run build
fi

exec "$@"
