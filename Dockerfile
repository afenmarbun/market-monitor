FROM node:22-alpine AS assets
WORKDIR /app
ARG VITE_REVERB_HOST=localhost
ARG VITE_REVERB_PORT=8080
ARG VITE_REVERB_SCHEME=http
ARG VITE_REVERB_APP_KEY=market-monitor-key
ENV VITE_REVERB_HOST=$VITE_REVERB_HOST VITE_REVERB_PORT=$VITE_REVERB_PORT VITE_REVERB_SCHEME=$VITE_REVERB_SCHEME VITE_REVERB_APP_KEY=$VITE_REVERB_APP_KEY
COPY package*.json ./
RUN npm ci
COPY resources ./resources
COPY components ./components
COPY vite.config.js .
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

FROM php:8.4-fpm-alpine AS runtime
RUN apk add --no-cache icu-dev oniguruma-dev libzip-dev $PHPIZE_DEPS \
    && docker-php-ext-install bcmath intl opcache pcntl pdo_mysql \
    && apk del $PHPIZE_DEPS
WORKDIR /var/www/html
COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build
RUN cp .env.example .env.production.example \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache
USER www-data
EXPOSE 9000
CMD ["php-fpm", "-F"]

FROM nginx:1.27-alpine AS web
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=runtime /var/www/html/public /var/www/html/public
