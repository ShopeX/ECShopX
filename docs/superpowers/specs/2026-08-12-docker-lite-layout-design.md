# Design: Consolidate offline/fast deploy under `docker-lite`

Date: 2026-08-12  
Branch: `feat/offline-release-pack`  
Status: approved for planning

## Goal

Compared with `docker-dev` (full local install), the customer offline path is a **lite / fast** mode. Release scripts and runtime configs are currently scattered (`docker-release/`, root `pack.sh`/`deploy.sh`, `scripts/release/`, `docs/offline-release.md`). Consolidate them into one directory named `docker-lite`, symmetric with `docker-dev`.

## Non-goals

- Changing deploy runtime behavior (ports, supervisord, Nuxt/SSR, API prefixes)
- Changing `docker-dev` / `dev-setup.sh` / `docker-compose.dev.yml`
- Rebuilding or re-shipping the customer tarball in this layout task (optional follow-up smoke)
- Large rewrites of historical `docs/superpowers/plans` / `.superpowers/sdd` process artifacts

## Target layout

```text
ECShopX/
  docker-dev/                      # unchanged
  docker-lite/                     # was docker-release + pack/deploy + scripts/release
    pack.sh
    deploy.sh
    docker-compose.yml
    Dockerfile
    entrypoint.sh
    web-entrypoint.sh              # still used by supervisord for Nuxt
    nginx.conf
    images.env
    images.env.example
    images/                        # optional image tars; gitignored
    cron/
    supervisord.d/
    lib/                           # was scripts/release/*
      common.sh
      artifacts.sh
      package.sh
      images.sh
    README.md                      # operator guide (from docs/offline-release.md)
  pack.sh                          # thin wrapper → docker-lite/pack.sh
  deploy.sh                        # thin wrapper → docker-lite/deploy.sh
```

Remove after migration:

- `docker-release/` (renamed/moved)
- `scripts/release/` (moved to `docker-lite/lib/`)
- Thick root `pack.sh` / `deploy.sh` (replaced by wrappers)
- `docs/offline-release.md` (content moved to `docker-lite/README.md`)

## Path semantics

- Callers live in `ECShopX/docker-lite/`.
- `lib/common.sh` `release_init_paths`:
  - `RELEASE_LITE_DIR` = directory containing `pack.sh`/`deploy.sh` (`docker-lite`)
  - `RELEASE_ECSHOPX_ROOT` = parent of `RELEASE_LITE_DIR` (ECShopX repo root)
  - `RELEASE_PARENT_DIR` = parent of ECShopX (four-project root), unchanged meaning
- Compose: `--project-directory` and compose file paths use `docker-lite/`
- Container nginx prefer-mounted config:
  - `/data/httpd/ECShopX/docker-lite/nginx.conf` (update `entrypoint.sh`)
- Supervisord Nuxt command path: `/data/httpd/ECShopX/docker-lite/web-entrypoint.sh`

## Public CLI

Preferred:

```bash
cd ECShopX/docker-lite
./pack.sh [--with-images]
./deploy.sh --mode b2c|bbc [options]
```

Compatibility wrappers at `ECShopX/pack.sh` and `ECShopX/deploy.sh` exec into `docker-lite/` with all arguments preserved (`exec "$(dirname "$0")/docker-lite/pack.sh" "$@"`).

Customer extract after pack:

```bash
cd ecshopx-<ver>/ECShopX/docker-lite
./deploy.sh --mode b2c ...
```

Tarball must include `ECShopX/docker-lite/` (Dockerfile, compose, scripts, configs). Validation in `package.sh` checks these paths under the staged tree.

## Docs

- Operator guide: `docker-lite/README.md` (rewrite paths from `docs/offline-release.md`)
- Contrast: `docker-dev` = full/dev; `docker-lite` = offline/fast mount-based deploy
- Historical specs/plans under `docs/superpowers/` may keep old names; optional one-line note that runtime dir is now `docker-lite`

## Tests

Update `tests/release/*` so every fixture and assertion uses:

- `docker-lite/` instead of `docker-release/`
- `docker-lite/lib/` instead of `scripts/release/`
- path init assumptions for scripts living under `docker-lite/`

Acceptance: `bash tests/release/run.sh` passes.

Behavioral regression checklist (manual or existing tests where applicable):

- Pack staging includes `docker-lite` essentials and four front/back projects
- Deploy still activates `dist-*`, writes env, ensures images, compose up, migrates
- Root wrappers forward argv correctly

## Migration steps (implementation outline)

1. Create `docker-lite/` by moving `docker-release/*` and `scripts/release/*` → `lib/`
2. Move `pack.sh` / `deploy.sh` into `docker-lite/`; add root wrappers
3. Fix all internal path references (`common.sh`, compose project dir, entrypoint, supervisord, package validation, images paths)
4. Move/rewrite operator doc to `docker-lite/README.md`; delete `docs/offline-release.md`
5. Update `tests/release/*`; run `tests/release/run.sh`
6. Grep repo for leftover `docker-release` / `scripts/release` in active scripts/docs and fix

## Risks

- Server already extracted as `docker-release/` — next deploy/extract uses `docker-lite/`; document in README
- Forgetting supervisord/web-entrypoint path breaks Nuxt on 8082
- Forgetting entrypoint mounted nginx path falls back to image-baked conf and misses H5 `/api` proxy updates
