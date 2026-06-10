ARG VERSION=8.2
FROM docker.io/php:${VERSION}-apache

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

USER root

RUN apt update; \
    apt install -y \
        git \
        ffmpeg \
        libsdl2-2.0-0 \
        build-essential \
        autoconf \
        libtool \
        m4 \
        automake \
        wget \
    && \
    install-php-extensions \
        gd \
        zip \
        intl \
        yaml \
        pdo_mysql \
        imagick \
    && \
    wget http://www.xmailserver.org/libxdiff-0.23.tar.gz && \
    tar -xzf libxdiff-0.23.tar.gz && \
    cd libxdiff-0.23 && \
    ./configure --prefix=/usr && \
    make && \
    make install && \
    cd / && \
    rm -rf libxdiff-0.23 libxdiff-0.23.tar.gz && \
    pecl install xdiff && \
    docker-php-ext-enable xdiff && \
    rm -rf /var/lib/apt/lists/*