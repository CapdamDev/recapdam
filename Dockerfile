# Production image: PHP 8.5 + Apache serving public/. See DEPLOY.md.
# The database runs in its own container (docker-compose.prod.yml); nothing is installed on the host.
FROM php:8.5-apache

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# intl: Filament · pdo_mysql: MariaDB · gd: image previews · zip/bcmath: Laravel · opcache: speed.
# iconv, mbstring, xml, curl and fileinfo are built into the base image.
RUN install-php-extensions intl pdo_mysql gd zip bcmath opcache \
    && a2enmod rewrite headers remoteip \
    && rm -rf /var/lib/apt/lists/*

COPY docker/app/php.ini "$PHP_INI_DIR/conf.d/zz-recapdam.ini"
COPY docker/app/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/app/entrypoint.sh /usr/local/bin/recapdam-entrypoint

WORKDIR /var/www/html

# Dependencies first so code changes don't reinstall them.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .

# Runs package:discover and filament:upgrade (publishes Filament's CSS/JS into public/).
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && php artisan storage:link \
    && chmod +x /usr/local/bin/recapdam-entrypoint \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -fsS http://127.0.0.1/up >/dev/null || exit 1

ENTRYPOINT ["recapdam-entrypoint"]
CMD ["apache2-foreground"]
