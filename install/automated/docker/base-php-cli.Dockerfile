ARG VERSION=8.2
FROM docker.io/php:$VERSION-cli

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN apt update && \
    apt install -y \
        git

RUN install-php-extensions \
    gd \
    zip \
    yaml \
    intl \
    pdo_mysql