#!/bin/sh
set -eu

# Enforce a single Apache MPM at runtime to avoid "More than one MPM loaded".
rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf
rm -f /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf
ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

APP_PORT="${PORT:-8080}"
sed -ri "s/^Listen [0-9]+$/Listen ${APP_PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${APP_PORT}>/" /etc/apache2/sites-available/000-default.conf
printf 'ServerName localhost\n' > /etc/apache2/conf-available/servername.conf
a2enconf servername >/dev/null

# Create configuration to block sensitive directories
cat > /etc/apache2/conf-available/block-sensitive-dirs.conf << 'EOF'
<Directory /var/www/html/storage>
    Deny from all
</Directory>
<Directory /var/www/html/storage/uploads>
    Allow from all
</Directory>
<Directory /var/www/html/vendor>
    Deny from all
</Directory>
<FilesMatch "^(bootstrap|config)\.php$">
    Deny from all
</FilesMatch>
EOF
a2enconf block-sensitive-dirs >/dev/null

# Ensure upload directory exists and has proper permissions
mkdir -p /var/www/html/storage/uploads
chmod 755 /var/www/html/storage/uploads
chown www-data:www-data /var/www/html/storage/uploads

# Ensure PHP can write uploads
chown -R www-data:www-data /var/www/html/storage/

echo "[startup] Apache MPM symlinks:"
ls -1 /etc/apache2/mods-enabled/mpm_*.load 2>/dev/null || true
echo "[startup] Apache loaded MPM modules:"
apache2ctl -M 2>/dev/null | grep -E 'mpm_(event|worker|prefork)_module' || true
echo "[startup] Apache listen port: ${APP_PORT}"

exec apache2-foreground
