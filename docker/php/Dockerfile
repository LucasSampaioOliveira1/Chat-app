FROM php:8.2-fpm

# Instalar dependências
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev

# Instalar extensões PHP necessárias
RUN docker-php-ext-install pdo pdo_mysql sockets zip

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Definir o diretório de trabalho
WORKDIR /var/www/html
