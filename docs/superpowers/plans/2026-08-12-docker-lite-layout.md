# docker-lite Layout Consolidation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move all offline/fast deploy scripts and configs into `ECShopX/docker-lite/`, with thin root wrappers, matching the approved spec.

**Architecture:** Rename `docker-release/` → `docker-lite/`, move `scripts/release/*` → `docker-lite/lib/`, move thick `pack.sh`/`deploy.sh` into `docker-lite/`, and fix path init so `RELEASE_ECSHOPX_ROOT` is the parent of `docker-lite`. Behavior of pack/deploy stays the same; only filesystem layout and references change.

**Tech Stack:** Bash, docker compose, existing `tests/release/*.sh`

## Global Constraints

- Directory name must be `docker-lite` (not `docker-release`)
- Do not change `docker-dev/` / `dev-setup.sh` / `docker-compose.dev.yml` behavior
- Do not change runtime ports or supervisord program set beyond path string updates
- Root `pack.sh` / `deploy.sh` must remain as thin `exec` wrappers
- Active scripts/tests/docs for operators must not reference `docker-release` or `scripts/release` after migration
- Historical `.superpowers/sdd/*` and old plans may keep old paths (optional note only)
- Spec: `docs/superpowers/specs/2026-08-12-docker-lite-layout-design.md`

## File map

| Path | Role after migration |
|------|----------------------|
| `docker-lite/pack.sh` | Pack entry (moved from root) |
| `docker-lite/deploy.sh` | Deploy entry (moved from root) |
| `docker-lite/lib/*.sh` | Shared helpers (was `scripts/release/`) |
| `docker-lite/{Dockerfile,docker-compose.yml,entrypoint.sh,web-entrypoint.sh,nginx.conf,images.env*,cron/,supervisord.d/,images/}` | Runtime (was `docker-release/`) |
| `docker-lite/README.md` | Operator guide |
| `pack.sh`, `deploy.sh` (repo root) | Thin wrappers |
| `tests/release/*` | Path assertions updated |

---

### Task 1: Path init + failing tests for `docker-lite`

**Files:**
- Modify: `scripts/release/common.sh` (still at old path until Task 2 move; edit in place first OR edit after move — prefer edit after creating `docker-lite/lib` in Task 2; this task only updates tests to the *target* contract and implements `common.sh` at the **current** location then re-sources after move)
- Modify: `tests/release/test_common.sh`
- Modify: `tests/release/test_pack_cli.sh`
- Modify: `tests/release/test_deploy_cli.sh`
- Modify: `tests/release/test_package.sh`
- Modify: `tests/release/test_images.sh`
- Modify: `tests/release/test_artifacts.sh`
- Modify: `tests/release/test_nvm.sh`

**Interfaces:**
- Consumes: none
- Produces: `release_init_paths` sets:
  - `RELEASE_LITE_DIR` = dirname of caller (absolute)
  - `RELEASE_ECSHOPX_ROOT` = parent of `RELEASE_LITE_DIR`
  - `RELEASE_PARENT_DIR` = parent of `RELEASE_ECSHOPX_ROOT`
  - `RELEASE_ADMIN_DIR` / `RELEASE_MOBILE_DIR` / `RELEASE_PC_DIR` unchanged relative to parent

**Note:** Because paths move in Task 2, implement Task 1 as: (a) write/update tests for the final layout paths, (b) run and expect FAIL, then Task 2 makes them PASS. If you prefer less thrash, combine Task 1+2 in one commit only after tests are green — still follow steps below.

- [ ] **Step 1: Update `test_common.sh` to source `docker-lite/lib/common.sh` and assert path init**

Replace file contents with:

```bash
#!/usr/bin/env bash
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
# shellcheck source=../../docker-lite/lib/common.sh
source "$SCRIPT_DIR/../../docker-lite/lib/common.sh"

assert_eq() {
  local name=$1 expected=$2 actual=$3
  if [ "$expected" != "$actual" ]; then
    echo "FAIL $name: expected='$expected' actual='$actual'"
    exit 1
  fi
  echo "PASS $name"
}

TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT
printf '%s\n' '{"name":"x","version":"4.12.0"}' > "$TMP/composer.json"
assert_eq version "4.12.0" "$(release_read_product_version "$TMP/composer.json")"
assert_eq mode_b2c "standard" "$(release_mode_to_product_model b2c)"
assert_eq mode_bbc "platform" "$(release_mode_to_product_model bbc)"
assert_eq suffix_b2c "b2c" "$(release_mode_to_dist_suffix b2c)"
assert_eq suffix_bbc "bbc" "$(release_mode_to_dist_suffix bbc)"

if release_mode_to_product_model weird >/dev/null 2>&1; then
  echo "FAIL expected invalid mode to fail"
  exit 1
fi
echo "PASS invalid_mode"

# Fake ECShopX/docker-lite/pack.sh layout under TMP
mkdir -p "$TMP/ECShopX/docker-lite"
touch "$TMP/ECShopX/docker-lite/pack.sh"
release_init_paths "$TMP/ECShopX/docker-lite/pack.sh"
assert_eq lite_dir "$TMP/ECShopX/docker-lite" "$RELEASE_LITE_DIR"
assert_eq ecx_root "$TMP/ECShopX" "$RELEASE_ECSHOPX_ROOT"
assert_eq parent_dir "$TMP" "$RELEASE_PARENT_DIR"
```

- [ ] **Step 2: Update CLI / package / images / artifacts / nvm tests to final paths**

`test_pack_cli.sh` — point at `docker-lite/pack.sh` and require `docker-lite`:

```bash
#!/usr/bin/env bash
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PACK="$SCRIPT_DIR/../../docker-lite/pack.sh"
test -f "$PACK" || { echo "FAIL docker-lite/pack.sh missing"; exit 1; }
if grep -E '(^|[^[:alnum:]_-])(docker[[:space:]]+(compose|build|run|pull|load)|docker-compose[[:space:]]+)' "$PACK" >/dev/null; then
  echo "FAIL pack.sh must not invoke docker runtime commands"
  exit 1
fi
bash -n "$PACK"
grep -q 'docker-lite' "$PACK" || { echo "FAIL pack.sh should reference docker-lite"; exit 1; }
echo "PASS pack_cli_syntax"
```

`test_deploy_cli.sh` — use `docker-lite/deploy.sh` and `docker-lite/docker-compose.yml`; grep `docker-lite/docker-compose.yml`; point supervisord/nginx checks at `docker-lite/`.

`test_package.sh` — source `docker-lite/lib/{common,package}.sh`; fixtures under `$TMP/ECShopX/docker-lite/...`; assert extract contains `ECShopX/docker-lite/Dockerfile` etc.

`test_images.sh` — source `docker-lite/lib/...`; create `$TMP/docker-lite/images` and `$TMP/docker-lite/images.env`; set:

```bash
RELEASE_ECSHOPX_ROOT="$TMP"
RELEASE_LITE_DIR="$TMP/docker-lite"
```

(after `images.sh` uses `RELEASE_LITE_DIR` for image paths)

`test_artifacts.sh` / `test_nvm.sh` — change source paths to `../../docker-lite/lib/...`.

- [ ] **Step 3: Run tests — expect FAIL (missing `docker-lite/`)**

Run: `bash tests/release/run.sh`  
Expected: FAIL (source path or missing files)

- [ ] **Step 4: Commit test updates**

```bash
git add tests/release/
git commit -m "test: point release tests at docker-lite layout"
```

---

### Task 2: Move tree and fix `lib` path helpers

**Files:**
- Create/Move: `docker-lite/**` from `docker-release/**`
- Create/Move: `docker-lite/lib/*.sh` from `scripts/release/*.sh`
- Move: root `pack.sh` / `deploy.sh` → `docker-lite/`
- Create: root thin wrappers
- Delete: empty `docker-release/`, `scripts/release/`
- Modify: `docker-lite/lib/common.sh`, `images.sh`, `package.sh`
- Modify: `docker-lite/pack.sh`, `docker-lite/deploy.sh`
- Modify: `docker-lite/entrypoint.sh`, `docker-lite/supervisord.d/app.ini`

**Interfaces:**
- Consumes: Task 1 test contract
- Produces: working `docker-lite/pack.sh` and `docker-lite/deploy.sh` with correct roots

- [ ] **Step 1: Physical move**

```bash
cd ECShopX
git mv docker-release docker-lite
mkdir -p docker-lite/lib
git mv scripts/release/common.sh scripts/release/artifacts.sh \
  scripts/release/package.sh scripts/release/images.sh docker-lite/lib/
rmdir scripts/release 2>/dev/null || rm -rf scripts/release
git mv pack.sh docker-lite/pack.sh
git mv deploy.sh docker-lite/deploy.sh
# untracked files under old docker-release (nginx, supervisord, etc.) move with directory rename;
# if any left behind, mv them into docker-lite/
```

If `git mv docker-release` fails due to mixed tracked/untracked, use:

```bash
mv docker-release docker-lite
git add -A docker-lite
```

- [ ] **Step 2: Implement `release_init_paths` in `docker-lite/lib/common.sh`**

Replace the function body with:

```bash
release_init_paths() {
  local caller=$1
  RELEASE_LITE_DIR="$(cd "$(dirname "$caller")" && pwd)"
  RELEASE_CALLER_DIR="$RELEASE_LITE_DIR"
  RELEASE_ECSHOPX_ROOT="$(cd "$RELEASE_LITE_DIR/.." && pwd)"
  RELEASE_PARENT_DIR="$(cd "$RELEASE_ECSHOPX_ROOT/.." && pwd)"
  RELEASE_ADMIN_DIR="$RELEASE_PARENT_DIR/ECShopX_admin-frontend"
  RELEASE_MOBILE_DIR="$RELEASE_PARENT_DIR/ECShopX_mobile-frontend"
  RELEASE_PC_DIR="$RELEASE_PARENT_DIR/ECShopX_web-frontend"
}
```

- [ ] **Step 3: Fix `docker-lite/lib/images.sh` paths**

```bash
release_images_dir() {
  printf '%s' "${RELEASE_LITE_DIR}/images"
}

release_images_env_file() {
  printf '%s' "${RELEASE_LITE_DIR}/images.env"
}
```

Update log strings from `docker-release` → `docker-lite`.

- [ ] **Step 4: Fix `docker-lite/lib/package.sh` validation**

Require staged files under `ECShopX/docker-lite/Dockerfile` and `docker-compose.yml` (replace every `docker-release` string in this file).

- [ ] **Step 5: Fix `docker-lite/pack.sh` and `deploy.sh` sources and compose paths**

Sources become:

```bash
SCRIPT_PATH="${BASH_SOURCE[0]}"
LITE_DIR="$(cd "$(dirname "$SCRIPT_PATH")" && pwd)"
# shellcheck source=lib/common.sh
source "$LITE_DIR/lib/common.sh"
source "$LITE_DIR/lib/artifacts.sh"
source "$LITE_DIR/lib/package.sh"   # pack only
source "$LITE_DIR/lib/images.sh"
release_init_paths "$SCRIPT_PATH"
```

Replace:

- `docker-release` → `docker-lite` in help text and existence checks  
- `COMPOSE_FILE="$RELEASE_LITE_DIR/docker-compose.yml"`  
- `--project-directory "$RELEASE_LITE_DIR"`  
- pack check: `"$RELEASE_LITE_DIR/docker-compose.yml"`

Keep `dist-release` output at `"$RELEASE_ECSHOPX_ROOT/dist-release"`.

- [ ] **Step 6: Fix container paths**

`entrypoint.sh`:

```bash
MOUNTED_NGINX="/data/httpd/ECShopX/docker-lite/nginx.conf"
```

`supervisord.d/app.ini`:

```ini
command=/bin/sh /data/httpd/ECShopX/docker-lite/web-entrypoint.sh
```

- [ ] **Step 7: Add root wrappers**

`ECShopX/pack.sh`:

```bash
#!/usr/bin/env bash
exec "$(cd "$(dirname "$0")" && pwd)/docker-lite/pack.sh" "$@"
```

`ECShopX/deploy.sh`:

```bash
#!/usr/bin/env bash
exec "$(cd "$(dirname "$0")" && pwd)/docker-lite/deploy.sh" "$@"
```

`chmod +x` both.

- [ ] **Step 8: Run tests**

Run: `bash tests/release/run.sh`  
Expected: all `PASS`, exit 0

- [ ] **Step 9: Commit**

```bash
git add -A docker-lite pack.sh deploy.sh scripts/release
git status # ensure docker-release and scripts/release are gone
git commit -m "refactor: consolidate offline deploy under docker-lite"
```

---

### Task 3: Operator README and leftover reference sweep

**Files:**
- Create: `docker-lite/README.md`
- Delete: `docs/offline-release.md`
- Modify (only if still referenced by active code/tests): any remaining `docker-release` / `scripts/release` in `ECShopX/{pack,deploy,docker-lite,tests/release,docs/offline*}`

**Interfaces:**
- Consumes: Task 2 layout
- Produces: single operator doc under `docker-lite/README.md`

- [ ] **Step 1: Write `docker-lite/README.md`**

Content based on `docs/offline-release.md`, with these exact path updates:

- Title: mention lite/fast vs `docker-dev`
- Preferred CLI:

```bash
cd ECShopX/docker-lite
./pack.sh
./pack.sh --with-images
./deploy.sh --mode b2c --admin-url http://HOST:8080 --h5-url http://HOST:8081 --pc-url http://HOST:8082
```

- Note root wrappers still work: `ECShopX/./pack.sh`, `./deploy.sh`
- Customer: `cd ecshopx-<ver>/ECShopX/docker-lite && ./deploy.sh ...`
- All former `docker-release` paths → `docker-lite`
- Remove stale claim that host publishes PHP `9000` if compose no longer maps it
- Contrast table: `docker-dev` = full/dev; `docker-lite` = offline/fast

- [ ] **Step 2: Delete `docs/offline-release.md`**

```bash
git rm docs/offline-release.md
```

- [ ] **Step 3: Grep sweep (active surface only)**

```bash
rg -n 'docker-release|scripts/release' \
  pack.sh deploy.sh docker-lite tests/release \
  --glob '!**/.superpowers/**' --glob '!docs/superpowers/plans/**' \
  --glob '!docs/superpowers/specs/2026-08-12-release-nginx*'
```

Expected: no matches in `pack.sh`, `deploy.sh`, `docker-lite/`, `tests/release/` (the layout design spec may still mention old names historically — OK).

- [ ] **Step 4: Re-run tests**

Run: `bash tests/release/run.sh`  
Expected: exit 0

- [ ] **Step 5: Commit**

```bash
git add docker-lite/README.md
git rm docs/offline-release.md
git commit -m "docs: move offline operator guide into docker-lite/README"
```

---

## Spec coverage checklist

| Spec requirement | Task |
|------------------|------|
| `docker-lite/` contains configs + pack/deploy + `lib/` | Task 2 |
| Root thin wrappers | Task 2 |
| `RELEASE_LITE_DIR` / parent `RELEASE_ECSHOPX_ROOT` | Task 2 |
| entrypoint + supervisord path updates | Task 2 |
| package validation `docker-lite` | Task 2 |
| tests updated + `run.sh` green | Task 1–3 |
| README; delete `docs/offline-release.md` | Task 3 |
| No active `docker-release` / `scripts/release` leftovers | Task 3 |
| Do not change docker-dev behavior | All (constraint) |

## Self-review notes

- No TBD placeholders
- `images.sh` must use `RELEASE_LITE_DIR` (not `RELEASE_ECSHOPX_ROOT/docker-lite` alone) so tests that set both stay consistent
- Wrappers must `exec` so argv and exit codes propagate
