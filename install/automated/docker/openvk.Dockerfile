ARG GITREPO=openvk/openvk
FROM ghcr.io/${GITREPO}/php:8.2-cli AS builder

WORKDIR /opt

RUN git clone --depth=2 https://github.com/openvk/chandler.git

WORKDIR /opt/chandler

RUN composer install

WORKDIR /opt/chandler/extensions/available

RUN git clone --depth=2 https://github.com/openvk/commitcaptcha.git

WORKDIR /opt/chandler/extensions/available/commitcaptcha

RUN composer install

WORKDIR /opt/chandler/extensions/available

RUN mkdir openvk

WORKDIR /opt/chandler/extensions/available/openvk

ADD composer.* .

RUN composer install

FROM docker.io/node:20 AS nodejs

COPY --from=builder /opt/chandler /opt/chandler

WORKDIR /opt/chandler/extensions/available/openvk/Web/static/js

ADD Web/static/js/package.json Web/static/js/package-lock.json ./

RUN npm ci

WORKDIR /opt/chandler/extensions/available/openvk

ADD . .

ARG GITREPO=openvk/openvk
FROM ghcr.io/${GITREPO}/php:8.2-apache

COPY --from=nodejs --chown=www-data:www-data /opt/chandler /opt/chandler

RUN ln -s /opt/chandler/extensions/available/commitcaptcha/ /opt/chandler/extensions/enabled/commitcaptcha && \
    ln -s /opt/chandler/extensions/available/openvk/ /opt/chandler/extensions/enabled/openvk && \
    ln -s /opt/chandler/extensions/available/openvk/install/automated/docker/docker-openvk-* /usr/local/bin && \
    rm -f /etc/apache2/sites-enabled/000-default.conf && \
    ln -s /opt/chandler/extensions/available/openvk/install/automated/common/10-openvk.conf /etc/apache2/sites-enabled/10-openvk.conf && \
    a2enmod rewrite

VOLUME [ "/opt/chandler/extensions/available/openvk/storage" ]
VOLUME [ "/opt/chandler/extensions/available/openvk/tmp/api-storage/audios" ]
VOLUME [ "/opt/chandler/extensions/available/openvk/tmp/api-storage/photos" ]
VOLUME [ "/opt/chandler/extensions/available/openvk/tmp/api-storage/videos" ]

USER www-data

WORKDIR /opt/chandler/extensions/available/openvk

ENTRYPOINT [ "docker-openvk-entrypoint" ]
CMD ["apache2-foreground"]
