FROM php:8.4-fpm-alpine AS base
RUN apk update && apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    zip \
    unzip \
    git \
    curl \
    nodejs \
    npm \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip mbstring intl pdo_mysql
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www


FROM base AS dev
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
USER root
ENTRYPOINT ["entrypoint.sh"]


FROM base AS build-assets
COPY . .
RUN composer install && npm install && npm run build


FROM base AS test
COPY --exclude=.env --exclude=.env.prod . .
COPY --from=build-assets /var/www/public/build ./public/build
COPY --from=build-assets /var/www/vendor ./vendor
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache .
USER www-data


FROM base AS prod
COPY --exclude=.env --exclude=.env.test . .
COPY --from=build-assets /var/www/public/build ./public/build
COPY --from=build-assets /var/www/vendor ./vendor
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache .
USER www-data
CMD ["php-fpm"]


FROM nginx:alpine AS nginx
COPY --from=build-assets /var/www/public /var/www/public
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
WORKDIR /var/www