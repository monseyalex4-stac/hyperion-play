# Entorno de Node para compilar assets (si los hay)
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build || true

# Entorno PHP 8.3 de Producción
FROM php:8.3-cli-alpine

# Instalar dependencias del sistema y extensiones de PHP requeridas por Laravel
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite \
    sqlite-dev \
    libzip-dev \
    bash \
    python3 \
    py3-pip

# Instalar extensiones PHP (SQLite, PDO, etc.)
RUN docker-php-ext-install pdo pdo_sqlite bcmath opcache pcntl

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar archivos del proyecto
COPY . .
COPY --from=frontend /app/public/build ./public/build

# Instalar dependencias de PHP sin dev para producción
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Crear base de datos SQLite si no existe y asegurar permisos
RUN mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && touch database/database.sqlite \
    && chmod -R 777 storage bootstrap/cache database

# Puerto por defecto (Render / Railway asignan PORT)
ENV PORT=8000
EXPOSE 8000

# Script de inicio
CMD sh -c "php artisan key:generate --force && php artisan migrate --force && php artisan db:seed --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan serve --host=0.0.0.0 --port=${PORT}"
