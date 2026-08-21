#!/bin/bash
# ECShopX 宝塔一键部署安装脚本
# 由宝塔面板在解压源码后自动执行：bash install.sh <站点名>
# 工作目录为站点根目录（本脚本所在目录）。执行结束后面板会删除本脚本。
#
# 注意：宝塔 PHP 一键部署仅在存在 import.sql 时才会替换 .env 里的 BT_DB_*，
# 本项目用 Doctrine 迁移而非 import.sql，因此必须在本脚本内自行注入数据库凭据。
#
# 本脚本负责：DB 注入、composer 依赖、生成密钥、Redis 密码、数据库迁移、
#            按 PRODUCT_MODEL 导入 Demo、初始化管理员密码、阿里云短信场景、目录权限、队列与计划任务。
#            全程写入 storage/logs/bt-install.log。

set -e

# ---------------------------------------------------------------------------
# 基础变量
# ---------------------------------------------------------------------------
SITE_ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$SITE_ROOT"

SITE_NAME="${1:-}"
LOG_PREFIX="[ECShopX]"

mkdir -p "$SITE_ROOT/storage/logs"
LOG_FILE="$SITE_ROOT/storage/logs/bt-install.log"

# 同时写 stdout 与日志文件（不用 process substitution，避免影响退出码）
log()  {
    local msg="${LOG_PREFIX} $*"
    echo "$msg"
    echo "$msg" >> "$LOG_FILE"
}
err()  {
    local msg="${LOG_PREFIX} [错误] $*"
    echo "$msg" >&2
    echo "$msg" >> "$LOG_FILE"
}
die()  { err "$*"; err "完整日志: $LOG_FILE"; exit 1; }

log "======== 开始安装 $(date '+%Y-%m-%d %H:%M:%S') ========"
log "站点根目录: $SITE_ROOT"
log "站点名参数: ${SITE_NAME:-（空）}"

# ---------------------------------------------------------------------------
# 探测 PHP / Composer（宝塔 PHP 安装在 /www/server/php/<ver>/bin）
# ---------------------------------------------------------------------------
PHP_BIN=""
detect_php() {
    for ver in 83 82; do
        if [ -x "/www/server/php/${ver}/bin/php" ]; then
            PHP_BIN="/www/server/php/${ver}/bin/php"
            return 0
        fi
    done
    if command -v php >/dev/null 2>&1; then
        PHP_BIN="$(command -v php)"
        return 0
    fi
    die "未找到 PHP 可执行文件，请确认已安装 PHP 8.2+。"
}

PHP_DIR=""
detect_php_dir() {
    PHP_DIR="$(dirname "$PHP_BIN")"
}

run_php() { "$PHP_BIN" "$@"; }

COMPOSER_CMD=""
detect_composer() {
    if [ -f "$SITE_ROOT/composer.phar" ]; then
        COMPOSER_CMD="$PHP_BIN $SITE_ROOT/composer.phar"
    elif [ -x "$PHP_DIR/composer" ]; then
        COMPOSER_CMD="$PHP_BIN $PHP_DIR/composer"
    elif command -v composer >/dev/null 2>&1; then
        COMPOSER_CMD="$PHP_BIN $(command -v composer)"
    else
        die "未找到 composer，请在宝塔 PHP 设置中安装 composer 或保留项目内 composer.phar。"
    fi
}

detect_php
detect_php_dir
detect_composer
log "PHP: $PHP_BIN ($("$PHP_BIN" -r 'echo PHP_VERSION;'))"
log "Composer: $COMPOSER_CMD"

# ---------------------------------------------------------------------------
# .env 读写
# ---------------------------------------------------------------------------
prepare_env() {
    if [ ! -f "$SITE_ROOT/.env" ]; then
        die ".env 不存在（打包时应已由 pack.sh 写入站点根目录）"
    fi
}

set_env() {
    local key="$1" val="$2" file="$SITE_ROOT/.env"
    local esc
    esc="$(printf '%s' "$val" | sed -e 's/[\/&|]/\\&/g')"
    if grep -qE "^${key}=" "$file"; then
        sed -i "s|^${key}=.*|${key}=${esc}|" "$file"
    else
        printf '\n%s=%s\n' "$key" "$val" >> "$file"
    fi
}

get_env() {
    local key="$1" file="$SITE_ROOT/.env"
    grep -E "^${key}=" "$file" | head -n1 | cut -d'=' -f2-
}

is_db_placeholder() {
    local v
    v="$(get_env DB_DATABASE)"
    [ -z "$v" ] || [ "$v" = "BT_DB_NAME" ] || [ "$v" = "\${BT_DB_NAME}" ]
}

# ---------------------------------------------------------------------------
# 从宝塔面板注入数据库凭据（弥补面板不替换 BT_DB_* 的问题）
# 新版面板把 sites / databases 拆到 data/db/site.db、database.db，且密码加密，
# 因此必须走面板 public.M API（自动选库并解密），不能直接读 default.db。
# ---------------------------------------------------------------------------
inject_db_from_baota() {
    if ! is_db_placeholder; then
        log "DB_DATABASE 已是真实值 ($(get_env DB_DATABASE))，跳过注入。"
        return 0
    fi

    log "检测到 BT_DB_* 占位符，尝试从宝塔面板注入..."

    local py=""
    for candidate in \
        "/www/server/panel/pyenv/bin/python3" \
        "/www/server/panel/pyenv/bin/python" \
        "$(command -v python3 2>/dev/null || true)"
    do
        if [ -n "$candidate" ] && [ -x "$candidate" ]; then
            py="$candidate"
            break
        fi
    done
    [ -n "$py" ] || die "未找到可用的 python3，无法读取宝塔面板数据库凭据。"

    local result
    result="$(
        SITE_NAME="$SITE_NAME" SITE_ROOT="$SITE_ROOT" \
        PYTHONPATH="/www/server/panel:/www/server/panel/class${PYTHONPATH:+:$PYTHONPATH}" \
        "$py" - <<'PY'
import os, sys
sys.path.insert(0, "/www/server/panel")
sys.path.insert(0, "/www/server/panel/class")
os.chdir("/www/server/panel")

try:
    import public
except Exception as e:
    print("ERR\tIMPORT\t{}".format(e))
    sys.exit(0)

site_name = (os.environ.get("SITE_NAME") or "").strip()
site_root = (os.environ.get("SITE_ROOT") or "").rstrip("/")

site = None
if site_name:
    site = public.M("sites").where("name=?", (site_name,)).field("id,name,path").find()

if (not site or "id" not in site) and site_root:
    site = public.M("sites").where("path=?", (site_root,)).field("id,name,path").find()
    if (not site or "id" not in site) and not site_root.endswith("/"):
        site = public.M("sites").where("path=?", (site_root + "/",)).field("id,name,path").find()

if (not site or "id" not in site) and site_name:
    alt = site_name.replace(".", "_")
    rows = public.M("sites").field("id,name,path").select() or []
    for row in rows:
        path = (row.get("path") or "")
        if alt in path or site_name in path:
            site = row
            break

if not site or "id" not in site:
    print("ERR\tNO_SITE")
    sys.exit(0)

db = public.M("databases").where("pid=?", (site["id"],)).field("name,username,password").order("id desc").find()
if not db or "username" not in db:
    print("ERR\tNO_DB")
    sys.exit(0)

db_name = db.get("name") or db.get("username") or ""
db_user = db.get("username") or ""
db_pass = db.get("password") or ""
# TAB 分隔；密码可能含特殊字符
print("OK\t{}\t{}\t{}".format(db_name, db_user, db_pass))
PY
    )" || die "读取宝塔面板数据库失败。"

    local status db_name db_user db_pass
    status="$(printf '%s' "$result" | cut -f1)"
    case "$status" in
        OK)
            db_name="$(printf '%s' "$result" | cut -f2)"
            db_user="$(printf '%s' "$result" | cut -f3)"
            db_pass="$(printf '%s' "$result" | cut -f4-)"
            ;;
        ERR)
            local why
            why="$(printf '%s' "$result" | cut -f2)"
            if [ "$why" = "NO_SITE" ]; then
                die "面板中未找到站点「${SITE_NAME:-$SITE_ROOT}」。请确认一键部署已创建站点。"
            fi
            if [ "$why" = "IMPORT" ]; then
                die "无法导入宝塔 public 模块: $(printf '%s' "$result" | cut -f3-)"
            fi
            die "站点未关联数据库。请在一键部署时勾选「创建数据库」，或在面板为该站点创建数据库后，将账号写入 .env 再执行: php artisan doctrine:migrations:migrate --no-interaction"
            ;;
        *)
            die "解析面板数据库结果失败: $result"
            ;;
    esac

    [ -n "$db_name" ] && [ -n "$db_user" ] || die "面板返回的数据库名为空。"

    set_env DB_DATABASE "$db_name"
    set_env DB_USERNAME "$db_user"
    set_env DB_PASSWORD "$db_pass"
    log "已注入数据库: DB_DATABASE=$db_name DB_USERNAME=$db_user"
}

assert_db_ready() {
    if is_db_placeholder; then
        die "数据库配置仍为占位符 BT_DB_*，拒绝执行迁移。请检查一键部署是否创建了数据库。"
    fi
    local db_host db_name db_user
    db_host="$(get_env DB_HOST)"
    db_name="$(get_env DB_DATABASE)"
    db_user="$(get_env DB_USERNAME)"
    log "将使用数据库: ${db_user}@${db_host:-127.0.0.1}/${db_name}"
}

# ---------------------------------------------------------------------------
# composer 依赖（幂等：已有 vendor 则跳过）
# ---------------------------------------------------------------------------
install_dependencies() {
    if [ -f "$SITE_ROOT/vendor/autoload.php" ]; then
        log "检测到预装的 vendor，跳过 composer 安装。"
        return 0
    fi
    log "未检测到 vendor，开始在服务器安装依赖（首次较慢）..."
    $COMPOSER_CMD config -g repo.packagist composer https://mirrors.aliyun.com/composer/ >/dev/null 2>&1 || true
    if ! $COMPOSER_CMD install --no-dev -o --no-interaction --prefer-dist --no-scripts; then
        die "composer install 失败，请检查 PHP 扩展与网络。"
    fi
    log "依赖安装完成。"
}

# ---------------------------------------------------------------------------
# 生成密钥 / Redis 密码（幂等：已有则跳过）
# ---------------------------------------------------------------------------
generate_secrets() {
    if [ -z "$(get_env APP_KEY)" ]; then
        local app_key
        app_key="base64:$("$PHP_BIN" -r 'echo base64_encode(random_bytes(32));')"
        set_env APP_KEY "$app_key"
        log "已生成 APP_KEY。"
    else
        log "APP_KEY 已存在，跳过。"
    fi

    if [ -z "$(get_env JWT_SECRET)" ]; then
        local jwt
        jwt="$("$PHP_BIN" -r 'echo bin2hex(random_bytes(32));')"
        set_env JWT_SECRET "$jwt"
        log "已生成 JWT_SECRET。"
    else
        log "JWT_SECRET 已存在，跳过。"
    fi

    if [ -z "$(get_env REDIS_PASSWORD)" ]; then
        local redis_pass
        redis_pass="$("$PHP_BIN" -r 'echo bin2hex(random_bytes(12));')"
        set_env REDIS_PASSWORD "$redis_pass"
        configure_redis_password "$redis_pass"
    else
        log "REDIS_PASSWORD 已存在，跳过生成（不覆盖 redis.conf）。"
    fi
}

configure_redis_password() {
    local pass="$1"
    local redis_conf="/www/server/redis/redis.conf"
    if [ -f "$redis_conf" ]; then
        if grep -qE "^requirepass" "$redis_conf"; then
            sed -i "s|^requirepass.*|requirepass ${pass}|" "$redis_conf"
        else
            echo "requirepass ${pass}" >> "$redis_conf"
        fi
        if [ -x "/etc/init.d/redis" ]; then
            /etc/init.d/redis restart >/dev/null 2>&1 || true
        fi
        log "已为 Redis 设置访问密码。"
    else
        log "未检测到宝塔 Redis 配置，跳过密码写入。"
    fi
}

# ---------------------------------------------------------------------------
# 数据库迁移（Doctrine 自身幂等；失败则中止，不假装成功）
# ---------------------------------------------------------------------------
run_migrations() {
    assert_db_ready
    log "执行数据库迁移（doctrine:migrations:migrate）..."
    if ! run_php artisan doctrine:migrations:migrate --no-interaction --force; then
        die "数据库迁移失败。请检查 .env 中 DB_* 与 MySQL 服务后手动执行: $PHP_BIN artisan doctrine:migrations:migrate --no-interaction --force"
    fi
    log "数据库迁移完成。"
}

# ---------------------------------------------------------------------------
# 按 PRODUCT_MODEL 导入 Demo（幂等：items 已有数据则跳过）
# ---------------------------------------------------------------------------
MYSQL_BIN=""
detect_mysql() {
    for candidate in \
        "/www/server/mysql/bin/mysql" \
        "$(command -v mysql 2>/dev/null || true)"
    do
        if [ -n "$candidate" ] && [ -x "$candidate" ]; then
            MYSQL_BIN="$candidate"
            return 0
        fi
    done
    return 1
}

import_demo_data() {
    local product_model demo_sql demo_label
    product_model="$(get_env PRODUCT_MODEL)"

    case "$product_model" in
        platform)
            demo_sql="$SITE_ROOT/docker-dev/demo/bbc.sql"
            demo_label="BBC Demo"
            ;;
        standard)
            demo_sql="$SITE_ROOT/docker-dev/demo/b2c_sports.sql"
            demo_label="B2C Demo"
            ;;
        *)
            err "未知 PRODUCT_MODEL=${product_model:-（空）}，跳过 Demo 导入。"
            return 0
            ;;
    esac

    if [ ! -f "$demo_sql" ]; then
        err "未找到 Demo 文件 ${demo_sql}, 跳过导入。"
        return 0
    fi

    detect_mysql || {
        err "未找到 mysql 客户端，跳过 Demo 导入。可稍后手动导入: $demo_sql"
        return 0
    }

    local db_host db_port db_name db_user db_pass item_count
    db_host="$(get_env DB_HOST)"
    db_port="$(get_env DB_PORT)"
    db_name="$(get_env DB_DATABASE)"
    db_user="$(get_env DB_USERNAME)"
    db_pass="$(get_env DB_PASSWORD)"
    [ -n "$db_host" ] || db_host="127.0.0.1"
    [ -n "$db_port" ] || db_port="3306"

    item_count="$(
        "$MYSQL_BIN" -h"$db_host" -P"$db_port" -u"$db_user" -p"$db_pass" \
            --default-character-set=utf8mb4 \
            -N -s -e "SELECT COUNT(*) FROM items;" "$db_name" 2>/dev/null || echo ""
    )"
    if [ -n "$item_count" ] && [ "$item_count" -gt 0 ] 2>/dev/null; then
        log "数据库已有商品数据 (items=${item_count}), 跳过 ${demo_label} 导入。"
        return 0
    fi

    log "导入 $demo_label 数据: $demo_sql ..."
    if ! "$MYSQL_BIN" -h"$db_host" -P"$db_port" -u"$db_user" -p"$db_pass" \
        --default-character-set=utf8mb4 "$db_name" < "$demo_sql"; then
        die "$demo_label 导入失败。请检查 MySQL 后手动导入: $demo_sql"
    fi
    log "$demo_label 导入完成。"
}

# ---------------------------------------------------------------------------
# 绑定商户域名（Demo 导入后或已有 companys 时，将 pc_domain/h5_domain 设为站点 Host）
# ---------------------------------------------------------------------------
_resolve_site_host() {
    local name="${SITE_NAME:-}"
    [ -n "$name" ] || return 1
    if [[ "$name" == *.* ]] || [[ "$name" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
        echo "$name"
        return 0
    fi
    return 1
}

bind_company_domains() {
    local site_host
    site_host="$(_resolve_site_host)" || {
        log "站点名「${SITE_NAME:-（空）}」不像域名/IP，跳过 pc_domain/h5_domain 绑定。"
        return 0
    }

    detect_mysql || {
        err "未找到 mysql 客户端，跳过域名绑定。"
        return 0
    }

    local company_id db_host db_port db_name db_user db_pass company_count
    company_id="$(get_env SYSTEM_MAIN_COMPANYS_ID)"
    [ -n "$company_id" ] || company_id="1"

    db_host="$(get_env DB_HOST)"
    db_port="$(get_env DB_PORT)"
    db_name="$(get_env DB_DATABASE)"
    db_user="$(get_env DB_USERNAME)"
    db_pass="$(get_env DB_PASSWORD)"
    [ -n "$db_host" ] || db_host="127.0.0.1"
    [ -n "$db_port" ] || db_port="3306"

    company_count="$(
        "$MYSQL_BIN" -h"$db_host" -P"$db_port" -u"$db_user" -p"$db_pass" \
            --default-character-set=utf8mb4 \
            -N -s -e "SELECT COUNT(*) FROM companys WHERE company_id=${company_id};" "$db_name" 2>/dev/null || echo ""
    )"
    if [ -z "$company_count" ] || [ "$company_count" -eq 0 ] 2>/dev/null; then
        log "companys 无 company_id=${company_id} 记录，跳过域名绑定。"
        return 0
    fi

    log "绑定商户域名: pc_domain/h5_domain = ${site_host} (company_id=${company_id})"
    if ! "$MYSQL_BIN" -h"$db_host" -P"$db_port" -u"$db_user" -p"$db_pass" \
        --default-character-set=utf8mb4 \
        -e "UPDATE companys SET pc_domain='${site_host}', h5_domain='${site_host}' WHERE company_id=${company_id};" \
        "$db_name"; then
        err "域名绑定失败（安装继续）。"
    fi
}

# ---------------------------------------------------------------------------
# 初始化管理员账号密码（与 auto_install.json 一致，供宝塔成功页展示）
# ---------------------------------------------------------------------------
ADMIN_USERNAME="admin"
ADMIN_PASSWORD=""

init_admin_account() {
    local cfg="$SITE_ROOT/auto_install.json"
    if [ -f "$cfg" ]; then
        ADMIN_USERNAME="$(
            run_php -r '
                $j=@json_decode(@file_get_contents($argv[1]), true);
                $u=is_array($j)?(string)($j["admin_username"]??""):"";
                echo $u!==""?$u:"admin";
            ' "$cfg" 2>/dev/null || echo "admin"
        )"
        ADMIN_PASSWORD="$(
            run_php -r '
                $j=@json_decode(@file_get_contents($argv[1]), true);
                echo is_array($j)?(string)($j["admin_password"]??""):"";
            ' "$cfg" 2>/dev/null || true
        )"
    fi
    [ -n "$ADMIN_USERNAME" ] || ADMIN_USERNAME="admin"
    if [ -z "$ADMIN_PASSWORD" ]; then
        ADMIN_PASSWORD="$("$PHP_BIN" -r 'echo bin2hex(random_bytes(6));')"
        log "auto_install.json 未配置 admin_password，已生成随机密码。"
    fi

    log "初始化管理员密码 (账号: ${ADMIN_USERNAME})..."
    if ! run_php artisan account:init-admin-password "$ADMIN_PASSWORD" --no-interaction; then
        die "初始化管理员密码失败。"
    fi

    local cred_file="$SITE_ROOT/storage/logs/admin-credentials.txt"
    cat > "$cred_file" <<EOF
ECShopX 管理后台账号（安装时写入，请尽快登录后修改密码）
登录地址: /admin/
账号: $ADMIN_USERNAME
密码: $ADMIN_PASSWORD
生成时间: $(date '+%Y-%m-%d %H:%M:%S')
EOF
    chmod 600 "$cred_file" 2>/dev/null || true
    log "管理员凭据已写入: $cred_file"
}

# ---------------------------------------------------------------------------
# 初始化阿里云短信场景（与 dev-setup.sh 一致，失败不中断安装）
# ---------------------------------------------------------------------------
init_aliyunsms_scenes() {
    log "初始化阿里云短信场景..."
    if run_php artisan aliyunsms:scene:initialize 1; then
        log "阿里云短信场景初始化完成。"
    else
        err "阿里云短信场景初始化失败（安装继续）。"
    fi
}

# ---------------------------------------------------------------------------
# 目录权限
# ---------------------------------------------------------------------------
fix_permissions() {
    local run_user="www"
    id "$run_user" >/dev/null 2>&1 || run_user="www-data"
    mkdir -p "$SITE_ROOT/storage/logs" \
             "$SITE_ROOT/storage/framework/cache/laravel-excel" \
             "$SITE_ROOT/bootstrap/cache"
    chown -R "${run_user}:${run_user}" "$SITE_ROOT/storage" "$SITE_ROOT/bootstrap/cache" 2>/dev/null || true
    chmod -R 755 "$SITE_ROOT/storage" "$SITE_ROOT/bootstrap/cache" 2>/dev/null || true
    if [ ! -e "$SITE_ROOT/public/storage" ]; then
        ln -snf "$SITE_ROOT/storage/app/public" "$SITE_ROOT/public/storage" 2>/dev/null || true
    fi
    if [ -d "$SITE_ROOT/web/.output/public/images" ]; then
        ln -snf "$SITE_ROOT/web/.output/public/images" "$SITE_ROOT/public/images"
    fi
    if [ -d "$SITE_ROOT/web/.output/public/assets" ]; then
        ln -snf "$SITE_ROOT/web/.output/public/assets" "$SITE_ROOT/public/assets"
    fi
    log "目录权限设置完成。"
}

# ---------------------------------------------------------------------------
# Supervisor 配置目录（宝塔 profile/*.ini 优先，系统 conf.d 回退）
# ---------------------------------------------------------------------------
_resolve_supervisor_conf_dir() {
    local baota_profile="/www/server/panel/plugin/supervisor/profile"
    if [ -d "$baota_profile" ]; then
        SUPERVISOR_CONF_DIR="$baota_profile"
        SUPERVISOR_CONF_EXT="ini"
    elif [ -d "/etc/supervisor.d" ]; then
        SUPERVISOR_CONF_DIR="/etc/supervisor.d"
        SUPERVISOR_CONF_EXT="conf"
    elif [ -d "/etc/supervisor/conf.d" ]; then
        SUPERVISOR_CONF_DIR="/etc/supervisor/conf.d"
        SUPERVISOR_CONF_EXT="conf"
    else
        SUPERVISOR_CONF_DIR=""
        SUPERVISOR_CONF_EXT=""
    fi
}

_resolve_supervisorctl() {
    SUPERVISORCTL=""
    SUPERVISORCTL_ARGS=()
    if [ -x /www/server/panel/pyenv/bin/supervisorctl ]; then
        SUPERVISORCTL=/www/server/panel/pyenv/bin/supervisorctl
        [ -f /etc/supervisor/supervisord.conf ] && SUPERVISORCTL_ARGS=(-c /etc/supervisor/supervisord.conf)
    elif command -v supervisorctl >/dev/null 2>&1; then
        SUPERVISORCTL="$(command -v supervisorctl)"
    fi
}

_supervisorctl_update() {
    _resolve_supervisorctl
    if [ -z "$SUPERVISORCTL" ]; then
        err "未找到 supervisorctl，配置已写入但未 reload。请在面板 Supervisor 管理器中重载。"
        return 1
    fi
    local out
    out="$("$SUPERVISORCTL" "${SUPERVISORCTL_ARGS[@]}" reread 2>&1)" || true
    [ -n "$out" ] && log "supervisorctl reread: $out"
    out="$("$SUPERVISORCTL" "${SUPERVISORCTL_ARGS[@]}" update 2>&1)" || true
    [ -n "$out" ] && log "supervisorctl update: $out"
    return 0
}

# 显式 start/restart，避免仅 update 时旧 FATAL/BACKOFF 进程不拉起
_supervisorctl_ensure() {
    local target="$1"
    _resolve_supervisorctl
    [ -n "$SUPERVISORCTL" ] || return 1
    local out
    out="$("$SUPERVISORCTL" "${SUPERVISORCTL_ARGS[@]}" restart "$target" 2>&1)" \
        || out="$("$SUPERVISORCTL" "${SUPERVISORCTL_ARGS[@]}" start "$target" 2>&1)" \
        || true
    [ -n "$out" ] && log "supervisorctl ensure ${target}: $out"
}

# 释放 3000，避免旧 Nuxt/残留 node 占端口导致新进程 FATAL → /web/ 502
_free_nuxt_port() {
    local pids
    pids="$(ss -lntp 2>/dev/null | sed -n 's/.*127.0.0.1:3000 .*pid=\([0-9][0-9]*\).*/\1/p' | sort -u)"
    [ -n "$pids" ] || return 0
    local pid cwd
    for pid in $pids; do
        cwd="$(readlink -f /proc/$pid/cwd 2>/dev/null || true)"
        log "释放 127.0.0.1:3000 占用进程 pid=$pid cwd=${cwd:-?}"
        kill "$pid" 2>/dev/null || true
    done
    sleep 1
    for pid in $pids; do
        if kill -0 "$pid" 2>/dev/null; then
            kill -9 "$pid" 2>/dev/null || true
        fi
    done
    sleep 1
}


_resolve_node20() {
    NODE_BIN=""
    local cand
    for cand in \
        /www/server/nodejs/v20.19.*/bin/node \
        /www/server/nodejs/v20.*/bin/node \
        "$(command -v node 2>/dev/null || true)"
    do
        for f in $cand; do
            [ -x "$f" ] || continue
            local major
            major="$("$f" -p "process.versions.node.split('.')[0]" 2>/dev/null || echo 0)"
            if [ "${major:-0}" -ge 20 ]; then
                NODE_BIN="$f"
                return 0
            fi
        done
    done
    return 1
}

# ---------------------------------------------------------------------------
# 队列与计划任务（幂等覆盖同名配置）
# ---------------------------------------------------------------------------
setup_queue_and_cron() {
    local run_user="www"
    id "$run_user" >/dev/null 2>&1 || run_user="www-data"

    _resolve_supervisor_conf_dir
    local conf_dir="$SUPERVISOR_CONF_DIR"

    if [ -n "$conf_dir" ]; then
        log "写入队列 supervisor 配置到 $conf_dir ..."
        # 与 docker-new/supervisor/super-queue.ini 对齐的 5 个队列（宝塔环境 numprocs=1）
        _write_queue_conf "$conf_dir/ecshopx-queue-default.$SUPERVISOR_CONF_EXT" default 30 "$run_user"
        _write_queue_conf "$conf_dir/ecshopx-queue-quick.$SUPERVISOR_CONF_EXT"   quick   30 "$run_user"
        _write_queue_conf "$conf_dir/ecshopx-queue-seckill.$SUPERVISOR_CONF_EXT" seckill 30 "$run_user"
        _write_queue_conf "$conf_dir/ecshopx-queue-slow.$SUPERVISOR_CONF_EXT"    slow    1800 "$run_user"
        _write_queue_conf "$conf_dir/ecshopx-queue-sms.$SUPERVISOR_CONF_EXT"     sms     1800 "$run_user"
        _supervisorctl_update
        # update  alone 不会拉起已存在的 FATAL/BACKOFF；需显式 restart
        _supervisorctl_ensure "ecshopx-queue-default:"
        _supervisorctl_ensure "ecshopx-queue-quick:"
        _supervisorctl_ensure "ecshopx-queue-seckill:"
        _supervisorctl_ensure "ecshopx-queue-slow:"
        _supervisorctl_ensure "ecshopx-queue-sms:"
        log "队列 worker 已注册并尝试启动（default/quick/seckill/slow/sms）。"
    else
        err "未检测到 Supervisor，请安装「Supervisor 管理器」后手动添加队列 worker（见 README）。"
    fi

    local cron_line="* * * * * cd $SITE_ROOT && $PHP_BIN artisan schedule:run >> $SITE_ROOT/storage/logs/schedule.log 2>&1"
    if command -v crontab >/dev/null 2>&1; then
        if id www >/dev/null 2>&1; then
            ( crontab -u www -l 2>/dev/null | grep -v "$SITE_ROOT.*artisan schedule:run"; echo "$cron_line" ) | crontab -u www - 2>/dev/null \
                && log "已写入 www 用户计划任务（schedule:run）。" \
                || err "写入 www crontab 失败，请在宝塔「计划任务」手动添加：$cron_line"
        else
            ( crontab -l 2>/dev/null | grep -v "$SITE_ROOT.*artisan schedule:run"; echo "$cron_line" ) | crontab - 2>/dev/null \
                && log "已写入计划任务（schedule:run）。" \
                || err "写入 crontab 失败，请在宝塔「计划任务」手动添加：$cron_line"
        fi
    else
        err "未找到 crontab，请在宝塔「计划任务」手动添加：$cron_line"
    fi
}

_write_queue_conf() {
    local file="$1" queue="$2" timeout="$3" user="$4"
    cat > "$file" <<EOF
[program:ecshopx-queue-${queue}]
command=${PHP_BIN} ${SITE_ROOT}/artisan doctrine:queue:work --queue=${queue} --delay=3 --memory=128 --timeout=${timeout} --sleep=1 --tries=3
directory=${SITE_ROOT}
stdout_logfile=${SITE_ROOT}/storage/logs/supervisor-queue-${queue}.log
redirect_stderr=true
process_name=%(program_name)s_%(process_num)02d
autostart=true
autorestart=true
numprocs=1
user=${user}
startsecs=3
EOF
}

# ---------------------------------------------------------------------------
# PC 端 Nuxt（Supervisor + Node ≥20）
# ---------------------------------------------------------------------------
require_node20() {
    if ! _resolve_node20; then
        err "未找到 Node ≥20。请在宝塔面板安装「Node 版本管理器」并安装 Node 20，然后重试。"
        exit 1
    fi
}

_write_nuxt_conf() {
    local file="$1" user="$2"
    local node_dir company_id
    node_dir="$(dirname "$NODE_BIN")"
    company_id="$(get_env SYSTEM_MAIN_COMPANYS_ID)"
    [ -n "$company_id" ] || company_id="1"
    cat > "$file" <<EOF
[program:ecshopx-nuxt]
command=${NODE_BIN} .output/server/index.mjs
directory=${SITE_ROOT}/web
environment=NITRO_HOST=127.0.0.1,NITRO_PORT=3000,PORT=3000,NUXT_PUBLIC_API_BASE=/api/h5app,NUXT_INTERNAL_API_BASE=http://127.0.0.1/api/h5app,NUXT_PUBLIC_COMPANY_ID=${company_id},PATH=${node_dir}:/usr/local/bin:/usr/bin:/bin
stdout_logfile=${SITE_ROOT}/storage/logs/supervisor-nuxt.log
redirect_stderr=true
autostart=true
autorestart=true
numprocs=1
user=${user}
startsecs=3
EOF
}

setup_nuxt() {
    local run_user="www"
    id "$run_user" >/dev/null 2>&1 || run_user="www-data"

    require_node20

    if [ ! -f "$SITE_ROOT/web/.output/server/index.mjs" ]; then
        err "未找到 PC 端构建产物 web/.output/server/index.mjs，请确认发布包包含 Nuxt 构建结果。"
        exit 1
    fi

    _resolve_supervisor_conf_dir
    local conf_dir="$SUPERVISOR_CONF_DIR"

    if [ -n "$conf_dir" ]; then
        log "写入 PC 端 Nuxt supervisor 配置到 $conf_dir ..."
        _write_nuxt_conf "$conf_dir/ecshopx-nuxt.$SUPERVISOR_CONF_EXT" "$run_user"
        _free_nuxt_port
        _supervisorctl_update
        _supervisorctl_ensure "ecshopx-nuxt"
        log "PC 端 Nuxt 已注册并尝试启动（ecshopx-nuxt）。"
    else
        err "未检测到 Supervisor，请安装「Supervisor 管理器」后手动添加 PC 端 Nuxt worker（见 README）。"
        exit 1
    fi
}

# ---------------------------------------------------------------------------
# 开源安装统计上报（与 dev-setup.sh 同逻辑）
# ---------------------------------------------------------------------------

open_source_stat_md5_upper() {
    local data="$1"
    if command -v md5sum &>/dev/null; then
        printf '%s' "$data" | md5sum | awk '{print toupper($1)}'
    elif command -v md5 &>/dev/null; then
        printf '%s' "$data" | md5 -q | tr '[:lower:]' '[:upper:]'
    else
        echo ""
    fi
}

open_source_stat_sign() {
    local secret="$1"
    local product="$2"
    local instance_id="$3"
    local version="$4"
    local timestamp="$5"
    local s="instance_id${instance_id}product${product}timestamp${timestamp}version${version}"
    open_source_stat_md5_upper "${secret}${s}${secret}"
}

escape_json_string() {
    local s="$1"
    s="${s//\\/\\\\}"
    s="${s//\"/\\\"}"
    printf '%s' "$s"
}

get_ecshopx_version_from_composer() {
    local f="${SITE_ROOT}/composer.json"
    local v=""
    [ -r "$f" ] || {
        echo "0.0.0"
        return 0
    }
    # Prefer PHP only if it actually runs
    if [ -n "${PHP_BIN:-}" ] && [ -x "$PHP_BIN" ] && "$PHP_BIN" -r 'echo 1;' >/dev/null 2>&1; then
        v=$("$PHP_BIN" -r '$j=json_decode(file_get_contents($argv[1]),true);echo isset($j["version"])?(string)$j["version"]:"";' "$f" 2>/dev/null || true)
    elif command -v php >/dev/null 2>&1 && php -r 'echo 1;' >/dev/null 2>&1; then
        v=$(php -r '$j=json_decode(file_get_contents($argv[1]),true);echo isset($j["version"])?(string)$j["version"]:"";' "$f" 2>/dev/null || true)
    fi
    if [ -z "$v" ]; then
        v=$(sed -n '1,40s/^[[:space:]]*"version"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' "$f" | head -1)
    fi
    [ -n "$v" ] || v="0.0.0"
    echo "$v"
}

get_open_source_instance_id() {
    local mac="" f
    if [[ "${OSTYPE:-}" == darwin* ]]; then
        mac=$(ifconfig 2>/dev/null | awk '/ether/ {print tolower($2); exit}' || true)
    else
        for f in /sys/class/net/*/address; do
            [ ! -f "$f" ] && continue
            case "$f" in
                */lo/address) continue ;;
            esac
            mac=$(tr '[:upper:]' '[:lower:]' <"$f" || true)
            [ -n "$mac" ] && [ "$mac" != "00:00:00:00:00:00" ] && break
        done
    fi
    if [ -z "$mac" ]; then
        mac=$(hostname 2>/dev/null || echo "unknown")
    fi
    echo "${mac:0:64}"
}

report_open_source_install_stat() {
    local gateway="https://gwnextapi.shopex.cn"
    local secret="aF3dG6hJ1kL9zXcV4bN2mQ"
    ([ -z "$gateway" ] || [ -z "$secret" ]) && return 0
    command -v curl &>/dev/null || return 0

    local product="echopx"
    local instance_id version timestamp sign base url inst_esc ver_esc body
    instance_id=$(get_open_source_instance_id)
    version=$(get_ecshopx_version_from_composer)
    timestamp=$(date +%s)
    sign=$(open_source_stat_sign "$secret" "$product" "$instance_id" "$version" "$timestamp")
    [ -z "$sign" ] && return 0

    base="${gateway%/}"
    url="${base}/usercenter/open_source/stat/report"
    inst_esc=$(escape_json_string "$instance_id")
    ver_esc=$(escape_json_string "$version")
    body=$(printf '{"product":"%s","instance_id":"%s","version":"%s","timestamp":%s,"sign":"%s"}' \
        "$product" "$inst_esc" "$ver_esc" "$timestamp" "$sign")

    log "上报开源安装统计..."
    curl -sS --max-time 15 -o /dev/null -X POST "$url" \
        -H 'Content-Type: application/json' \
        -d "$body" 2>/dev/null || true
    return 0
}

# ---------------------------------------------------------------------------
# 主流程
# ---------------------------------------------------------------------------
main() {
    prepare_env
    inject_db_from_baota
    install_dependencies
    generate_secrets
    run_migrations
    import_demo_data
    bind_company_domains
    init_admin_account
    init_aliyunsms_scenes
    fix_permissions
    setup_queue_and_cron
    setup_nuxt

    echo ""
    log "=============================================="
    log " ECShopX 部署完成！"
    log "  管理后台:  /admin/"
    log "  PC 商城:   /web/"
    log "  H5 商城:   /mobile/"
    log "  后端 API:  /api/"
    log "  后台账号:  $ADMIN_USERNAME"
    log "  后台密码:  $ADMIN_PASSWORD"
    log "  凭据文件:  storage/logs/admin-credentials.txt"
    log "  安装日志:  $LOG_FILE"
    log "=============================================="
    log "======== 安装结束 $(date '+%Y-%m-%d %H:%M:%S') ========"
    report_open_source_install_stat
}

main "$@"
