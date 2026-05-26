FROM php:8.2-fpm

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY ./common/conf.d/www.conf /usr/local/etc/php-fpm.d/www.conf

RUN apt update && apt install -y \
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

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_port=9005" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --2

RUN groupadd -g 988 docker && useradd -s /bin/bash -u 1000 -g 988 -m -d /home/docker docker
RUN usermod -aG docker www-data
RUN chown -R www-data:www-data /var/www/html

USER docker

WORKDIR /var/www/html

CMD ["php-fpm"]
