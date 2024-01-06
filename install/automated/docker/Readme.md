# Docker
Note: `buildx` is required for building multi-arch images. See [Docker Buildx](https://docs.docker.com/buildx/working-with-buildx/) for more information.

If unsure, skip to single-arch image build instructions.

## Build
Note: commands below should be run from the root of the repository.
### Multi-arch (arm64, amd64)
Base images:
```
docker buildx build --platform linux/amd64,linux/arm64 -t ghcr.io/openvk/openvk/php:8.2-cli . --load -f install/automated/docker/base-php-cli.Dockerfile
docker buildx build --platform linux/amd64,linux/arm64 -t ghcr.io/openvk/openvk/php:8.2-apache . --load -f install/automated/docker/base-php-apache.Dockerfile
```
DB images:
```
docker buildx build --platform linux/amd64,linux/arm64 -t ghcr.io/openvk/openvk/mariadb:10.9-primary . --load -f install/automated/docker/mariadb-primary.Dockerfile
docker buildx build --platform linux/amd64,linux/arm64 -t ghcr.io/openvk/openvk/mariadb:10.9-eventdb . --load -f install/automated/docker/mariadb-eventdb.Dockerfile
```
OpenVK main image:
```
docker buildx build --platform linux/amd64,linux/arm64 -t ghcr.io/openvk/openvk/openvk:latest . --load -f install/automated/docker/openvk.Dockerfile
```

### Single-arch
Base images:
```
docker build -t ghcr.io/openvk/openvk/php:8.2-cli . -f install/automated/docker/base-php-cli.Dockerfile
docker build -t ghcr.io/openvk/openvk/php:8.2-apache . -f install/automated/docker/base-php-apache.Dockerfile
```
DB images:
```
docker build -t ghcr.io/openvk/openvk/mariadb:10.9-primary . -f install/automated/docker/mariadb-primary.Dockerfile
docker build -t ghcr.io/openvk/openvk/mariadb:10.9-eventdb . -f install/automated/docker/mariadb-eventdb.Dockerfile
```
OpenVK main image:
```
docker build -t ghcr.io/openvk/openvk/openvk:latest . -f install/automated/docker/openvk.Dockerfile
```

## Run
If you have Docker Desktop installed, then you probably have `docker-compose` installed as well. If not, refer to [Docker Compose](https://docs.docker.com/compose/install/) for installation instructions.

Before start, copy `openvk-example.yml` from the root of the repository to `openvk.yml` in this directory and edit it to your liking.

Then, obtain `chandler-example.yml` from [chandler repository](https://github.com/openvk/chandler/blob/master/chandler-example.yml) and place it in this directory as well.

Start is simple as `docker-compose up -d`. You can also use `docker-compose up` to see logs.

- OpenVK will be available at `http://localhost:8080/`.
- PHPMyAdmin will be available at `http://localhost:8081/`.
- Adminer will be available at `http://localhost:8082/`.
