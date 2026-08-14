# docker-lite — offline / fast release pack and deploy

Operator guide for building a customer-facing offline tarball (`pack.sh`) and deploying with **docker compose** (`deploy.sh`). This is the **lite / fast** path: prebuilt artifacts, bind-mounted source, minimal compose stack.

For a full local development install, use `dev-setup.sh` + `docker-compose.dev.yml` / **`docker-dev`** instead (also included in the release package for reference; deploy itself uses **`docker-lite/`** only).

## docker-dev vs docker-lite

| | **docker-dev** | **docker-lite** |
|---|----------------|-----------------|
| Purpose | Full local development | Offline / fast customer deploy |
| Builds on deploy | Yes (Composer, npm, pnpm, frontends) | No — requires prebuilt `dist` / `.output` |
| Compose file | `docker-compose.dev.yml` | `docker-lite/docker-compose.yml` |
| Operator entry | `dev-setup.sh` | `docker-lite/pack.sh`, `docker-lite/deploy.sh` |

## Architecture (customer deploy)

Three services under `docker-lite/docker-compose.yml`:

| Service | Image source |
|---------|----------------|
| **app** | Built from **`docker-lite/Dockerfile`** on top of your **base runtime image** (`APP_BASE_IMAGE`, no MySQL/Redis). If the base image is missing and no tar URL is set, deploy runs `docker pull`. **Application code is not copied into the image** — the four-project parent directory is bind-mounted to `/data/httpd` (same idea as `docker-compose.dev.yml`). |
| **mysql** | Prefer `docker load` from a provided MySQL image **tar**; otherwise `docker pull` if no tar URL |
| **redis** | Prefer `docker load` from a provided Redis image **tar**; otherwise `docker pull` if no tar URL |

### App container layout after deploy

Host extract root (parent of `ECShopX/`) is mounted to `/data/httpd`:

```text
/data/httpd/ECShopX
/data/httpd/ECShopX_admin-frontend
/data/httpd/ECShopX_mobile-frontend
/data/httpd/ECShopX_web-frontend
```

Working directory: `/data/httpd/ECShopX`. App PID 1 is **supervisord**, running OpenResty (8080/8081/8082), php-fpm (`127.0.0.1:9000`, not published to the host), crond (`schedule:run`), queue workers, and Nuxt (`127.0.0.1:3000`, proxied on 8082).

Host ports (defaults): admin/API `8080`, H5 `8081`, PC `8082`.

## Pack (`pack.sh`)

Prerequisites: `php`, `composer`/`composer.phar`, `nvm`, Node **16.20** (admin/mobile), Node **20.19** + pnpm **10.13.0** (PC), `tar`.

Preferred CLI (run from this directory):

```bash
cd ECShopX/docker-lite
./pack.sh
./pack.sh --with-images
```

Root wrappers still work from `ECShopX/`:

```bash
cd ECShopX
./pack.sh
./pack.sh --with-images
```

`--with-images` downloads image tars into `docker-lite/images/` (needs URLs in `images.env`).

**Included:** source trees, `vendor/`, frontend `dist-b2c`/`dist-bbc` + PC `.output`, `deploy.sh`, `docker-lite/` (Dockerfile + compose + `images.env`), demo SQL under `docker-new/demo/` when available, and any `docker-lite/images/*.tar` present at pack time.

**Excluded:** top-level `node_modules`, `.git`, `.env`, prior `ecshopx-*.tar.gz`, Docker images not prefetched as tar. Nuxt keeps `.output/server/node_modules` (required to run SSR).

## Configure image tars

Edit `docker-lite/images.env` (see `images.env.example`):

```bash
APP_BASE_IMAGE_TAR_URL=https://your-cdn/ecshopex-php-ecx-8.2.29.tar
APP_BASE_IMAGE=registry.cn-hangzhou.aliyuncs.com/shopex_company/ecshopex-php:ecx-8.2.29-fpm-alpine3.22-openresty-node-20.19   # must match tag inside the tar after docker load

MYSQL_IMAGE_TAR_URL=https://your-cdn/mysql-5.7.tar
MYSQL_IMAGE=mysql:5.7

REDIS_IMAGE_TAR_URL=https://your-cdn/redis-7.0.tar
REDIS_IMAGE=redis:7.0
```

If a tar already exists under `docker-lite/images/<basename>`, deploy skips download and loads it directly.

## Deploy (`deploy.sh`)

```bash
tar xzf ecshopx-4.12.0.tar.gz
cd ecshopx-4.12.0/ECShopX/docker-lite
# fill images.env URLs/tags first
./deploy.sh --mode b2c --admin-url http://HOST:8080 --h5-url http://HOST:8081 --pc-url http://HOST:8082
```

Root wrappers from the extracted `ECShopX/` directory also work: `./deploy.sh ...`.

What it does:

1. Validate prebuilt frontend/backend artifacts; activate `dist-*`
2. Ensure bind-mount parent is traversable by container `www-data` (`chmod a+x` — fixes 404 when installed under `/root`)
3. Write `.env` (`DB_HOST=mysql`, `REDIS_HOST=redis`)
4. Ensure base/MySQL/Redis images (`docker load`)
5. `docker compose -f docker-lite/docker-compose.yml build/up`
6. Generate `APP_KEY` / `JWT_SECRET`, migrate DB, optional demo import

Does **not** run Composer/npm/pnpm/frontend builds. Does **not** use `docker-compose.dev.yml`.

OpenResty inside the app container serves admin/H5/PC on 8080/8081/8082 from activated `dist` / `.output`.

## Related scripts

| Script | Audience |
|--------|----------|
| `docker-lite/pack.sh` (or `ECShopX/pack.sh`) | Release: build artifacts + optional image tar prefetch |
| `docker-lite/deploy.sh` (or `ECShopX/deploy.sh`) | Customer: load image tars + compose up |
| `dev-setup.sh` | Local full stack (not used by offline deploy) |
