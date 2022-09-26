ARG VERSION="10.9"
FROM mariadb:$VERSION

COPY ./install/init-event-db.sql /docker-entrypoint-initdb.d/00000-1-init-event-db.sql
COPY ./install/sqls/eventdb/*.sql /docker-entrypoint-initdb.d/