ARG VERSION="10.9"
FROM mariadb:$VERSION

ADD --chmod=644 https://raw.githubusercontent.com/openvk/chandler/master/install/init-db.sql /docker-entrypoint-initdb.d/00000-1-init-db.sql

# Workaround for compatibility with older docker versions w/o `--chmod` flag for COPY/ADD directive
# Ref: https://stackoverflow.com/q/67910547/14388565
RUN chmod 644 /docker-entrypoint-initdb.d/00000-1-init-db.sql

COPY ./install/init-static-db.sql /docker-entrypoint-initdb.d/00000-2-init-static-db.sql
COPY ./install/sqls/*.sql /docker-entrypoint-initdb.d/