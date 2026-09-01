# syntax=docker/dockerfile:1
# HElbaron API — production image (PHP 8.3 FPM, OPcache, no dev deps).

# ---- Stage 1: composer deps (no dev) ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# Download-only stage: the composer:2 image ships a minimal PHP without ext-intl/ext-pcntl (required
# by filament/support and laravel/horizon), so platform checks are ignored HERE. The runtime stage
# below installs those extensions (intl, pcntl, …), so the actual image satisfies every requirement.
RUN composer install --no-dev --prefer-dist --no-scripts --no-autoloader --no-interaction --ignore-platform-reqs

# ---- Stage 2: runtime ----
FROM php:8.3-fpm-alpine AS runtime

# Patch OS packages to their latest security-fixed versions (addresses fixable CRITICAL/HIGH OS
# CVEs the base image may lag on; the image is Trivy-scanned with ignore-unfixed).
RUN apk --no-cache upgrade

# Pinned installer release (supply-chain: never track "latest").
ADD https://github.com/mlocati/docker-php-extension-installer/releases/download/2.7.14/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
    && apk add --no-cache --virtual .redis-build-deps $PHPIZE_DEPS \
    && pecl install redis-6.3.0 \
    && docker-php-ext-enable redis \
    && apk del .redis-build-deps \
    && install-php-extensions pdo_pgsql pgsql mbstring bcmath intl zip pcntl opcache \
    && apk add --no-cache fcgi

WORKDIR /var/www/html

# App code + vendored deps, then optimized autoloader.
COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && chown -R www-data:www-data storage bootstrap/cache

# Production PHP + OPcache tuning.
COPY infra/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY infra/php/php.ini /usr/local/etc/php/conf.d/zz-helbaron.ini

# Build identity. Supplied by CI (--build-arg APP_BUILD_SHA=$GITHUB_SHA) and surfaced by
# GET /api/v1/version, so an operator can tell which commit an instance is actually running instead
# of trusting the image tag. Unset in a local build, which is reported honestly as "unknown".
ARG APP_BUILD_SHA=""
ARG APP_RELEASE=""
ENV APP_BUILD_SHA=${APP_BUILD_SHA} \
    APP_RELEASE=${APP_RELEASE}

# Warms config/route/event caches from THIS container's environment before handing off to the given
# command (php-fpm, horizon, schedule:work). See the script for why it cannot be done at deploy time.
COPY --chmod=0755 infra/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

# Container healthcheck that asks the APPLICATION, not just the socket. See the script.
COPY --chmod=0755 infra/php/fpm-healthcheck.sh /usr/local/bin/fpm-healthcheck.sh

# Created here, with the right owner, so the named volume mounted over it inherits that ownership on
# first use. Docker creates a fresh volume's directory as root otherwise, and the entrypoint runs as
# www-data — it would be unable to publish anything into its own asset volume.
RUN mkdir -p /srv/filament-assets && chown www-data:www-data /srv/filament-assets

# Asks the app's liveness route through FPM and checks the BODY. The previous probe requested /ping,
# which is a php-fpm POOL setting enabled nowhere in this repository, so cgi-fcgi received
# "Primary script unknown" and still exited 0 — reporting a wedged worker pool as healthy.
HEALTHCHECK --interval=30s --timeout=10s --retries=3 --start-period=30s \
    CMD /usr/local/bin/fpm-healthcheck.sh

EXPOSE 9000

# Run the FPM master as www-data (storage/ and bootstrap/cache are chowned above).
USER www-data
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
