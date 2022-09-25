FROM mysql:8.0

ADD https://raw.githubusercontent.com/openvk/chandler/master/install/init-db.sql /docker-entrypoint-initdb.d/init-db.sql
COPY ./install/init-static-db.sql /docker-entrypoint-initdb.d/init-static-db.sql
COPY ./install/sqls/* /docker-entrypoint-initdb.d/