#!/bin/bash
set -e

# =============================================================
# Lemonwares VPS Setup Script
# Run as root on a fresh Ubuntu 22.04/24.04 server
# Usage: bash setup.sh
# =============================================================

APP_USER="lemonwar"
APP_DIR="/var/www/lemonwares"
DOMAIN="gadgets.lemonwares.com"
PHP_VERSION="8.4"
NODE_VERSION="24"
DB_NAME="lemonwares"
DB_USER="lemonwares_app"
DB_PASS=$(openssl rand -base64 24)

echo "================================================"
echo "  Lemonwares VPS Setup"
echo "================================================"

# --- System updates ---
apt update && apt upgrade -y

# --- Add PHP repo ---
apt install -y software-properties-common curl gnupg2
add-apt-repository -y ppa:ondrej/php
apt update

# --- Install PHP 8.4 + extensions ---
apt install -y \
  php${PHP_VERSION}-fpm \
  php${PHP_VERSION}-cli \
  php${PHP_VERSION}-pgsql \
  php${PHP_VERSION}-mbstring \
  php${PHP_VERSION}-xml \
  php${PHP_VERSION}-curl \
  php${PHP_VERSION}-zip \
  php${PHP_VERSION}-bcmath \
  php${PHP_VERSION}-intl \
  php${PHP_VERSION}-gd \
  php${PHP_VERSION}-tokenizer \
  php${PHP_VERSION}-fileinfo \
  php${PHP_VERSION}-dom

# --- Install Nginx ---
apt install -y nginx

# --- Install PostgreSQL ---
apt install -y postgresql postgresql-contrib

# --- Install Node.js ---
curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash -
apt install -y nodejs

# --- Install Composer ---
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# --- Install Git ---
apt install -y git

# --- Create app user ---
id -u $APP_USER &>/dev/null || useradd -m -s /bin/bash $APP_USER

# --- Setup PostgreSQL database ---
sudo -u postgres psql -c "CREATE USER ${DB_USER} WITH PASSWORD '${DB_PASS}';" 2>/dev/null || true
sudo -u postgres psql -c "CREATE DATABASE ${DB_NAME} OWNER ${DB_USER};" 2>/dev/null || true
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER};"

# --- Create app directory ---
mkdir -p $APP_DIR
chown $APP_USER:$APP_USER $APP_DIR

# --- Clone repo (you'll need to set this up) ---
echo ""
echo ">>> Clone your repo manually:"
echo "    su - $APP_USER"
echo "    git clone https://github.com/YOUR_USERNAME/lemonwaresbuild.git $APP_DIR"
echo ""

# --- Nginx config ---
cat > /etc/nginx/sites-available/$DOMAIN <<'NGINX'
server {
    listen 80;
    server_name DOMAIN_PLACEHOLDER;
    root /var/www/lemonwares/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/phpPHP_VERSION_PLACEHOLDER-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

# Replace placeholders
sed -i "s/DOMAIN_PLACEHOLDER/$DOMAIN/g" /etc/nginx/sites-available/$DOMAIN
sed -i "s/PHP_VERSION_PLACEHOLDER/$PHP_VERSION/g" /etc/nginx/sites-available/$DOMAIN

# Enable site
ln -sf /etc/nginx/sites-available/$DOMAIN /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test and restart
nginx -t && systemctl restart nginx
systemctl restart php${PHP_VERSION}-fpm

# --- SSL with Certbot ---
apt install -y certbot python3-certbot-nginx

echo ""
echo "================================================"
echo "  SETUP COMPLETE!"
echo "================================================"
echo ""
echo "  Database credentials:"
echo "    DB_CONNECTION=pgsql"
echo "    DB_HOST=localhost"
echo "    DB_PORT=5432"
echo "    DB_DATABASE=$DB_NAME"
echo "    DB_USERNAME=$DB_USER"
echo "    DB_PASSWORD=$DB_PASS"
echo ""
echo "  Next steps:"
echo "    1. Clone your repo to $APP_DIR"
echo "    2. Create .env file with the DB creds above"
echo "    3. Run: cd $APP_DIR && composer install --no-dev --optimize-autoloader"
echo "    4. Run: npm ci && npm run build"
echo "    5. Run: php artisan key:generate"
echo "    6. Run: php artisan migrate --force"
echo "    7. Run: php artisan db:seed --force"
echo "    8. Run: chown -R $APP_USER:www-data storage bootstrap/cache"
echo "    9. Run: chmod -R 775 storage bootstrap/cache"
echo "   10. Point DNS for $DOMAIN to this server IP"
echo "   11. Run: certbot --nginx -d $DOMAIN"
echo ""
echo "  For GitHub Actions deploy, add SSH key:"
echo "    ssh-keygen -t ed25519 -C deploy@lemonwares -f /home/$APP_USER/.ssh/deploy_key -N ''"
echo "    cat /home/$APP_USER/.ssh/deploy_key.pub >> /home/$APP_USER/.ssh/authorized_keys"
echo "    Then add the private key as CPANEL_SSH_KEY in GitHub secrets"
echo ""
