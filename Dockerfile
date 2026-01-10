FROM wordpress:php8.4-fpm

ARG WP_PLUGINS="query-monitor sharing-image wps-hide-login"

# Ensure curl/unzip are available so we can download plugin zips during build.
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends curl unzip; \
    rm -rf /var/lib/apt/lists/*

# Pre-install plugins that should be available inside every image.
RUN set -eux; \
    for plugin in $WP_PLUGINS; do \
        curl -fsSL -o /tmp/${plugin}.zip https://downloads.wordpress.org/plugin/${plugin}.latest-stable.zip; \
        unzip -q /tmp/${plugin}.zip -d /usr/src/wordpress/wp-content/plugins; \
        rm -f /tmp/${plugin}.zip; \
    done

COPY public/ /usr/src/wordpress/wp-content/themes/blok45
COPY mu-plugins/ /usr/src/wordpress/wp-content/mu-plugins/
