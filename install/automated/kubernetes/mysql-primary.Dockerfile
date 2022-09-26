ARG VERSION="8.0"
FROM mysql:$VERSION

ADD --chmod=644 https://raw.githubusercontent.com/openvk/chandler/master/install/init-db.sql /docker-entrypoint-initdb.d/00000-1-init-db.sql
COPY ./install/init-static-db.sql /docker-entrypoint-initdb.d/00000-2-init-static-db.sql
COPY ./install/sqls/* /docker-entrypoint-initdb.d/