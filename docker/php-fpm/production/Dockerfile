FROM php:8.2-fpm

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY ./common/conf.d/www.conf /usr/local/etc/php-fpm.d/www.conf

RUN apt update && apt install -y \
        gosu \
        cron \
        git \
        curl \
        libpng-dev \
        libjpeg-dev \
        libwebp-dev \
        libfreetype6-dev \
        libgmp-dev \
        libssl-dev \
        zlib1g-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        libicu-dev \
        zip \
        unzip \
        supervisor \
        build-essential \
        pkg-config \
        openssl \
        && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd \
    --with-jpeg \
    --with-webp \
    --with-freetype \
    --enable-gd
RUN docker-php-ext-configure intl

RUN docker-php-ext-install -j$(nproc) \
    gd \
    intl \
    pdo \
    pdo_mysql \
    mysqli \
    zip \
    pcntl \
    opcache \
    bcmath \
    sockets \
    gmp \
    exif

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --2

COPY ./production/conf.d/laravel-horizon.conf /etc/supervisor/conf.d/laravel-horizon.conf
COPY ./production/conf.d/laravel-scheduler.conf /etc/supervisor/conf.d/laravel-scheduler.conf

RUN groupadd -g 988 docker && useradd -s /bin/bash -u 1000 -g 988 -m -d /home/docker docker
RUN usermod -aG docker www-data
RUN chown -R www-data:www-data /var/www/html

USER docker

WORKDIR /var/www/html

CMD ["php-fpm"]
