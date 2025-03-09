FROM php:8.1-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    unzip \
    git \
    curl

# Instalar extensiones PHP necesarias
RUN docker-php-ext-install pdo pdo_mysql

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer el directorio de trabajo
WORKDIR /var/www

# Copiar el código de la aplicación
COPY . /var/www

# Ajustar permisos
RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www

# Instalar dependencias de Composer (sin dev para producción; en pruebas se pueden incluir)
RUN composer install --optimize-autoloader

EXPOSE 9000

CMD ["php-fpm"]
