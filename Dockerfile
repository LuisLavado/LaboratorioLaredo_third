FROM php:8.2-apache

# Instalar extensiones PHP y herramientas
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    default-mysql-client \
    supervisor \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar Apache
RUN a2enmod rewrite headers
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's/Listen 80/Listen 8000/g' /etc/apache2/ports.conf
RUN sed -i 's/:80>/:8000>/g' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# Copiar archivos
COPY . .

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader

# Crear directorios necesarios
RUN mkdir -p storage/framework/cache/data \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/app/temp \
    && mkdir -p storage/fonts \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache

# Permisos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Configurar supervisor
COPY <<'EOF' /etc/supervisor/conf.d/supervisord.conf
[supervisord]
nodaemon=true
user=root
logfile=/var/log/supervisor/supervisord.log
pidfile=/var/run/supervisord.pid

[program:apache]
command=/usr/sbin/apache2ctl -D FOREGROUND
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:reverb]
command=php /var/www/html/artisan reverb:start --host=0.0.0.0 --port=8080
directory=/var/www/html
user=www-data
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
EOF

# Script de inicio
COPY <<'EOF' /start.sh
#!/bin/bash
set -e

# Asegurar permisos correctos en storage (importante cuando se usan volúmenes)
echo "Configurando permisos de storage..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Esperar a que la BD esté disponible (si es necesario)
if [ ! -z "$DB_HOST" ] && [ "$DB_HOST" != "localhost" ]; then
    echo "Esperando a que la base de datos esté disponible..."
    until php artisan db:monitor 2>/dev/null; do
        echo "Reintentando conexión a la base de datos..."
        sleep 3
    done
fi

# Verificar si hay migraciones previas
if php artisan tinker --execute="echo Schema::hasTable('migrations') ? 'yes' : 'no';" 2>/dev/null | grep -q "yes"; then
    # Ya hay migraciones, solo migrate
    php artisan migrate --force
else
    # Primera vez, migrate + seed
    php artisan migrate --force
    php artisan db:seed --force
fi

# Crear storage link
php artisan storage:link || true

# Iniciar supervisor (Apache + Reverb)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
EOF

RUN chmod +x /start.sh

EXPOSE 8000 8080

CMD ["/start.sh"]
