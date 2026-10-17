#!/usr/bin/env bash
# Kokango API: one-time server setup on Oracle Cloud (Ubuntu 22.04 / 24.04, ARM or x86).
#
#   sudo DOMAIN=kokango.duckdns.org EMAIL=you@example.com ADMIN_EMAIL=you@example.com bash setup.sh
#
# Optional:  FRONTEND_URL=https://kokango.netlify.app   REPO=https://github.com/Saurabhpalkar/kokango.git
#            BRANCH=main   SEED=1 (0 = empty database, e.g. when you import your old data yourself)
#
# Installs Nginx, PHP, MySQL, Composer and Certbot; opens ports 80/443 in the server firewall; clones the repo
# to /var/www/kokango; creates the database, .env, an admin account and HTTPS; and adds the Laravel scheduler.
# Safe to run again: existing database, .env and admin are kept.
set -euo pipefail

: "${DOMAIN:?Set DOMAIN, e.g. DOMAIN=kokango.duckdns.org}"
: "${EMAIL:?Set EMAIL, used by Lets Encrypt for certificate notices}"
ADMIN_EMAIL="${ADMIN_EMAIL:-$EMAIL}"
FRONTEND_URL="${FRONTEND_URL:-https://kokango.netlify.app}"
REPO="${REPO:-https://github.com/Saurabhpalkar/kokango.git}"
BRANCH="${BRANCH:-main}"
SEED="${SEED:-1}"
APP_DIR=/var/www/kokango
API_DIR="$APP_DIR/backend"
SECRETS=/root/kokango-secrets.txt

[ "$(id -u)" = 0 ] || { echo "Run with sudo."; exit 1; }
say() { printf '\n\033[1;32m==> %s\033[0m\n' "$*"; }
export DEBIAN_FRONTEND=noninteractive

say "Installing packages (Nginx, PHP, MySQL, Composer, Certbot)"
apt-get update -y
apt-get install -y nginx mysql-server git unzip curl certbot python3-certbot-nginx iptables-persistent \
  php-fpm php-cli php-mysql php-mbstring php-xml php-curl php-zip php-bcmath php-intl php-gd composer
PHPV="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
php -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);' || { echo "PHP $PHPV is too old (8.2+ needed). Use Ubuntu 24.04."; exit 1; }

say "Swap file (helps the small 1 GB servers)"
if ! swapon --show | grep -q /swapfile; then
  fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
  grep -q '/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

say "Opening ports 80 and 443 in the server firewall (Oracle images block them by default)"
for port in 80 443; do
  iptables -C INPUT -p tcp --dport "$port" -m state --state NEW -j ACCEPT 2>/dev/null \
    || iptables -I INPUT 5 -p tcp --dport "$port" -m state --state NEW -j ACCEPT
done
netfilter-persistent save

say "Getting the code"
if [ -d "$APP_DIR/.git" ]; then git -C "$APP_DIR" pull --ff-only; else git clone --branch "$BRANCH" "$REPO" "$APP_DIR"; fi

say "Database"
touch "$SECRETS"; chmod 600 "$SECRETS"
DB_PASS="$(grep '^DB_PASSWORD=' "$SECRETS" | cut -d= -f2- || true)"
if [ -z "$DB_PASS" ]; then DB_PASS="$(openssl rand -hex 18)"; echo "DB_PASSWORD=$DB_PASS" >> "$SECRETS"; fi
mysql <<SQL
CREATE DATABASE IF NOT EXISTS kokango CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'kokango'@'localhost' IDENTIFIED BY '$DB_PASS';
ALTER USER 'kokango'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON kokango.* TO 'kokango'@'localhost';
FLUSH PRIVILEGES;
SQL

say "Laravel .env"
cd "$API_DIR"
if [ ! -f .env ]; then
  cp .env.example .env
  set_env() { if grep -q "^$1=" .env; then sed -i "s|^$1=.*|$1=$2|" .env; else echo "$1=$2" >> .env; fi; }
  set_env APP_ENV production
  set_env APP_DEBUG false
  set_env APP_URL "https://$DOMAIN"
  set_env LOG_LEVEL warning
  set_env DB_DATABASE kokango
  set_env DB_USERNAME kokango
  set_env DB_PASSWORD "$DB_PASS"
  set_env SESSION_DRIVER file
  set_env CACHE_STORE file
  set_env QUEUE_CONNECTION sync
  set_env FRONTEND_URL "$FRONTEND_URL"
  set_env MAIL_FROM_ADDRESS "\"$EMAIL\""
  echo ".env created. Payment stays on the fake driver until you add Razorpay keys (see README)."
fi

say "Installing PHP libraries and preparing Laravel"
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --optimize-autoloader --no-interaction
grep -q '^APP_KEY=base64' .env || php artisan key:generate --force
php artisan migrate --force
if [ "$SEED" = 1 ]; then
  php artisan db:seed --class=RoleSeeder --force
  php artisan db:seed --class=SettingSeeder --force
  php artisan db:seed --class=CatalogueSeeder --force
else
  php artisan db:seed --class=RoleSeeder --force
fi
php artisan storage:link || true

say "Admin account ($ADMIN_EMAIL)"
if ! grep -q '^ADMIN_PASSWORD=' "$SECRETS"; then
  ADMIN_PASS="$(openssl rand -base64 12 | tr -d '/+=')A9!"
  ADMIN_EMAIL="$ADMIN_EMAIL" ADMIN_PASS="$ADMIN_PASS" php artisan tinker --execute='
    $u = App\Models\User::firstOrNew(["email" => getenv("ADMIN_EMAIL")]);
    $u->name = $u->name ?: "Kokango Admin"; $u->password = getenv("ADMIN_PASS"); $u->is_active = true; $u->save();
    $u->syncRoles(["admin"]); echo "admin ready\n";'
  echo "ADMIN_EMAIL=$ADMIN_EMAIL" >> "$SECRETS"; echo "ADMIN_PASSWORD=$ADMIN_PASS" >> "$SECRETS"
fi

chown -R www-data:www-data "$API_DIR/storage" "$API_DIR/bootstrap/cache"
chmod -R ug+rwX "$API_DIR/storage" "$API_DIR/bootstrap/cache"
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache

say "Nginx"
cat > /etc/nginx/sites-available/kokango <<NGINX
server {
    listen 80;
    server_name $DOMAIN;
    root $API_DIR/public;
    index index.php;
    client_max_body_size 6M;

    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php$PHPV-fpm.sock;
    }
    location ~ /\.(?!well-known) { deny all; }
}
NGINX
ln -sf /etc/nginx/sites-available/kokango /etc/nginx/sites-enabled/kokango
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

say "HTTPS certificate (Let's Encrypt)"
certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos -m "$EMAIL" --redirect || \
  echo "!! Certificate failed: check that $DOMAIN points to this server's public IP and ports 80/443 are open in the Oracle Security List, then run: sudo certbot --nginx -d $DOMAIN"

say "Scheduler (releases stock of unpaid orders every 15 minutes)"
echo "* * * * * www-data cd $API_DIR && php artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/kokango
chmod 644 /etc/cron.d/kokango

say "Done"
echo "API:        https://$DOMAIN/api/v1/products"
echo "Admin:      $ADMIN_EMAIL  (password saved in $SECRETS, read it with: sudo cat $SECRETS)"
echo "Next:       set VITE_API_BASE_URL=https://$DOMAIN/api/v1 in Netlify and redeploy the site."
