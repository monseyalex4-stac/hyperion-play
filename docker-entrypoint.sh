#!/bin/sh
set -e

echo "=== Iniciando Hyperion Play en Render ==="

# 1. Asegurar archivo .env
if [ ! -f .env ]; then
    echo "Copiando .env.example a .env..."
    cp .env.example .env
fi

# 2. Generar APP_KEY válida si no existe
echo "Verificando clave de aplicación..."
php artisan key:generate --force

# 3. Preparar base de datos SQLite y carpetas de almacenamiento
echo "Preparando base de datos SQLite y permisos..."
mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
touch database/database.sqlite
chmod -R 777 storage bootstrap/cache database

# 4. Migraciones y Seeders
echo "Ejecutando migraciones..."
php artisan migrate --force

echo "Sembrando datos iniciales..."
php artisan db:seed --force || true

# 5. Iniciar servidor web de Laravel
PORT_TO_USE="${PORT:-8000}"
echo "Servidor listo. Escuchando en 0.0.0.0:${PORT_TO_USE}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT_TO_USE}"
