#!/usr/bin/env bash
# =====================================================
#  Se ejecuta SOLO en la instancia de base de datos (Ng1) como root.
#  Instala MySQL, lo abre a la red privada de la VCN e importa agenda.sql
#  Uso (lo llama instalar.sh):  sudo bash setup_db.sh 10.0.0.0/24
# =====================================================
set -euo pipefail
RED_PRIVADA="${1:-10.0.0.0/24}"

export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq mysql-server >/dev/null
systemctl enable --now mysql

# Escuchar en todas las interfaces (el firewall limita quién entra)
CNF=/etc/mysql/mysql.conf.d/mysqld.cnf
sed -i -E 's/^\s*bind-address\s*=.*/bind-address = 0.0.0.0/' "$CNF"
sed -i -E 's/^\s*mysqlx-bind-address\s*=.*/mysqlx-bind-address = 127.0.0.1/' "$CNF"
grep -q '^bind-address' "$CNF" || echo 'bind-address = 0.0.0.0' >> "$CNF"
systemctl restart mysql

# Crear base de datos, usuario y tablas
mysql < /tmp/agenda.sql

# Firewall interno de Ubuntu (iptables): permitir 3306 SOLO desde la red privada
if ! iptables -C INPUT -p tcp -s "$RED_PRIVADA" --dport 3306 -j ACCEPT 2>/dev/null; then
    iptables -I INPUT 1 -p tcp -s "$RED_PRIVADA" --dport 3306 -j ACCEPT
    command -v netfilter-persistent >/dev/null && netfilter-persistent save >/dev/null || true
fi

echo "[$(hostname)] MySQL listo, aceptando conexiones desde $RED_PRIVADA"
mysql -e "SELECT user, host FROM mysql.user WHERE user='agenda_user';"
