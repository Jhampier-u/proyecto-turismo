# =========================================================
#  Etapa 1: build de assets con Node (Vite + Tailwind)
# =========================================================
FROM node:20-alpine AS assets

WORKDIR /app
COPY package*.json vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources
RUN npm ci && npm run build


# =========================================================
#  Etapa 2: runtime PHP 8.2 + Apache
# =========================================================
FROM php:8.2-apache

# ---- Dependencias del sistema y extensiones PHP ----------
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libpng-dev libonig-dev libxml2-dev \
        libpq-dev libicu-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql pdo_mysql mbstring zip exif pcntl bcmath gd intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ---- Composer --------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ---- Configuración Apache --------------------------------
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite headers

# Render expone el puerto vía $PORT
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:${PORT}>/g' /etc/apache2/sites-available/000-default.conf

# ---- Código de la app ------------------------------------
WORKDIR /var/www/html
COPY . .

# Assets compilados desde la etapa 1
COPY --from=assets /app/public/build ./public/build

# ---- Dependencias PHP en modo producción -----------------
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# ---- Permisos --------------------------------------------
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

# ---- Entrypoint ------------------------------------------
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENV PORT=8080
EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
