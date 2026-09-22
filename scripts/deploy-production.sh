#!/usr/bin/env bash
#
# Despliegue de Gofio en servidor Linux (VPS).
# Uso: ./scripts/deploy-production.sh [--first-run]
#
# Requisitos en el servidor: PHP 8.2+, Composer, pnpm, MySQL, Redis (recomendado).
# Ejecutar desde la raíz del proyecto: bash scripts/deploy-production.sh
#
# Orden alineado con composer deploy:update (migrate antes de sync; optimize:clear antes de cache).

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

FIRST_RUN=false
if [[ "${1:-}" == "--first-run" ]]; then
    FIRST_RUN=true
fi

echo "==> Gofio — despliegue en producción"
echo "    Directorio: $ROOT"

# Comprueba herramientas necesarias
command -v php >/dev/null || { echo "Error: PHP no encontrado."; exit 1; }
command -v composer >/dev/null || { echo "Error: Composer no encontrado."; exit 1; }
command -v pnpm >/dev/null || { echo "Error: pnpm no encontrado. Instala con: corepack enable"; exit 1; }

if [[ ! -f .env ]]; then
    echo "Error: falta .env. Copia .env.example y configura APP_URL, DB_*, REVERB_*, etc."
    exit 1
fi

# Dependencias PHP (sin dev)
echo "==> Composer install (producción)..."
composer install --no-dev --optimize-autoloader --no-interaction

# Dependencias frontend y build de assets
echo "==> pnpm install + build..."
pnpm install --frozen-lockfile
pnpm run build

# Migraciones antes de sync (tablas requeridas por themes/icon-packs)
echo "==> Migraciones..."
php artisan migrate --force --no-interaction

# Enlace storage y sincronización de temas/iconos
echo "==> Storage, temas e icon packs..."
php artisan storage:link --force 2>/dev/null || php artisan storage:link
php artisan gofio:themes:sync --no-interaction
php artisan gofio:icon-packs:sync --no-interaction

# Limpia cachés obsoletas antes de regenerar (igual que composer optimize:clear)
echo "==> Limpieza de cachés..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear 2>/dev/null || true

# Cachés de Laravel (igual que composer optimize:prod)
echo "==> Optimización de cachés..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache 2>/dev/null || true

# Permisos (solo si el script se ejecuta con sudo o el usuario es propietario)
if [[ -d storage && -d bootstrap/cache ]]; then
    chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true
fi

echo ""
echo "==> Despliegue completado."
echo "    Recuerda reiniciar workers y Reverb:"
echo "      sudo supervisorctl restart gofio-worker:*"
echo "      sudo supervisorctl restart gofio-reverb"
echo "    Cron del scheduler (cada minuto):"
echo "      * * * * * cd $ROOT && php artisan schedule:run >> /dev/null 2>&1"
