#!/bin/bash

# --- TUS DATOS ---
PMX_IP="192.168.100.181"
NODE="pve-desarrollo03"    # <--- Asegúrate que este sea el nombre correcto del nodo en esa IP
VMID="8010"                 # <--- La VM que estamos probando
TOKEN_ID="monitoreoTI@pam!InventarioInfra"
TOKEN_SECRET="aebd955d-3c15-46f6-bf40-fbb35860fa69"
# -----------------

# Construimos la cabecera de autorización para Token
AUTH_HEADER="Authorization: PVEAPIToken=$TOKEN_ID=$TOKEN_SECRET"

echo "--- 1. Enviando comando 'systemctl is-active zabbix-agent' usando Token..."

EXEC_DATA=$(curl -s -k -X POST \
    -H "$AUTH_HEADER" \
    --data-urlencode "command=systemctl" \
    --data-urlencode "command=is-active" \
    --data-urlencode "command=zabbix-agent2" \
    "https://$PMX_IP:8006/api2/json/nodes/$NODE/qemu/$VMID/agent/exec")

# Usamos PHP para sacar el PID del JSON
PID=$(echo $EXEC_DATA | php -r '$d=json_decode(fgets(STDIN)); echo $d->data->pid ?? "null";')

if [ "$PID" == "null" ] || [ -z "$PID" ]; then
    echo "❌ Error al ejecutar. Respuesta del servidor:"
    echo $EXEC_DATA
    exit 1
fi

echo "✅ Comando enviado. PID: $PID"
echo "--- 2. Esperando 2 segundos..."
sleep 2

echo "--- 3. Leyendo resultado RAW..."
RESULT=$(curl -s -k \
    -H "$AUTH_HEADER" \
    "https://$PMX_IP:8006/api2/json/nodes/$NODE/qemu/$VMID/agent/exec-status?pid=$PID")

echo ""
echo "============================================="
echo "LO QUE REALMENTE ENTREGA PROXMOX:"
echo "============================================="
echo $RESULT
echo "============================================="
