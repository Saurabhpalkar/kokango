#!/usr/bin/env bash
# Kokango API: install the latest code from GitHub on the server.   sudo bash /var/www/kokango/deploy/oracle/update.sh
set -euo pipefail
API_DIR=/var/www/kokango/backend
[ "$(id -u)" = 0 ] || { echo "Run with sudo."; exit 1; }

git -C /var/www/kokango pull --ff-only
cd "$API_DIR"
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --optimize-autoloader --no-interaction
php artisan down --retry=15 || true
php artisan migrate --force
chown -R www-data:www-data storage bootstrap/cache
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
php artisan up
systemctl reload "php$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')-fpm"
echo "Updated to $(git -C /var/www/kokango log -1 --format='%h %s')"
