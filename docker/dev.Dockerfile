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

# The base image's default memory_limit (256M) isn't enough to run
# `composer install`'s post-install `cache:clear` step (or `bin/console
# cache:clear` in general) once the project grows past a certain number of
# bundles/services — it OOMs and takes the whole container down on every
# restart. Development only; production builds warm the cache at image build
# time, before traffic, so this doesn't apply there.
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory-limit.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
