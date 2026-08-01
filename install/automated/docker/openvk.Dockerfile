# syntax=docker/dockerfile:1
ARG GITREPO=openvk/openvk
FROM ghcr.io/${GITREPO}/php:8.2-cli AS builder

WORKDIR /opt/openvk

COPY composer.json composer.lock /opt/openvk/

RUN composer install --no-interaction 

FROM docker.io/node:20 AS nodejs

COPY --from=builder /opt/openvk /opt/openvk

WORKDIR /opt/openvk/Web/static/js

ADD Web/static/js/package.json Web/static/js/package-lock.json ./

RUN npm ci

WORKDIR /opt/openvk

ADD . .

ARG GITREPO=openvk/openvk
FROM ghcr.io/${GITREPO}/php:8.2-apache

ARG INSTALL_TEST_DEPS=
RUN if [ -n "$INSTALL_TEST_DEPS" ]; then \
        apt-get update -qq && \
        DEBIAN_FRONTEND=noninteractive apt-get install -y -qq default-mysql-client curl && \
        rm -rf /var/lib/apt/lists/*; \
    fi

COPY --from=nodejs --chown=www-data:www-data /opt/openvk /opt/openvk

RUN ln -s /opt/openvk/install/automated/docker/docker-openvk-* /usr/local/bin && \
    rm -f /etc/apache2/sites-enabled/000-default.conf && \
    ln -s /opt/openvk/install/automated/common/10-openvk.conf /etc/apache2/sites-enabled/10-openvk.conf && \
    a2enmod rewrite

VOLUME [ "/opt/openvk/storage" ]
VOLUME [ "/opt/openvk/tmp/api-storage/audios" ]
VOLUME [ "/opt/openvk/tmp/api-storage/photos" ]
VOLUME [ "/opt/openvk/tmp/api-storage/videos" ]

USER www-data

WORKDIR /opt/openvk

ENTRYPOINT [ "docker-openvk-entrypoint" ]
CMD ["apache2-foreground"]
