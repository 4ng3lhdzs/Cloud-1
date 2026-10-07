#!/usr/bin/env bash
# =====================================================
#  Instalación inicial (se corre UNA vez desde su computadora)
#   1. MySQL en Ng1 + importar database/agenda.sql
#   2. nginx + PHP-FPM en Ng1, Ng2 y Ng3
#   3. Sube la agenda a las 3 instancias (sync.sh)
#
#  Uso:  bash deploy/instalar.sh
#  (en Windows: desde Git Bash o la terminal "Git Bash" de VS Code)
# =====================================================
set -euo pipefail
DIR="$(cd "$(dirname "$0")" && pwd)"
RAIZ="$(dirname "$DIR")"
source "$DIR/servidores.conf"

SSH_OPTS=(-i "$SSH_KEY" -o StrictHostKeyChecking=accept-new -o ConnectTimeout=10)

echo "==> 1/3 Base de datos en $DB_PUBLIC_IP"
scp "${SSH_OPTS[@]}" "$RAIZ/database/agenda.sql" "$DIR/setup_db.sh" "$SSH_USER@$DB_PUBLIC_IP:/tmp/"
ssh "${SSH_OPTS[@]}" "$SSH_USER@$DB_PUBLIC_IP" "sudo bash /tmp/setup_db.sh '$RED_PRIVADA' && rm -f /tmp/agenda.sql"

echo "==> 2/3 nginx + PHP en las instancias web"
for ip in "${WEB_SERVERS[@]}"; do
    echo "--- $ip"
    scp "${SSH_OPTS[@]}" "$DIR/nginx-agenda.conf" "$DIR/setup_web.sh" "$SSH_USER@$ip:/tmp/"
    ssh "${SSH_OPTS[@]}" "$SSH_USER@$ip" "sudo bash /tmp/setup_web.sh"
    # Probar que la instancia web alcanza a MySQL por la red privada
    ssh "${SSH_OPTS[@]}" "$SSH_USER@$ip" \
        "timeout 3 bash -c '</dev/tcp/$DB_PRIVATE_IP/3306' && echo '   MySQL alcanzable desde '\$(hostname) || echo '   ¡ERROR! '\$(hostname)' no llega a $DB_PRIVATE_IP:3306 (revise la Security List)'"
done

echo "==> 3/3 Subiendo la agenda"
bash "$DIR/sync.sh"

echo
echo "Listo. Abra http://$LB_IP/agenda/  (usuario: admin / contraseña: Admin2026!)"
