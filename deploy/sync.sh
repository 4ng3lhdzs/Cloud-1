#!/usr/bin/env bash
# =====================================================
#  Sincroniza la estructura de carpetas del proyecto con las 3 instancias.
#  Córralo cada vez que modifique el código:
#
#      bash deploy/sync.sh            -> sube a Ng1, Ng2 y Ng3
#      bash deploy/sync.sh 1.2.3.4    -> sube solo a esa IP
#
#  Usa tar + ssh (funciona en Linux, macOS y Git Bash de Windows, sin rsync).
#  Cada servidor recibe una copia limpia: lo que borre localmente
#  también desaparece del servidor.
# =====================================================
set -euo pipefail
DIR="$(cd "$(dirname "$0")" && pwd)"
RAIZ="$(dirname "$DIR")"
source "$DIR/servidores.conf"

SSH_OPTS=(-i "$SSH_KEY" -o StrictHostKeyChecking=accept-new -o ConnectTimeout=10)
[ $# -gt 0 ] && DESTINOS=("$@") || DESTINOS=("${WEB_SERVERS[@]}")

for ip in "${DESTINOS[@]}"; do
    echo "==> Sincronizando con $ip:$RUTA_REMOTA"
    tar -C "$RAIZ" -czf - \
        --exclude=.git --exclude=.vscode --exclude=deploy --exclude='*.zip' . \
    | ssh "${SSH_OPTS[@]}" "$SSH_USER@$ip" "
        set -e
        sudo rm -rf '$RUTA_REMOTA.nuevo'
        sudo mkdir -p '$RUTA_REMOTA.nuevo'
        sudo tar -xzf - -C '$RUTA_REMOTA.nuevo'
        sudo chown -R www-data:www-data '$RUTA_REMOTA.nuevo'
        sudo find '$RUTA_REMOTA.nuevo' -type d -exec chmod 755 {} +
        sudo find '$RUTA_REMOTA.nuevo' -type f -exec chmod 644 {} +
        sudo rm -rf '$RUTA_REMOTA.anterior'
        [ -d '$RUTA_REMOTA' ] && sudo mv '$RUTA_REMOTA' '$RUTA_REMOTA.anterior'
        sudo mv '$RUTA_REMOTA.nuevo' '$RUTA_REMOTA'
        echo \"   OK en \$(hostname)\"
    "
done

echo "Sincronización terminada."
