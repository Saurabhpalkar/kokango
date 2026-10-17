#!/usr/bin/env bash
# Container start-up: database certificate, Laravel caches, migrations, admin account, scheduler, then Apache.
set -e
cd /var/www/html

# Render (and most platforms) say which port to listen on
PORT="${PORT:-10000}"
sed -ri "s/^Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Laravel needs a 32-byte key. Render's "Generate" button makes a random value in another format,
# so any value that is not already "base64:..." is turned into a proper key (the same value always gives the same key).
if [ -n "${APP_KEY:-}" ] && [[ "$APP_KEY" != base64:* ]]; then
  export APP_KEY="$(php -r 'echo "base64:".base64_encode(hash("sha256", getenv("APP_KEY"), true));')"
fi

# Managed MySQL (Aiven) needs its CA certificate: paste it into the DB_CA_CERT variable
if [ -n "${DB_CA_CERT:-}" ]; then
  printf '%s\n' "$DB_CA_CERT" > /var/www/html/storage/db-ca.pem
  export MYSQL_ATTR_SSL_CA=/var/www/html/storage/db-ca.pem
fi

php artisan config:cache
php artisan route:cache
php artisan storage:link >/dev/null 2>&1 || true

# Database: new tables, roles, the Kokango products (all safe to repeat on every start)
php artisan migrate --force
php artisan db:seed --class=RoleSeeder --force
if [ "${SEED_CATALOGUE:-1}" = "1" ]; then
  php artisan db:seed --class=SettingSeeder --force
  php artisan db:seed --class=CatalogueSeeder --force
fi

# Admin account from ADMIN_EMAIL / ADMIN_PASSWORD (created once; later changes are made in the admin panel)
if [ -n "${ADMIN_EMAIL:-}" ] && [ -n "${ADMIN_PASSWORD:-}" ]; then
  php artisan tinker --execute='
    $u = App\Models\User::where("email", getenv("ADMIN_EMAIL"))->first();
    if (! $u) {
      $u = App\Models\User::create(["name" => "Kokango Admin", "email" => getenv("ADMIN_EMAIL"), "password" => getenv("ADMIN_PASSWORD"), "is_active" => true]);
      $u->assignRole("admin"); echo "admin created\n";
    }'
fi

chown -R www-data:www-data storage bootstrap/cache

# Laravel scheduler (releases stock of unpaid orders) runs in the background while the container is awake
su -s /bin/bash www-data -c "php artisan schedule:work" > /dev/null 2>&1 &

exec apache2-foreground
