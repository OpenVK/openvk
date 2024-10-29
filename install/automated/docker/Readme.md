# Docker
Note: If you want to build images for multiple architectures, you must use `buildx`. See [Docker Buildx](https://docs.docker.com/buildx/working-with-buildx/) for more information.

If unsure, skip to single-arch image build instructions.

## Build
Note: **commands below must be run from the this directory** (`/install/automated/docker/`).
### Multi-arch (arm64, amd64)
Base images:
```
docker buildx build --platform linux/amd64,linux/arm64 -t ghcr.io/openvk/openvk/php:8.2-cli ../../.. --load -f base-php-cli.Dockerfile
docker buildx build --platform linux/amd64,linux/arm64 -t ghcr.io/openvk/openvk/php:8.2-apache ../../.. --load -f base-php-apache.Dockerfile
```
DB images:
```
docker buildx build --platform linux/amd64,linux/arm64 -t ghcr.io/openvk/openvk/mariadb:10.9-primary ../../.. --load -f mariadb-primary.Dockerfile
docker buildx build --platform linux/amd64,linux/arm64 -t ghcr.io/openvk/openvk/mariadb:10.9-eventdb ../../.. --load -f mariadb-eventdb.Dockerfile
```
OpenVK main image:
```
docker buildx build --platform linux/amd64,linux/arm64 -t ghcr.io/openvk/openvk/openvk:latest ../../.. --load -f openvk.Dockerfile
```

### Single-arch
Base images:
```
docker build -t ghcr.io/openvk/openvk/php:8.2-cli ../../.. -f base-php-cli.Dockerfile
docker build -t ghcr.io/openvk/openvk/php:8.2-apache ../../.. -f base-php-apache.Dockerfile
```
DB images:
```
docker build -t ghcr.io/openvk/openvk/mariadb:10.9-primary ../../.. -f mariadb-primary.Dockerfile
docker build -t ghcr.io/openvk/openvk/mariadb:10.9-eventdb ../../.. --f mariadb-eventdb.Dockerfile
```
OpenVK main image:
```
docker build -t ghcr.io/openvk/openvk/openvk:latest ../../.. -f openvk.Dockerfile
```

## Run
If you have Docker Desktop installed, then you should have `docker compose` installed automatically. If not, refer to [Docker Compose](https://docs.docker.com/compose/install/) for installation instructions.

Example configurations are located in this directory for convenience. Before start, copy `openvk.example.yml` to `openvk.yml`, then `chandler.example.yml` to `chandler.yml` and edit them to your liking.

Start is simple as `docker compose up -d`. You can also use `docker compose up` to see logs.

- OpenVK will be available at `http://localhost:8080/`.
- PHPMyAdmin will be available at `http://localhost:8081/`.
- Adminer will be available at `http://localhost:8082/`.
