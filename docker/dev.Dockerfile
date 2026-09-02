# syntax=docker/dockerfile:1
#
# Local development image: runs the FrankenPHP web server (same engine used
# in production) directly against the source tree, mounted as a volume by
# docker-compose.dev.yml. Not used for building release artifacts — see
# docker/linux-static-build.Dockerfile and docker/package.Dockerfile for that.
FROM dunglas/frankenphp:php8.4

RUN install-php-extensions \
    bcmath \
    gd \
    intl \
    pdo_mysql \
    pdo_pgsql \
    redis \
    soap \
    xsl \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
