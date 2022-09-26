ARG VERSION="8.0"
FROM mysql:$VERSION

COPY ./install/init-event-db.sql /docker-entrypoint-initdb.d/init-event-db.sql