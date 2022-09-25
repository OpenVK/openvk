FROM clickhouse/clickhouse-server:22.9-alpine

COPY ./install/init-event-db.sql /docker-entrypoint-initdb.d/init-event-db.sql