FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring zip bcmath \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN php artisan key:generate --force
RUN php artisan jwt:secret --force

EXPOSE 8000

CMD sh -c "php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=8000"
