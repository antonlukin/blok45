FROM composer:2 AS s3uploads-build

ARG S3_UPLOADS_VERSION=^3.0

WORKDIR /build

RUN set -eux; \
  composer init --no-interaction --name blok45/s3-uploads-build; \
  composer config --no-interaction platform.php 8.4.0; \
  composer require --no-interaction --no-progress "humanmade/s3-uploads:${S3_UPLOADS_VERSION}"; \
  composer install --no-dev --classmap-authoritative --no-interaction --no-progress

FROM wordpress:php8.4-fpm

ARG WP_PLUGINS="query-monitor sharing-image wps-hide-login"

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends curl unzip; \
    rm -rf /var/lib/apt/lists/*

RUN set -eux; \
    for plugin in $WP_PLUGINS; do \
        curl -fsSL -o /tmp/${plugin}.zip https://downloads.wordpress.org/plugin/${plugin}.latest-stable.zip; \
        unzip -q /tmp/${plugin}.zip -d /usr/src/wordpress/wp-content/plugins; \
        rm -f /tmp/${plugin}.zip; \
    done

COPY public/ /usr/src/wordpress/wp-content/themes/blok45
COPY mu-plugins/ /usr/src/wordpress/wp-content/mu-plugins/

COPY --from=s3uploads-build /build/vendor /usr/src/wordpress/wp-content/mu-plugins/vendor
