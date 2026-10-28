# Inventario Proxmox

Aplicación Laravel para inventariar clusters, nodos, máquinas virtuales, almacenamiento, interfaces de red, servicios y agentes. Incluye sincronización con Proxmox, auditorías SSH/Infransible, historial de VM, reglas NAT y un módulo de Proxmox Backup Server (PBS).

## Requisitos

- PHP 8.1 o superior, con las extensiones requeridas por Laravel y el driver de base de datos.
- Composer 2.
- MySQL/MariaDB recomendado para producción.
- Node.js y npm para compilar los assets frontend.
- Acceso de red desde el servidor web hacia las APIs Proxmox/PBS y los hosts auditados por SSH.
- Usuario de aplicación con permisos de escritura sobre `storage/` y `bootstrap/cache/`.

Las versiones y dependencias PHP exactas están declaradas en `composer.json`; las dependencias frontend están en `package.json`.

## Instalación desde cero

```bash
git clone git@github.com:JavierEsparza/InventarioProxmox.git
cd InventarioProxmox
composer install --no-interaction --prefer-dist
cp .env.example .env
php artisan key:generate
npm ci
npm run build
```

Editar `.env` con los valores reales antes de ejecutar migraciones:

```bash
php artisan migrate --seed
php artisan storage:link
php artisan optimize:clear
```

Para desarrollo:

```bash
php artisan serve
npm run dev
```

En producción, configurar el virtual host para apuntar a `public/`. No publicar la raíz del repositorio directamente.

## Configuración de entorno

El repositorio incluye `.env.example` sin secretos. Crear el archivo local con:

```bash
cp .env.example .env
php artisan key:generate
```

Variables principales:

- `APP_*`: nombre, ambiente, URL, debug y clave de cifrado Laravel.
- `DB_*`: conexión, host, puerto, base, usuario y contraseña de la base de datos.
- `LOG_*` y `PROXMOX_SYNC_LOG_*`: canales, nivel y retención de logs.
- `INFRANSIBLE_USER` y `INFRANSIBLE_PASSWORD`: credenciales usadas por las auditorías SSH.
- `NODE_SSH_USER` y `NODE_SSH_PASS`: credenciales del flujo de sincronización SSH heredado. `NODE_SSH_PASS` es leído como valor cifrado por el controlador; no colocar una contraseña plana allí.

Las conexiones de Proxmox y PBS se administran desde la aplicación y se persisten en la base de datos. No hardcodear tokens, contraseñas, claves SSH ni URLs privadas en el código.

Las demás variables Laravel opcionales (mail, Redis, S3, broadcasting, cache, queue y Sanctum) están listadas en `.env.example` y sólo son necesarias si se habilita el componente correspondiente.

## Datos y módulos

Las migraciones crean las tablas de:

- Inventario de nodos, VM, interfaces y direcciones.
- Historial y eventos de máquinas virtuales.
- Auditorías de cluster y resultados por host.
- Estado de servicios/agentes.
- Reglas NAT.
- Servidores, datastores, snapshots y tareas PBS.
- Registros de sincronización.

Los seeders incluidos cargan permisos y datos iniciales relacionados con el inventario. Revisar los seeders antes de ejecutarlos en una base existente.

## Comandos Artisan propios

```bash
php artisan proxmox:sync-daily
php artisan pbs:sync-all
php artisan pbs:sync-all --server=ID_O_NOMBRE
php artisan qemu:refresh-qga-ip-os
php artisan qemu:refresh-qga-ip-os --limit=100
php artisan qemu:import-ip-os-pdf ruta/al/archivo.csv --dry-run
php artisan proxmox:import-ips ruta/al/archivo.csv
```

Los comandos de sincronización pueden modificar datos de inventario. Ejecutarlos primero en una ventana controlada y revisar los logs del canal `proxmox_sync`.

## Funcionalidades principales

- Inventario global de clusters, nodos, VM y storage.
- Sincronización de datos desde Proxmox API.
- Sincronización SSH de hardware, interfaces y servicios.
- Conservación de datos manuales cuando un nodo está offline o devuelve información vacía.
- Auditoría por cluster con SSH, sudo, VIP/Keepalived, HAProxy y agentes.
- Detección de QEMU Guest Agent y origen IP/OS.
- Historial mensual y anual de cambios de VM.
- Módulo PBS para datastores, tareas, snapshots y comparación de backups.
- Administración de usuarios, roles y permisos.
- Exportaciones y búsquedas del inventario.

## Verificación

```bash
php -l app/Http/Controllers/ProxmoxController.php
php artisan about
php artisan route:list
php artisan config:clear
php artisan view:clear
php artisan view:cache
php artisan test
```

Ejecutar `view:clear` y `view:cache` como el usuario de la aplicación, no como root, para evitar archivos compilados con ownership incorrecto.

Antes de una sincronización o auditoría, verificar conectividad y permisos desde el mismo servidor donde corre Laravel. Separar siempre conectividad TCP, autenticación SSH, ejecución remota, persistencia en base y renderizado de la interfaz.

## Logs

El canal `proxmox_sync` escribe en `storage/logs/proxmox-sync.log` por defecto. Los logs, caches, sesiones, archivos importados y respaldos de ejecución son runtime y no forman parte del repositorio.

## Seguridad y archivos excluidos

Nunca subir:

- `.env` o credenciales reales.
- Tokens Proxmox/PBS, contraseñas o claves privadas.
- `vendor/`, `node_modules/` y caches generados.
- `storage/logs/`, `storage/framework/` y datos runtime.
- Backups `.bak*`, `.bkp*` y la carpeta `backups/`.

Los respaldos históricos del proyecto se conservan fuera del repositorio, en el servidor operativo.

## Flujo de actualización

```bash
git pull --ff-only origin main
composer install --no-interaction --prefer-dist
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
```

Revisar el diff y ejecutar las validaciones antes de publicar. Los reinicios o recargas de servicios deben coordinarse según el procedimiento operativo del entorno.

## Licencia

Este repositorio contiene el código operativo del inventario Proxmox de la organización. Definir la licencia corporativa antes de distribuirlo fuera del entorno autorizado.
