#!/usr/bin/env bash
# =====================================================
#  Se ejecuta EN CADA instancia web (Ng1, Ng2, Ng3) como root.
#  Instala PHP-FPM y configura nginx para la agenda.
#  Lo llama instalar.sh, que antes copia nginx-agenda.conf a /tmp.
# =====================================================
set -euo pipefail

export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq nginx php-fpm php-mysql php-cli rsync >/dev/null

# Detectar el socket de php-fpm (8.1 en Ubuntu 22.04, 8.3 en 24.04...)
PHP_VER=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
PHP_SOCK="/run/php/php${PHP_VER}-fpm.sock"
systemctl enable --now "php${PHP_VER}-fpm"

# Configuración de nginx (se respalda la original una sola vez)
[ -f /etc/nginx/sites-available/default.original ] || \
    cp /etc/nginx/sites-available/default /etc/nginx/sites-available/default.original
sed "s#__PHP_SOCK__#${PHP_SOCK}#" /tmp/nginx-agenda.conf > /etc/nginx/sites-available/default
ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

mkdir -p /var/www/html/agenda
nginx -t
systemctl reload nginx

# Firewall interno de Ubuntu en OCI (iptables): asegurar el puerto 80
if ! iptables -C INPUT -p tcp --dport 80 -j ACCEPT 2>/dev/null; then
    iptables -I INPUT 1 -p tcp --dport 80 -j ACCEPT
    command -v netfilter-persistent >/dev/null && netfilter-persistent save >/dev/null || true
fi

echo "[$(hostname)] nginx + PHP ${PHP_VER} listos"
