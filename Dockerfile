FROM wordpress:php8.4-fpm

# Ensure curl/unzip are available so we can download plugin zips during build.
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends curl unzip; \
    rm -rf /var/lib/apt/lists/*

# Pre-install plugins that should be available inside every image.
RUN set -eux; \
    curl -fsSL -o /tmp/query-monitor.zip https://downloads.wordpress.org/plugin/query-monitor.latest-stable.zip; \
    unzip -q /tmp/query-monitor.zip -d /usr/src/wordpress/wp-content/plugins; \
    rm -f /tmp/query-monitor.zip

COPY public/ /usr/src/wordpress/wp-content/themes/blok45
