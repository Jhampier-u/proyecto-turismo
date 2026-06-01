#!/usr/bin/env bash
set -e

cd /var/www/html

echo "▶ Esperando variables de entorno..."

# Generar APP_KEY si no viene seteada (solo primera vez)
if [ -z "${APP_KEY:-}" ]; then
    echo "⚠ APP_KEY no definida. Genera una con: php artisan key:generate --show"
fi

# El disco persistente de Render monta en /var/data; usamos esa ruta
# para storage/app/public si está disponible.
if [ -d "/var/data" ]; then
    echo "▶ Disco persistente detectado en /var/data"
    mkdir -p /var/data/app/public
    rm -rf storage/app/public
    ln -s /var/data/app /var/www/html/storage/app
    chown -R www-data:www-data /var/data
fi

# Crear el symlink público → storage/app/public
php artisan storage:link --force || true

echo "▶ Optimizando configuración..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "▶ Ejecutando migraciones..."
php artisan migrate --force

echo "✓ App lista. Iniciando Apache en el puerto ${PORT}"
exec "$@"
