FROM phpdockerio/php:8.3-fpm
WORKDIR "/app"

RUN apt-get update \
    && apt-get -y --no-install-recommends install \
        php8.3-pgsql \
        php8.3-xdebug \
        php8.3-intl \
        php8.3-imagick \
        php8.3-amqp \
        ffmpeg \
        imagemagick \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Копирование исходного кода в контейнер
COPY . .

# Установка зависимостей Symfony
#RUN composer install

# Создание директории для загрузки файлов и настройка прав
RUN mkdir -p /app/uploads/private \
    && chown -R www-data:www-data /app/uploads

# Настройка прав доступа к рабочей директории (если необходимо)
RUN chown -R www-data:www-data /app