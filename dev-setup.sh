#!/bin/bash

# ECShopX 开发环境设置脚本（Docker 方式）
# 主容器运行 PHP-FPM、Nginx、MySQL、Redis，Web 前端使用独立 Node 容器
# 支持四个项目：ECShopX、ECShopX_admin-frontend、ECShopX_mobile-frontend、ECShopX_web-frontend

set -e

# ===========================================
# 配置变量（可根据需要修改）
# ===========================================

# 脚本目录和项目根目录
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$SCRIPT_DIR"
PARENT_DIR="$(cd "$PROJECT_ROOT/.." && pwd)"

# 容器配置
CONTAINER_NAME="ecshopx-dev"
WEB_CONTAINER_NAME="ecshopx-web-frontend"
DOCKER_COMPOSE_FILE="$PROJECT_ROOT/docker-compose.dev.yml"

# 公开前端仓库地址；默认使用 Gitee 方便国内用户，如需 GitHub 可在运行脚本前导出 PUBLIC_REPO_BASE_URL 覆盖
PUBLIC_REPO_BASE_URL="${PUBLIC_REPO_BASE_URL:-https://gitee.com/ShopeX}"

# 数据库配置
MYSQL_ROOT_PWD="rootpassword"
MYSQL_DATABASE="ecshopx"
MYSQL_USER="ecshopx"
MYSQL_PASSWORD="ecshopx"

# Redis 配置
REDIS_PASSWORD="redispassword"

# 默认选项
REBUILD=false
SKIP_ADMIN=false
SKIP_VSHOP=false
SKIP_PC=false

# 外部访问地址配置（默认本地安装体验；可通过参数或交互输入覆盖）
SITE_URL=""
ADMIN_URL_OVERRIDE=""
H5_URL_OVERRIDE=""
PC_URL_OVERRIDE=""

ADMIN_URL="http://localhost:8080"
API_BASE_URL="http://localhost:8080/api/"
H5_URL="http://localhost:8081"
PC_URL="http://localhost:8082"
QIANKUN_ENTRY_URL="http://localhost:8080/newpc/"
MOBILE_API_URL="http://localhost:8080/api/h5app/wxapp"
PC_API_URL="http://localhost:8080/api/h5app"
ADMIN_HOST_PORT="8080"
H5_HOST_PORT="8081"
PC_HOST_PORT="8082"
NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS="http://localhost:8080"

# 业务模式（全局变量，在 build_admin 中设置，build_vshop 中复用）
SELECTED_PLATFORM=""

# 默认语言（全局变量，在第一个编译的项目中设置，后续项目复用）
SELECTED_LANG=""

# 最近一次前端 .env 配置是否发生变化（build_* 中用于决定已有产物能否跳过编译）
FRONTEND_ENV_CHANGED=false

# 语言/业务模式等交互必须从 /dev/tty 读取：若仅从 stdin 读，在「管道、重定向、部分 IDE 集成终端」下会立即 EOF，
# 空变量会触发 ${VAR:-1}，表现为未确认就选用默认项 1。

# 跟踪已安装的前端项目
INSTALLED_ADMIN=false
INSTALLED_VSHOP=false
INSTALLED_PC=false

# 记录开始时间
START_TIME=$(date +%s)

# 开源安装统计（可选）：安装成功时上报，需同时配置 OPEN_SOURCE_STAT_GATEWAY（网关根 URL，无尾部斜杠）
# 与 OPEN_SOURCE_STAT_SECRET_ECHOPX（验签密钥，勿提交到仓库）

# Docker Compose 命令（兼容新旧版本）
DOCKER_COMPOSE_CMD=""

# ===========================================
# 颜色和日志函数
# ===========================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

log_info() {
    printf "%b\n" "${BLUE}[INFO]${NC} $1"
}

log_success() {
    printf "%b\n" "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    printf "%b\n" "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    printf "%b\n" "${RED}[ERROR]${NC} $1"
}

log_step() {
    printf "%b\n" "${CYAN}[STEP]${NC} $1"
}

trim_trailing_slashes() {
    local value=$1
    while [ "${value%/}" != "$value" ]; do
        value=${value%/}
    done
    echo "$value"
}

validate_public_url() {
    local name=$1
    local value=$2
    
    if [ -z "$value" ]; then
        return 0
    fi
    
    case "$value" in
        http://*|https://*)
            return 0
            ;;
        *)
            log_error "$name 必须以 http:// 或 https:// 开头: $value"
            exit 1
            ;;
    esac
}

extract_host_port_from_url() {
    local url=$1
    local default_port=$2
    local without_scheme=""
    local authority=""
    local port=""

    without_scheme=${url#*://}
    authority=${without_scheme%%/*}

    case "$authority" in
        *:*)
            port=${authority##*:}
            ;;
        *)
            port=$default_port
            ;;
    esac

    case "$port" in
        ''|*[!0-9]*)
            log_error "访问地址端口无效: $url"
            exit 1
            ;;
    esac

    if [ "$port" -lt 1 ] || [ "$port" -gt 65535 ]; then
        log_error "访问地址端口超出范围: $url"
        exit 1
    fi

    echo "$port"
}

configure_docker_host_ports() {
    ADMIN_HOST_PORT=$(extract_host_port_from_url "$ADMIN_URL" "8080")
    H5_HOST_PORT=$(extract_host_port_from_url "$H5_URL" "8081")
    PC_HOST_PORT=$(extract_host_port_from_url "$PC_URL" "8082")

    export ADMIN_HOST_PORT H5_HOST_PORT PC_HOST_PORT

    log_info "Docker 宿主机端口映射："
    log_info "  管理后台/API: ${ADMIN_HOST_PORT}->8080"
    log_info "  H5前端:       ${H5_HOST_PORT}->8081"
    log_info "  PC前端:       ${PC_HOST_PORT}->8082"
}

set_url_defaults_from_site_url() {
    local normalized_site_url
    normalized_site_url=$(trim_trailing_slashes "$SITE_URL")
    
    ADMIN_URL="$normalized_site_url"
    QIANKUN_ENTRY_URL="$normalized_site_url/newpc/"
    derive_api_base_from_admin_url
}

derive_api_base_from_admin_url() {
    local normalized_api_url
    normalized_api_url="$(trim_trailing_slashes "$ADMIN_URL")/api"
    
    API_BASE_URL="$normalized_api_url/"
    MOBILE_API_URL="$normalized_api_url/h5app/wxapp"
    PC_API_URL="$normalized_api_url/h5app"
}

origin_from_url() {
    local url=$1
    local scheme=""
    local without_scheme=""
    local authority=""

    scheme=${url%%://*}
    without_scheme=${url#*://}
    authority=${without_scheme%%/*}

    echo "${scheme}://${authority}"
}

read_env_value() {
    local env_file=$1
    local key=$2
    local value=""

    if [ ! -f "$env_file" ]; then
        return 0
    fi

    value=$(grep -m 1 "^${key}=" "$env_file" 2>/dev/null | cut -d'=' -f2- || true)
    value=${value%\"}
    value=${value#\"}
    value=${value%\'}
    value=${value#\'}
    echo "$value"
}

configure_public_urls() {
    if [ -z "$SITE_URL" ] && [ -z "$ADMIN_URL_OVERRIDE" ] && [ -z "$H5_URL_OVERRIDE" ] && [ -z "$PC_URL_OVERRIDE" ]; then
        echo ""
        log_info "外部访问地址配置："
        log_info "  每项直接回车使用默认本地地址"
        log_info "  生产环境可分别输入管理后台、PC、H5 的完整访问地址"
        read -r -p "请输入管理后台/API 地址 (默认: $ADMIN_URL): " ADMIN_URL_OVERRIDE < /dev/tty
        read -r -p "请输入 PC 前端地址 (默认: $PC_URL): " PC_URL_OVERRIDE < /dev/tty
        read -r -p "请输入 H5 前端地址 (默认: $H5_URL): " H5_URL_OVERRIDE < /dev/tty
    fi
    
    SITE_URL=$(trim_trailing_slashes "$SITE_URL")
    ADMIN_URL_OVERRIDE=$(trim_trailing_slashes "$ADMIN_URL_OVERRIDE")
    H5_URL_OVERRIDE=$(trim_trailing_slashes "$H5_URL_OVERRIDE")
    PC_URL_OVERRIDE=$(trim_trailing_slashes "$PC_URL_OVERRIDE")
    
    validate_public_url "--site-url" "$SITE_URL"
    validate_public_url "--admin-url" "$ADMIN_URL_OVERRIDE"
    validate_public_url "--h5-url" "$H5_URL_OVERRIDE"
    validate_public_url "--pc-url" "$PC_URL_OVERRIDE"
    
    if [ -n "$SITE_URL" ]; then
        set_url_defaults_from_site_url
    fi
    
    if [ -n "$ADMIN_URL_OVERRIDE" ]; then
        ADMIN_URL="$ADMIN_URL_OVERRIDE"
        QIANKUN_ENTRY_URL="$ADMIN_URL/newpc/"
    fi
    
    if [ -n "$H5_URL_OVERRIDE" ]; then
        H5_URL="$H5_URL_OVERRIDE"
    fi
    
    if [ -n "$PC_URL_OVERRIDE" ]; then
        PC_URL="$PC_URL_OVERRIDE"
    fi
    
    derive_api_base_from_admin_url
    export NUXT_PUBLIC_API_BASE="$PC_API_URL"
    NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS=$(origin_from_url "$ADMIN_URL")
    export NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS="$NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS"
    configure_docker_host_ports
    
    log_info "访问地址配置完成："
    log_info "  管理后台: $ADMIN_URL"
    log_info "  API 接口: $API_BASE_URL"
    log_info "  H5前端:   $H5_URL"
    log_info "  PC前端:   $PC_URL"
}

# ===========================================
# 进度条和动画函数
# ===========================================

# Spinner 动画（用于不确定时长的操作）
spinner() {
    local pid=$1
    local message=${2:-"处理中"}
    local spinstr='|/-\'
    local delay=0.1
    
    while [ "$(ps a | awk '{print $1}' | grep $pid)" ]; do
        local temp=${spinstr#?}
        printf "\r${CYAN}[INFO]${NC} $message ${spinstr:0:1}"
        spinstr=$temp${spinstr%"$temp"}
        sleep $delay
    done
    printf "\r${CYAN}[INFO]${NC} $message 完成\n"
}

# 简单的进度条（用于确定时长的操作）
progress_bar() {
    local current=$1
    local total=$2
    local width=50
    local percentage=$((current * 100 / total))
    local completed=$((current * width / total))
    local remaining=$((width - completed))
    
    printf "\r${CYAN}[INFO]${NC} 进度: ["
    printf "%${completed}s" | tr ' ' '='
    printf "%${remaining}s" | tr ' ' '-'
    printf "] %d%% (%d/%d)" $percentage $current $total
}

# 带消息的进度条
progress_bar_with_message() {
    local current=$1
    local total=$2
    local message=$3
    local width=40
    local percentage=$((current * 100 / total))
    local completed=$((current * width / total))
    local remaining=$((width - completed))
    
    printf "\r${CYAN}[INFO]${NC} $message ["
    printf "%${completed}s" | tr ' ' '='
    printf "%${remaining}s" | tr ' ' '-'
    printf "] %d%%" $percentage
}

# 等待操作完成并显示进度
wait_with_progress() {
    local command=$1
    local message=${2:-"执行中"}
    local max_wait=${3:-60}
    local interval=${4:-1}
    local count=0
    
    # 后台执行命令
    eval "$command" > /tmp/progress_output.log 2>&1 &
    local pid=$!
    
    # 显示进度
    while kill -0 $pid 2>/dev/null && [ $count -lt $max_wait ]; do
        progress_bar_with_message $count $max_wait "$message"
        sleep $interval
        count=$((count + interval))
    done
    
    # 等待命令完成
    wait $pid
    local exit_code=$?
    
    # 清除进度条
    printf "\r${GREEN}[SUCCESS]${NC} $message 完成"
    printf "%$((${#message} + 20))s" ""
    echo ""
    
    return $exit_code
}

# ===========================================
# 检测 Docker Compose 命令
# ===========================================

detect_docker_compose() {
    # 优先使用 docker compose (v2)
    if docker compose version &>/dev/null 2>&1; then
        DOCKER_COMPOSE_CMD="docker compose"
        log_info "使用 Docker Compose V2 (docker compose)"
    # 回退到 docker-compose (v1)
    elif command -v docker-compose &>/dev/null; then
        DOCKER_COMPOSE_CMD="docker-compose"
        log_info "使用 Docker Compose V1 (docker-compose)"
    else
        log_error "未找到 Docker Compose 命令"
        exit 1
    fi
}

# ===========================================
# 检查容器是否运行
# ===========================================

is_container_running() {
    docker ps --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$" 2>/dev/null
}

is_web_container_running() {
    docker ps --format '{{.Names}}' | grep -q "^${WEB_CONTAINER_NAME}$" 2>/dev/null
}

ensure_web_container_running() {
    if ! is_web_container_running; then
        log_error "Web 前端容器 $WEB_CONTAINER_NAME 未运行，请先启动 Docker Compose"
        return 1
    fi
    return 0
}

# ===========================================
# 检查容器是否存在（包括已停止的）
# ===========================================

container_exists() {
    docker ps -a --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$" 2>/dev/null
}

# ===========================================
# 帮助信息
# ===========================================

show_help() {
    echo "ECShopX 开发环境设置脚本"
    echo ""
    echo "用法: $0 [选项]"
    echo ""
    echo "选项:"
    echo "  --help, -h       显示帮助信息"
    echo "  --rebuild        强制重新构建镜像（不使用缓存）"
    echo "  --skip-admin     跳过 ECShopX_admin-frontend 编译"
    echo "  --skip-vshop     跳过 ECShopX_mobile-frontend 编译"
    echo "  --skip-pc        跳过 ECShopX_web-frontend 编译"
    echo "  --site-url URL   兼容快捷项：设置管理后台/API 地址，如 https://admin.example.com"
    echo "  --admin-url URL  覆盖管理后台访问地址"
    echo "  --h5-url URL     覆盖 H5 前端访问地址"
    echo "  --pc-url URL     覆盖 PC 前端访问地址"
    echo ""
    echo "示例:"
    echo "  $0                    # 正常启动（使用缓存）"
    echo "  $0 --rebuild          # 重新构建镜像"
    echo "  $0 --skip-admin       # 跳过管理后台编译"
    echo "  $0 --site-url https://admin.example.com"
    echo "  $0 --admin-url https://admin.example.com --h5-url https://m.example.com --pc-url https://www.example.com"
    echo ""
    exit 0
}

# ===========================================
# 解析命令行参数
# ===========================================

parse_args() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --help|-h)
                show_help
                ;;
            --rebuild)
                REBUILD=true
                shift
                ;;
            --skip-admin)
                SKIP_ADMIN=true
                shift
                ;;
            --skip-vshop)
                SKIP_VSHOP=true
                shift
                ;;
            --skip-pc)
                SKIP_PC=true
                shift
                ;;
            --site-url)
                if [ -z "${2:-}" ]; then
                    log_error "--site-url 需要提供 URL"
                    exit 1
                fi
                SITE_URL="$2"
                shift 2
                ;;
            --admin-url)
                if [ -z "${2:-}" ]; then
                    log_error "--admin-url 需要提供 URL"
                    exit 1
                fi
                ADMIN_URL_OVERRIDE="$2"
                shift 2
                ;;
            --h5-url)
                if [ -z "${2:-}" ]; then
                    log_error "--h5-url 需要提供 URL"
                    exit 1
                fi
                H5_URL_OVERRIDE="$2"
                shift 2
                ;;
            --pc-url)
                if [ -z "${2:-}" ]; then
                    log_error "--pc-url 需要提供 URL"
                    exit 1
                fi
                PC_URL_OVERRIDE="$2"
                shift 2
                ;;
            *)
                log_error "未知参数: $1"
                echo "使用 --help 查看帮助信息"
                exit 1
                ;;
        esac
    done
}

# ===========================================
# 检测操作系统
# ===========================================

detect_os() {
    if [[ "$OSTYPE" == "darwin"* ]]; then
        OS="macos"
    elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
        OS="linux"
    else
        log_error "不支持的操作系统: $OSTYPE"
        exit 1
    fi
    log_info "检测到操作系统: $OS"
}

# ===========================================
# 检测 Docker
# ===========================================

check_docker() {
    log_info "检测 Docker 安装状态..."
    
    if ! command -v docker &> /dev/null; then
        log_error "Docker 未安装"
        log_info "安装方式:"
        log_info "  macOS: 下载 Docker Desktop https://www.docker.com/products/docker-desktop"
        log_info "  Linux: sudo apt-get install docker.io docker-compose (Ubuntu/Debian)"
        exit 1
    fi
    
    # 检测 Docker Compose 命令
    detect_docker_compose
    
    # 检查 Docker 是否正在运行
    if ! docker info &> /dev/null 2>&1; then
        log_warning "Docker 未运行，尝试自动启动..."
        
        if [ "$OS" = "macos" ]; then
            open -a Docker
            log_info "正在启动 Docker Desktop，请等待..."
            
            for i in {1..60}; do
                if docker info &> /dev/null 2>&1; then
                    log_success "Docker 已启动"
                    break
                fi
                sleep 2
                if [ $((i % 5)) -eq 0 ]; then
                    log_info "  等待 Docker 启动... ($i/60)"
                fi
            done
            
            if ! docker info &> /dev/null 2>&1; then
                log_error "Docker 启动超时，请手动启动 Docker Desktop 后重新运行脚本"
                exit 1
            fi
        elif [ "$OS" = "linux" ]; then
            sudo systemctl start docker || {
                log_error "Docker 启动失败，请手动启动"
                exit 1
            }
            sleep 3
            if ! docker info &> /dev/null 2>&1; then
                log_error "Docker 启动失败"
                exit 1
            fi
            log_success "Docker 已启动"
        fi
    fi
    
    log_success "Docker 和 Docker Compose 已就绪"
}

# ===========================================
# 检查并克隆前端项目
# ===========================================

check_and_clone_frontend() {
    log_step "检查前端项目目录..."
    
    # 检查 ECShopX_web-frontend
    PC_DIR="$PARENT_DIR/ECShopX_web-frontend"
    PC_REPO="$PUBLIC_REPO_BASE_URL/ECShopX_web-frontend.git"
    
    if [ ! -d "$PC_DIR" ] || [ ! -f "$PC_DIR/package.json" ]; then
        if [ ! -d "$PC_DIR" ]; then
            log_warning "ECShopX_web-frontend 目录不存在"
        else
            log_warning "ECShopX_web-frontend 目录存在但缺少 package.json"
        fi
        
        echo ""
        echo -n "是否从 Gitee 克隆PC商城（ECShopX_web-frontend）代码？ [Y/n]: "
        read -r answer < /dev/tty
        
        if [ -z "$answer" ] || [ "$answer" = "Y" ] || [ "$answer" = "y" ] || [ "$answer" = "yes" ] || [ "$answer" = "YES" ]; then
            if [ -d "$PC_DIR" ]; then
                log_info "清空现有目录内容..."
                find "$PC_DIR" -mindepth 1 -delete 2>/dev/null
            fi
            
            log_info "正在从 Gitee 克隆PC商城（ECShopX_web-frontend）..."
            git clone "$PC_REPO" "$PC_DIR" > /tmp/git_clone_pc.log 2>&1 &
            local clone_pid=$!
            
            # 显示进度动画
            local spinstr='|/-\'
            while kill -0 $clone_pid 2>/dev/null; do
                local temp=${spinstr#?}
                printf "\r${CYAN}[INFO]${NC} 克隆PC商城（ECShopX_web-frontend）中 ${spinstr:0:1}"
                spinstr=$temp${spinstr%"$temp"}
                sleep 0.2
            done
            wait $clone_pid
            local clone_exit=$?
            
            if [ $clone_exit -eq 0 ]; then
                printf "\r${GREEN}[SUCCESS]${NC}PC商城（ECShopX_web-frontend）克隆成功"
                printf "%50s" ""
                echo ""
                INSTALLED_PC=true
            else
                printf "\r${RED}[ERROR]${NC}PC商城（ECShopX_web-frontend）克隆失败"
                printf "%50s" ""
                echo ""
                cat /tmp/git_clone_pc.log 2>/dev/null | tail -10
                exit 1
            fi
        else
            log_warning "跳过PC商城（ECShopX_web-frontend）克隆"
        fi
    else
        log_info "ECShopX_web-frontend 目录已存在且包含 package.json，跳过克隆"
        INSTALLED_PC=true
    fi
    
    # 检查 ECShopX_admin-frontend
    ADMIN_DIR="$PARENT_DIR/ECShopX_admin-frontend"
    ADMIN_REPO="$PUBLIC_REPO_BASE_URL/ECShopX_admin-frontend.git"
    
    if [ ! -d "$ADMIN_DIR" ] || [ ! -f "$ADMIN_DIR/package.json" ]; then
        if [ ! -d "$ADMIN_DIR" ]; then
            log_warning "ECShopX_admin-frontend 目录不存在"
        else
            log_warning "ECShopX_admin-frontend 目录存在但缺少 package.json"
        fi
        
        echo ""
        echo -n "是否从 Gitee 克隆管理后台（ECShopX_admin-frontend）代码？ [Y/n]: "
        read -r answer < /dev/tty
        
        if [ -z "$answer" ] || [ "$answer" = "Y" ] || [ "$answer" = "y" ] || [ "$answer" = "yes" ] || [ "$answer" = "YES" ]; then
            if [ -d "$ADMIN_DIR" ]; then
                log_info "清空现有目录内容..."
                find "$ADMIN_DIR" -mindepth 1 -delete 2>/dev/null
            fi
            
            log_info "正在从 Gitee 克隆管理后台（ECShopX_admin-frontend）..."
            git clone "$ADMIN_REPO" "$ADMIN_DIR" > /tmp/git_clone_admin.log 2>&1 &
            local clone_pid=$!
            
            # 显示进度动画
            local spinstr='|/-\'
            while kill -0 $clone_pid 2>/dev/null; do
                local temp=${spinstr#?}
                printf "\r${CYAN}[INFO]${NC} 克隆管理后台（ECShopX_admin-frontend）中 ${spinstr:0:1}"
                spinstr=$temp${spinstr%"$temp"}
                sleep 0.2
            done
            wait $clone_pid
            local clone_exit=$?
            
            if [ $clone_exit -eq 0 ]; then
                printf "\r${GREEN}[SUCCESS]${NC} 管理后台（ECShopX_admin-frontend）克隆成功"
                printf "%50s" ""
                echo ""
                INSTALLED_ADMIN=true
            else
                printf "\r${RED}[ERROR]${NC} 管理后台（ECShopX_admin-frontend）克隆失败"
                printf "%50s" ""
                echo ""
                cat /tmp/git_clone_admin.log 2>/dev/null | tail -10
                exit 1
            fi
        else
            log_warning "跳过管理后台（ECShopX_admin-frontend）克隆"
        fi
    else
        log_info "ECShopX_admin-frontend 目录已存在且包含 package.json，跳过克隆"
        INSTALLED_ADMIN=true
    fi
    # 修复语法错误：注释掉重复的 else...fi 块（原代码第401-403行）
    # else
    #     log_info "ECShopX_admin-frontend 目录已存在且包含 package.json，跳过克隆"
    # fi
    
    # 检查 ECShopX_mobile-frontend
    VSHOP_DIR="$PARENT_DIR/ECShopX_mobile-frontend"
    VSHOP_REPO="$PUBLIC_REPO_BASE_URL/ECShopX_mobile-frontend.git"
    
    if [ ! -d "$VSHOP_DIR" ] || [ ! -f "$VSHOP_DIR/package.json" ]; then
        if [ ! -d "$VSHOP_DIR" ]; then
            log_warning "ECShopX_mobile-frontend 目录不存在"
        else
            log_warning "ECShopX_mobile-frontend 目录存在但缺少 package.json"
        fi
        
        echo ""
        echo -n "是否从 Gitee 克隆移动商城（ECShopX_mobile-frontend）代码？ [Y/n]: "
        read -r answer < /dev/tty
        
        if [ -z "$answer" ] || [ "$answer" = "Y" ] || [ "$answer" = "y" ] || [ "$answer" = "yes" ] || [ "$answer" = "YES" ]; then
            if [ -d "$VSHOP_DIR" ]; then
                log_info "清空现有目录内容..."
                find "$VSHOP_DIR" -mindepth 1 -delete 2>/dev/null
            fi
            
            log_info "正在从 Gitee 克隆移动商城（ECShopX_mobile-frontend）..."
            git clone "$VSHOP_REPO" "$VSHOP_DIR" > /tmp/git_clone_vshop.log 2>&1 &
            local clone_pid=$!
            
            # 显示进度动画
            local spinstr='|/-\'
            while kill -0 $clone_pid 2>/dev/null; do
                local temp=${spinstr#?}
                printf "\r${CYAN}[INFO]${NC} 克隆移动商城（ECShopX_mobile-frontend）中 ${spinstr:0:1}"
                spinstr=$temp${spinstr%"$temp"}
                sleep 0.2
            done
            wait $clone_pid
            local clone_exit=$?
            
            if [ $clone_exit -eq 0 ]; then
                printf "\r${GREEN}[SUCCESS]${NC}移动商城（ECShopX_mobile-frontend）克隆成功"
                printf "%50s" ""
                echo ""
                INSTALLED_VSHOP=true
            else
                printf "\r${RED}[ERROR]${NC}移动商城（ECShopX_mobile-frontend）克隆失败"
                printf "%50s" ""
                echo ""
                cat /tmp/git_clone_vshop.log 2>/dev/null | tail -10
                exit 1
            fi
        else
            log_warning "跳过移动商城（ECShopX_mobile-frontend）克隆"
        fi
    else
        log_info "ECShopX_mobile-frontend 目录已存在且包含 package.json，跳过克隆"
        INSTALLED_VSHOP=true
    fi
    
    echo ""
}

# ===========================================
# 检查容器状态
# ===========================================

check_container_status() {
    # 检查容器是否存在（包括已停止的）
    if docker ps -a --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
        # 检查容器是否正在运行
        if docker ps --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
            log_warning "容器 $CONTAINER_NAME 正在运行"
        else
            log_warning "容器 $CONTAINER_NAME 已存在但未运行"
        fi
        echo ""
        echo -n "请选择操作: [1] 重启容器  [2] 使用现有容器  [3] 退出: "
        read -r choice < /dev/tty
        
            case $choice in
            1)
                log_info "停止并删除现有容器..."
                $DOCKER_COMPOSE_CMD -f "$DOCKER_COMPOSE_FILE" down
                return 0  # 需要重新构建
                ;;
            2)
                log_info "使用现有容器..."
                log_info "确保 Docker Compose 中的所有服务已启动..."
                $DOCKER_COMPOSE_CMD -f "$DOCKER_COMPOSE_FILE" up -d
                # 如果容器未运行，先启动它
                if ! is_container_running; then
                    log_info "启动现有容器..."
                    sleep 5
                    wait_for_services
                elif ! is_web_container_running; then
                    log_info "等待 Web 前端容器启动..."
                    sleep 5
                fi
                return 1  # 跳过构建
                ;;
            3)
                log_info "退出脚本"
                exit 0
                ;;
            *)
                log_info "使用现有容器..."
                log_info "确保 Docker Compose 中的所有服务已启动..."
                $DOCKER_COMPOSE_CMD -f "$DOCKER_COMPOSE_FILE" up -d
                return 1
                ;;
        esac
    fi
    return 0
}

# ===========================================
# 配置环境文件
# ===========================================

configure_env() {
    log_info "配置环境文件..."
    cd "$PROJECT_ROOT"
    
    if [ ! -f ".env" ]; then
        for template in ".env.full" ".env.example" ".env.template"; do
            if [ -f "$template" ]; then
                log_info "从 $template 复制创建 .env 文件..."
                cp "$template" .env
                break
            fi
        done
    fi
}

# ===========================================
# 构建并启动 Docker 容器
# ===========================================

run_docker() {
    log_step "启动 Docker 容器..."
    
    cd "$PROJECT_ROOT"
    
    if [ ! -f "$DOCKER_COMPOSE_FILE" ]; then
        log_error "未找到 $DOCKER_COMPOSE_FILE 文件"
        exit 1
    fi
    
    # 构建镜像
    if [ "$REBUILD" = true ]; then
        log_info "强制重新构建镜像（--no-cache）..."
        log_info "这可能需要较长时间，请耐心等待..."
        $DOCKER_COMPOSE_CMD -f "$DOCKER_COMPOSE_FILE" build --no-cache > /tmp/docker_build.log 2>&1 &
        local build_pid=$!
        
        # 显示进度动画
        local spinstr='|/-\'
        while kill -0 $build_pid 2>/dev/null; do
            local temp=${spinstr#?}
            printf "\r${CYAN}[INFO]${NC} 构建 Docker 镜像中 ${spinstr:0:1}"
            spinstr=$temp${spinstr%"$temp"}
            sleep 0.3
        done
        wait $build_pid
        local build_exit=$?
        
        if [ $build_exit -ne 0 ]; then
            printf "\r${RED}[ERROR]${NC} Docker 镜像构建失败"
            printf "%50s" ""
            echo ""
            cat /tmp/docker_build.log 2>/dev/null | tail -30
            exit 1
        else
            printf "\r${GREEN}[SUCCESS]${NC} Docker 镜像构建完成"
            printf "%50s" ""
            echo ""
        fi
    else
        log_info "构建镜像（使用缓存）..."
        $DOCKER_COMPOSE_CMD -f "$DOCKER_COMPOSE_FILE" build > /tmp/docker_build.log 2>&1 &
        local build_pid=$!
        
        # 显示进度动画
        local spinstr='|/-\'
        while kill -0 $build_pid 2>/dev/null; do
            local temp=${spinstr#?}
            printf "\r${CYAN}[INFO]${NC} 构建 Docker 镜像中 ${spinstr:0:1}"
            spinstr=$temp${spinstr%"$temp"}
            sleep 0.3
        done
        wait $build_pid
        local build_exit=$?
        
        if [ $build_exit -ne 0 ]; then
            printf "\r${RED}[ERROR]${NC} Docker 镜像构建失败"
            printf "%50s" ""
            echo ""
            cat /tmp/docker_build.log 2>/dev/null | tail -30
            exit 1
        else
            printf "\r${GREEN}[SUCCESS]${NC} Docker 镜像构建完成"
            printf "%50s" ""
            echo ""
        fi
    fi
    
    # 启动容器
    log_info "启动容器..."
    $DOCKER_COMPOSE_CMD -f "$DOCKER_COMPOSE_FILE" up -d || {
        log_error "Docker 服务启动失败"
        exit 1
    }
    
    log_info "等待服务启动..."
    sleep 5
    
    # 显示容器状态
    $DOCKER_COMPOSE_CMD -f "$DOCKER_COMPOSE_FILE" ps
    
    # 等待服务就绪
    wait_for_services
}

# ===========================================
# 等待服务就绪
# ===========================================

wait_for_services() {
    # 等待 Supervisor
    log_info "等待 Supervisor 启动..."
    for i in {1..20}; do
        if docker exec "$CONTAINER_NAME" supervisorctl status &>/dev/null 2>&1; then
            break
        fi
        sleep 1
    done
    
    # 等待 MySQL
    log_info "等待 MySQL 服务就绪..."
    for i in {1..60}; do
        if docker exec "$CONTAINER_NAME" mysqladmin ping --socket=/var/run/mysqld/mysqld.sock -u root -p"$MYSQL_ROOT_PWD" --silent 2>/dev/null || \
           docker exec "$CONTAINER_NAME" mysqladmin ping -h 127.0.0.1 -u root -p"$MYSQL_ROOT_PWD" --silent 2>/dev/null; then
            log_success "MySQL 服务已就绪"
            break
        fi
        sleep 2
        if [ $((i % 5)) -eq 0 ]; then
            log_info "  等待 MySQL... ($i/60)"
        fi
        if [ $i -eq 60 ]; then
            log_error "MySQL 服务启动超时"
            docker exec "$CONTAINER_NAME" tail -n 30 /var/log/supervisor/mysql.log 2>/dev/null || true
            exit 1
        fi
    done
    
    # 等待 Redis
    log_info "等待 Redis 服务就绪..."
    for i in {1..30}; do
        if docker exec "$CONTAINER_NAME" redis-cli -a "$REDIS_PASSWORD" ping 2>/dev/null | grep -q "PONG"; then
            log_success "Redis 服务已就绪"
            break
        fi
        sleep 2
        if [ $((i % 5)) -eq 0 ]; then
            log_info "  等待 Redis... ($i/30)"
        fi
        if [ $i -eq 30 ]; then
            log_error "Redis 服务启动超时"
            docker exec "$CONTAINER_NAME" tail -n 30 /var/log/supervisor/redis.log 2>/dev/null || true
            exit 1
        fi
    done
}

# ===========================================
# 从 ECShopX .env 读取 PRODUCT_MODEL 并设置 SELECTED_PLATFORM
# ===========================================

read_product_model_from_env() {
    if [ -n "$SELECTED_PLATFORM" ]; then
        return 0
    fi

    local product_model=""
    product_model=$(docker exec "$CONTAINER_NAME" sh -c \
        "cd /data/httpd/ECShopX && grep '^PRODUCT_MODEL=' .env 2>/dev/null | cut -d'=' -f2" 2>/dev/null)

    if [ "$product_model" = "standard" ]; then
        SELECTED_PLATFORM="standard"
        log_info "从 ECShopX 配置文件读取业务模式: B2C (PRODUCT_MODEL=standard)"
    elif [ "$product_model" = "platform" ]; then
        SELECTED_PLATFORM="platform"
        log_info "从 ECShopX 配置文件读取业务模式: BBC (PRODUCT_MODEL=platform)"
    else
        log_warning "未能从 ECShopX 配置文件读取 PRODUCT_MODEL，将使用默认值 platform"
        SELECTED_PLATFORM="platform"
    fi
}

preserve_existing_product_model() {
    local product_model=""

    product_model=$(read_env_value "$PROJECT_ROOT/.env" "PRODUCT_MODEL")

    if [ -z "$product_model" ] && docker ps --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$" 2>/dev/null; then
        product_model=$(docker exec "$CONTAINER_NAME" sh -c \
            "cd /data/httpd/ECShopX && grep '^PRODUCT_MODEL=' .env 2>/dev/null | cut -d'=' -f2" 2>/dev/null || true)
    fi

    if [ "$product_model" = "standard" ]; then
        SELECTED_PLATFORM="standard"
        log_info "检测到现有 PRODUCT_MODEL=standard，保留 B2C 业务模式"
        return 0
    fi

    if [ "$product_model" = "platform" ]; then
        SELECTED_PLATFORM="platform"
        log_info "检测到现有 PRODUCT_MODEL=platform，保留 BBC 业务模式"
        return 0
    fi

    return 1
}

load_existing_frontend_lang() {
    if [ -n "$SELECTED_LANG" ]; then
        return 0
    fi

    local lang=""
    lang=$(read_env_value "$PARENT_DIR/ECShopX_admin-frontend/.env" "VUE_APP_DEFAULT_LANG")
    if [ -z "$lang" ]; then
        lang=$(read_env_value "$PARENT_DIR/ECShopX_web-frontend/.env" "NUXT_PUBLIC_DEFAULT_COUNTRY_CODE")
    fi
    if [ -z "$lang" ]; then
        lang=$(read_env_value "$PARENT_DIR/ECShopX_mobile-frontend/.env" "APP_I18N_ORIGIN_LANG")
    fi

    if [ "$lang" = "zh-CN" ]; then
        SELECTED_LANG="zhcn"
        log_info "从现有前端 .env 读取默认语言: $SELECTED_LANG"
    elif [ "$lang" = "en-CN" ]; then
        SELECTED_LANG="en"
        log_info "从现有前端 .env 读取默认语言: $SELECTED_LANG"
    elif [ "$lang" = "zhcn" ] || [ "$lang" = "en" ]; then
        SELECTED_LANG="$lang"
        log_info "从现有前端 .env 读取默认语言: $SELECTED_LANG"
    fi
}

ensure_selected_lang() {
    load_existing_frontend_lang

    if [ -n "$SELECTED_LANG" ]; then
        log_info "复用默认语言: $SELECTED_LANG"
        return 0
    fi

    echo ""
    log_info "请选择默认语言："
    log_info "  1) 中文 (zhcn)"
    log_info "  2) 英文 (en)"
    echo ""
    while true; do
        read -r -p "请输入选项 (1 或 2，默认: 1): " LANG_CHOICE < /dev/tty
        LANG_CHOICE=${LANG_CHOICE:-1}
        if [ "$LANG_CHOICE" = "1" ] || [ "$LANG_CHOICE" = "zhcn" ] || [ "$LANG_CHOICE" = "zh" ]; then
            SELECTED_LANG="zhcn"
            break
        elif [ "$LANG_CHOICE" = "2" ] || [ "$LANG_CHOICE" = "en" ] || [ "$LANG_CHOICE" = "english" ]; then
            SELECTED_LANG="en"
            break
        else
            log_error "无效的选项，请输入 1 或 2"
        fi
    done
    echo ""
    log_info "已选择默认语言: $SELECTED_LANG"
    echo ""
}

pc_api_country_code_for_lang() {
    local lang=$1

    case "$lang" in
        en|en-CN|en_US|en-US)
            echo "en-CN"
            ;;
        zhcn|zh|zh-CN|zh_CN|"")
            echo "zh-CN"
            ;;
        *)
            echo "$lang"
            ;;
    esac
}

# ===========================================
# 配置 PHP 应用
# ===========================================

configure_application() {
    log_info "=========================================="
    log_info "开始配置 ECShopX（PHP API）..."
    log_info "=========================================="
    
    # 配置本地 .env 文件
    configure_env
    
    # 配置容器内 .env 文件
    log_info "配置应用环境..."
    docker exec "$CONTAINER_NAME" sh -c "cd /data/httpd/ECShopX && \
        if [ ! -f .env ]; then \
            cp .env.example .env 2>/dev/null || cp .env.full .env 2>/dev/null || touch .env; \
        fi" || true
    
    # 更新数据库配置和存储配置
    log_info "更新 .env 配置..."
    docker exec "$CONTAINER_NAME" sh -c "cd /data/httpd/ECShopX && \
        sed -i 's/^DB_HOST=.*/DB_HOST=127.0.0.1/' .env && \
        sed -i 's/^DB_PORT=.*/DB_PORT=3306/' .env && \
        sed -i 's/^DB_DATABASE=.*/DB_DATABASE=$MYSQL_DATABASE/' .env && \
        sed -i 's/^DB_USERNAME=.*/DB_USERNAME=$MYSQL_USER/' .env && \
        sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=$MYSQL_PASSWORD/' .env && \
        sed -i 's/^REDIS_HOST=.*/REDIS_HOST=127.0.0.1/' .env && \
        sed -i 's/^REDIS_PORT=.*/REDIS_PORT=6379/' .env && \
        sed -i 's/^REDIS_PASSWORD=.*/REDIS_PASSWORD=$REDIS_PASSWORD/' .env && \
        sed -i 's/^REDIS_DATABASE=.*/REDIS_DATABASE=0/' .env && \
        (grep -q '^DISK_DRIVER=' .env && sed -i 's/^DISK_DRIVER=.*/DISK_DRIVER=local/' .env || echo 'DISK_DRIVER=local' >> .env) && \
        (grep -q '^APP_URL=' .env && sed -i 's|^APP_URL=.*|APP_URL=$ADMIN_URL|' .env || echo 'APP_URL=$ADMIN_URL' >> .env) && \
        (grep -q '^H5_BASE_URL=' .env && sed -i 's|^H5_BASE_URL=.*|H5_BASE_URL=$H5_URL|' .env || echo 'H5_BASE_URL=$H5_URL' >> .env) && \
        (grep -q '^SHOP_ADMIN_URL=' .env && sed -i 's|^SHOP_ADMIN_URL=.*|SHOP_ADMIN_URL=$ADMIN_URL/|' .env || echo 'SHOP_ADMIN_URL=$ADMIN_URL/' >> .env) && \
        (grep -q '^API_BASE_URL=' .env && sed -i 's|^API_BASE_URL=.*|API_BASE_URL=$API_BASE_URL|' .env || echo 'API_BASE_URL=$API_BASE_URL' >> .env)" 2>/dev/null || true
    
    # 选择业务模式并写入 PRODUCT_MODEL；重跑脚本时优先保留已有安装模式
    if ! preserve_existing_product_model; then
        echo ""
        log_info "请选择业务模式："
        log_info "  1) BBC (多商户入驻电商平台模式)"
        log_info "  2) B2C (线上商城、O2O云店、内购商城、供应链线上商城等模式)"
        echo ""
        while true; do
            read -r -p "请输入选项 (1 或 2，默认: 1): " BIZ_MODE_CHOICE < /dev/tty
            BIZ_MODE_CHOICE=${BIZ_MODE_CHOICE:-1}
            if [ "$BIZ_MODE_CHOICE" = "1" ] || [ "$BIZ_MODE_CHOICE" = "bbc" ]; then
                SELECTED_PLATFORM="platform"
                log_info "已选择业务模式: BBC (B2B2C)"
                break
            elif [ "$BIZ_MODE_CHOICE" = "2" ] || [ "$BIZ_MODE_CHOICE" = "b2c" ]; then
                SELECTED_PLATFORM="standard"
                log_info "已选择业务模式: B2C"
                break
            else
                log_error "无效的选项，请输入 1 或 2"
            fi
        done
        echo ""
    fi

    # 将 PRODUCT_MODEL 写入 ECShopX 的 .env
    docker exec "$CONTAINER_NAME" sh -c "cd /data/httpd/ECShopX && \
        if grep -q '^PRODUCT_MODEL=' .env 2>/dev/null; then \
            sed -i 's/^PRODUCT_MODEL=.*/PRODUCT_MODEL=$SELECTED_PLATFORM/' .env; \
        else \
            echo 'PRODUCT_MODEL=$SELECTED_PLATFORM' >> .env; \
        fi" 2>/dev/null || true
    log_success "已将 PRODUCT_MODEL=$SELECTED_PLATFORM 写入 ECShopX .env"
    
    # 安装 Composer 依赖
    log_info "检查并安装 Composer 依赖..."
    
    # 检查是否需要安装依赖
    NEED_INSTALL=$(docker exec "$CONTAINER_NAME" sh -c "cd /data/httpd/ECShopX && \
        if [ ! -f composer.phar ]; then \
            echo 'ERROR'; \
        elif [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then \
            echo 'YES'; \
        else \
            echo 'NO'; \
        fi" 2>/dev/null)
    
    if [ "$NEED_INSTALL" = "ERROR" ]; then
        log_error "composer.phar 文件不存在"
        exit 1
    elif [ "$NEED_INSTALL" = "YES" ]; then
        log_info "开始安装 Composer 依赖（这可能需要一些时间）..."
        # 后台执行并显示进度
        docker exec "$CONTAINER_NAME" sh -c "cd /data/httpd/ECShopX && \
            php -d memory_limit=-1 composer.phar config repo.packagist composer https://mirrors.aliyun.com/composer/ 2>/dev/null || true && \
            php -d memory_limit=-1 composer.phar install -o --no-interaction --prefer-dist" > /tmp/composer_output.log 2>&1 &
        local composer_pid=$!
        
        # 显示进度动画
        local spinstr='|/-\'
        while kill -0 $composer_pid 2>/dev/null; do
            local temp=${spinstr#?}
            printf "\r${CYAN}[INFO]${NC} 安装 Composer 依赖中 ${spinstr:0:1}"
            spinstr=$temp${spinstr%"$temp"}
            sleep 0.2
        done
        wait $composer_pid
        local composer_exit=$?
        
        if [ $composer_exit -eq 0 ]; then
            printf "\r${GREEN}[SUCCESS]${NC} Composer 依赖安装完成"
            printf "%50s" ""
            echo ""
        else
            printf "\r${RED}[ERROR]${NC} Composer 依赖安装失败"
            printf "%50s" ""
            echo ""
            cat /tmp/composer_output.log 2>/dev/null | tail -20
            exit 1
        fi
    else
        log_success "Composer 依赖已存在，跳过安装"
    fi
    
    # 生成应用密钥（仅在不存在时）
    APP_KEY=$(docker exec "$CONTAINER_NAME" sh -c "cd /data/httpd/ECShopX && grep '^APP_KEY=' .env | cut -d'=' -f2" 2>/dev/null)
    if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
        log_info "生成应用密钥..."
        docker exec "$CONTAINER_NAME" sh -c "cd /data/httpd/ECShopX && php artisan key:generate --force" 2>/dev/null || {
            log_warning "应用密钥生成失败"
        }
    else
        log_info "应用密钥已存在，跳过生成"
    fi
    
    # 生成 JWT 密钥（仅在不存在时）
    JWT_SECRET=$(docker exec "$CONTAINER_NAME" sh -c "cd /data/httpd/ECShopX && grep '^JWT_SECRET=' .env | cut -d'=' -f2" 2>/dev/null)
    if [ -z "$JWT_SECRET" ] || [ "$JWT_SECRET" = "" ]; then
        log_info "生成 JWT 密钥..."
        docker exec "$CONTAINER_NAME" sh -c "cd /data/httpd/ECShopX && php artisan jwt:secret --force" 2>/dev/null || {
            log_warning "JWT 密钥生成失败"
        }
    else
        log_info "JWT 密钥已存在，跳过生成"
    fi
    
    # 迁移前修正 ECShopX 目录权限（容器内 www-data:www-data）
    log_info "修正 ECShopX 目录权限为 www-data:www-data..."
    docker exec "$CONTAINER_NAME" chown -R www-data:www-data /data/httpd/ECShopX 2>/dev/null || {
        log_warning "修正目录权限失败，继续执行迁移"
    }
    
    # 执行数据库迁移
    log_info "执行数据库迁移..."
    docker exec "$CONTAINER_NAME" sh -c "cd /data/httpd/ECShopX && php artisan doctrine:migrations:migrate --no-interaction" > /tmp/migration_output.log 2>&1 &
    local migration_pid=$!
    
    # 显示进度动画
    local spinstr='|/-\'
    while kill -0 $migration_pid 2>/dev/null; do
        local temp=${spinstr#?}
        printf "\r${CYAN}[INFO]${NC} 执行数据库迁移中 ${spinstr:0:1}"
        spinstr=$temp${spinstr%"$temp"}
        sleep 0.2
    done
    wait $migration_pid
    local migration_exit=$?
    
    if [ $migration_exit -ne 0 ]; then
        printf "\r${RED}[ERROR]${NC} 数据库迁移失败"
        printf "%50s" ""
        echo ""
        cat /tmp/migration_output.log 2>/dev/null | tail -20
        log_info "请手动执行: docker exec $CONTAINER_NAME sh -c 'cd /data/httpd/ECShopX && php artisan doctrine:migrations:migrate --no-interaction'"
        exit 1
    else
        printf "\r${GREEN}[SUCCESS]${NC} 数据库迁移完成"
        printf "%50s" ""
        echo ""
    fi
    
    # 创建存储链接（storage:link）
    log_info "创建存储目录链接..."
    docker exec "$CONTAINER_NAME" sh -c "cd /data/httpd/ECShopX && \
        if [ ! -L public/storage ] && [ ! -d public/storage ]; then \
            php artisan storage:link 2>/dev/null || true; \
        fi" || {
        log_warning "存储链接创建失败，将稍后重试"
    }
    log_success "存储链接配置完成"
    
    # 初始化管理员密码
    init_admin_password

    # 初始化阿里云短信场景
    log_info "初始化阿里云短信场景..."
    docker exec "$CONTAINER_NAME" sh -c "cd /data/httpd/ECShopX && php artisan aliyunsms:scene:initialize 1" || {
        log_warning "阿里云短信场景初始化失败"
    }
    log_success "阿里云短信场景初始化完成"
}

# ===========================================
# 初始化管理员密码
# ===========================================

init_admin_password() {
    echo ""
    while true; do
        echo -n "请输入管理员密码: "
        read -rs admin_password < /dev/tty
        echo ""  # 换行
        
        if [ -z "$admin_password" ]; then
            log_warning "密码不能为空，请重新输入"
            continue
        fi
        
        echo -n "请再次确认密码: "
        read -rs admin_password_confirm < /dev/tty
        echo ""  # 换行
        
        if [ "$admin_password" != "$admin_password_confirm" ]; then
            log_warning "两次输入的密码不一致，请重新输入"
            continue
        fi
        break
    done
    
    log_info "初始化管理员密码..."
    docker exec "$CONTAINER_NAME" sh -c "cd /data/httpd/ECShopX && php artisan account:init-admin-password '$admin_password'" || {
        log_warning "管理员密码初始化失败"
    }
    log_success "管理员密码初始化完成"
}

# ===========================================
# Crontab 与队列（Supervisor）
# ===========================================

configure_cron_and_supervisor_queues() {
    log_info "=========================================="
    log_info "配置 Crontab 定时任务与队列（Supervisor）..."
    log_info "=========================================="

    if ! is_container_running; then
        log_warning "容器未运行，跳过 Crontab 与队列配置"
        return 0
    fi

    local cron_dir="$PROJECT_ROOT/docker-dev/cron"
    local sup_dir="$PROJECT_ROOT/docker-dev/supervisor"

    if [ -d "$cron_dir" ]; then
        local has_cron=false
        for f in "$cron_dir"/*; do
            [ -e "$f" ] || continue
            [ -f "$f" ] || continue
            has_cron=true
            local user
            user=$(basename "$f")
            log_info "安装 crontab（用户: $user）: $f"
            docker cp "$f" "$CONTAINER_NAME:/tmp/crontab.install.$user"
            if docker exec "$CONTAINER_NAME" sh -c "crontab -u "$user" "/tmp/crontab.install.$user"" 2>/dev/null; then
                log_success "crontab 已安装: $user"
            else
                log_warning "crontab 安装失败（用户: $user）。请确认镜像已包含 dcron 且 supervisord 已配置 [program:crond]，必要时执行: $0 --rebuild"
            fi
        done
        if [ "$has_cron" = false ]; then
            log_warning "目录 $cron_dir 下无 crontab 文件"
        else
            if docker exec "$CONTAINER_NAME" supervisorctl status crond &>/dev/null; then
                docker exec "$CONTAINER_NAME" supervisorctl restart crond 2>/dev/null || true
            fi
        fi
    else
        log_warning "未找到目录: $cron_dir，跳过 Crontab"
    fi

    if [ ! -d "$sup_dir" ]; then
        log_warning "未找到目录: $sup_dir，跳过队列配置"
        return 0
    fi

    local has_sup=false
    for ini in "$sup_dir"/*.ini; do
        [ -f "$ini" ] || continue
        has_sup=true
        local base
        base=$(basename "$ini" .ini)
        log_info "安装 Supervisor 片段: ${base}.conf"
        docker cp "$ini" "$CONTAINER_NAME:/etc/supervisor/conf.d/${base}.conf"
    done

    if [ "$has_sup" = false ]; then
        log_warning "目录 $sup_dir 下无 .ini 配置"
        return 0
    fi

    log_info "重新加载 Supervisor 并应用队列配置..."
    if docker exec "$CONTAINER_NAME" supervisorctl reread 2>/dev/null && \
       docker exec "$CONTAINER_NAME" supervisorctl update 2>/dev/null; then
        log_success "Supervisor 已重新加载，队列进程应已启动"
    else
        log_warning "supervisorctl reread/update 未完全成功，尝试 reload..."
        docker exec "$CONTAINER_NAME" supervisorctl reload 2>/dev/null || {
            log_warning "Supervisor 重载失败，请重启容器: docker restart $CONTAINER_NAME"
        }
    fi
    log_success "Crontab 与队列配置步骤完成"
}

# ===========================================
# 检查容器内目录是否存在并挂载正确
# ===========================================

check_container_directory() {
    local container_path=$1
    local host_path=$2
    local project_name=$3
    local max_retries=2
    local retry_count=0
    local target_container="$CONTAINER_NAME"
    
    if [ "$project_name" = "ECShopX_web-frontend" ]; then
        target_container="$WEB_CONTAINER_NAME"
    fi
    
    # 首先检查容器是否运行
    if ! docker ps --format '{{.Names}}' | grep -q "^${target_container}$" 2>/dev/null; then
        log_warning "容器 $target_container 未运行，无法检查目录挂载状态"
        log_info "目录挂载将在容器启动后自动生效"
        return 0  # 容器未运行时，假设挂载会在启动后生效
    fi
    
    while [ $retry_count -lt $max_retries ]; do
        # 检查目录是否存在
        if docker exec "$target_container" sh -c "test -d $container_path" 2>/dev/null; then
            # 检查 package.json 是否存在
            if docker exec "$target_container" sh -c "test -f $container_path/package.json" 2>/dev/null; then
                log_success "目录挂载检查通过: $target_container:$container_path"
                return 0  # 目录和文件都存在
            else
                if [ $retry_count -eq 0 ]; then
                    log_warning "容器 $target_container 内 $container_path/package.json 不存在，尝试重启容器以确保目录正确挂载..."
                    if ! restart_container_for_mount "$project_name"; then
                        return 1
                    fi
                    retry_count=$((retry_count + 1))
                    continue
                else
                    log_error "容器 $target_container 内 $container_path/package.json 仍然不存在"
                    log_info "请检查主机目录 $host_path 是否存在且包含 package.json"
                    return 1
                fi
            fi
        else
            if [ $retry_count -eq 0 ]; then
                log_warning "容器 $target_container 内 $container_path 目录不存在，尝试重启容器以确保目录正确挂载..."
                if ! restart_container_for_mount "$project_name"; then
                    return 1
                fi
                retry_count=$((retry_count + 1))
                continue
            else
                log_error "容器 $target_container 内 $container_path 目录仍然不存在"
                log_info "请检查 docker-compose.dev.yml 中的卷挂载配置是否正确"
                log_info "主机目录路径: $host_path"
                return 1
            fi
        fi
    done
    
    return 1
}

# ===========================================
# 重启容器以确保目录挂载
# ===========================================

restart_container_for_mount() {
    local project_name=$1
    local target_container="$CONTAINER_NAME"
    
    if [ "$project_name" = "ECShopX_web-frontend" ]; then
        target_container="$WEB_CONTAINER_NAME"
    fi
    
    log_warning "检测到 $project_name 目录未正确挂载，正在重启容器..."
    
    if docker ps --format '{{.Names}}' | grep -q "^${target_container}$" 2>/dev/null; then
        log_info "重启容器 $target_container..."
        $DOCKER_COMPOSE_CMD -f "$DOCKER_COMPOSE_FILE" restart "$target_container" || {
            log_error "容器重启失败"
            return 1
        }
        
        log_info "等待容器启动..."
        sleep 5
        
        if [ "$target_container" = "$CONTAINER_NAME" ]; then
            # 等待主服务就绪
            wait_for_services
        fi
        
        log_success "容器重启完成"
        return 0
    else
        log_warning "容器未运行，无需重启"
        return 0
    fi
}

# ===========================================
# 配置前端项目的 .env 文件
# ===========================================

configure_frontend_env() {
    local project_dir=$1
    local project_name=$2
    local api_base_url=${3:-"http://localhost:8080/api/"}
    local app_id=${4:-""}
    local default_lang=${5:-""}
    local pc_url=${6:-"$PC_URL"}
    local qiankun_entry_url=${7:-"$QIANKUN_ENTRY_URL"}
    local target_container="$CONTAINER_NAME"
    FRONTEND_ENV_CHANGED=false
    
    # 检查主机目录是否存在
    if [ ! -d "$project_dir" ]; then
        log_warning "$project_name 目录不存在，跳过配置"
        return 0
    fi
    
    # 检查容器内目录是否存在
    local container_path=""
    if [ "$project_name" = "ECShopX_admin-frontend" ]; then
        container_path="/data/httpd/ECShopX_admin-frontend"
    elif [ "$project_name" = "ECShopX_mobile-frontend" ]; then
        container_path="/data/httpd/ECShopX_mobile-frontend"
    elif [ "$project_name" = "ECShopX_web-frontend" ]; then
        container_path="/data/httpd/ECShopX_web-frontend"
        target_container="$WEB_CONTAINER_NAME"
    else
        log_warning "未知的项目名称: $project_name"
        return 0
    fi
    
    # 如果容器未运行，直接配置主机目录
    if ! docker ps --format '{{.Names}}' | grep -q "^${target_container}$" 2>/dev/null; then
        log_info "容器 $target_container 未运行，配置主机目录的 .env 文件..."
        local env_file="$project_dir/.env"
        local env_before=""
        local env_after=""
        env_before=$(cat "$env_file" 2>/dev/null || true)
        
        # 如果 .env 不存在，尝试从 .env.example 复制
        if [ ! -f "$env_file" ]; then
            if [ -f "$project_dir/.env.example" ]; then
                log_info "从 .env.example 创建 .env 文件..."
                cp "$project_dir/.env.example" "$env_file"
            else
                log_info "创建新的 .env 文件..."
                touch "$env_file"
            fi
        fi
        
        # 配置 ECShopX_admin-frontend
        if [ "$project_name" = "ECShopX_admin-frontend" ]; then
            # 使用 sed 修改或添加 VUE_APP_BASE_API
            if grep -q "^VUE_APP_BASE_API=" "$env_file" 2>/dev/null; then
                # macOS 和 Linux 兼容的 sed 命令
                if [[ "$OSTYPE" == "darwin"* ]]; then
                    sed -i '' "s|^VUE_APP_BASE_API=.*|VUE_APP_BASE_API=$api_base_url|" "$env_file"
                else
                    sed -i "s|^VUE_APP_BASE_API=.*|VUE_APP_BASE_API=$api_base_url|" "$env_file"
                fi
            else
                echo "VUE_APP_BASE_API=$api_base_url" >> "$env_file"
            fi
            log_success "已配置 VUE_APP_BASE_API=$api_base_url"
            
            # 配置 VUE_APP_DEFAULT_LANG（如果提供）
            if [ -n "$default_lang" ]; then
                if grep -q "^VUE_APP_DEFAULT_LANG=" "$env_file" 2>/dev/null; then
                    # macOS 和 Linux 兼容的 sed 命令
                    if [[ "$OSTYPE" == "darwin"* ]]; then
                        sed -i '' "s|^VUE_APP_DEFAULT_LANG=.*|VUE_APP_DEFAULT_LANG=$default_lang|" "$env_file"
                    else
                        sed -i "s|^VUE_APP_DEFAULT_LANG=.*|VUE_APP_DEFAULT_LANG=$default_lang|" "$env_file"
                    fi
                else
                    echo "VUE_APP_DEFAULT_LANG=$default_lang" >> "$env_file"
                fi
                log_success "已配置 VUE_APP_DEFAULT_LANG=$default_lang"
            fi
            
            # 配置 VUE_APP_QIANKUN_ENTRY
            if grep -q "^VUE_APP_QIANKUN_ENTRY=" "$env_file" 2>/dev/null; then
                # macOS 和 Linux 兼容的 sed 命令
                if [[ "$OSTYPE" == "darwin"* ]]; then
                    sed -i '' "s|^VUE_APP_QIANKUN_ENTRY=.*|VUE_APP_QIANKUN_ENTRY=$qiankun_entry_url|" "$env_file"
                else
                    sed -i "s|^VUE_APP_QIANKUN_ENTRY=.*|VUE_APP_QIANKUN_ENTRY=$qiankun_entry_url|" "$env_file"
                fi
            else
                echo "VUE_APP_QIANKUN_ENTRY=$qiankun_entry_url" >> "$env_file"
            fi
            log_success "已配置 VUE_APP_QIANKUN_ENTRY=$qiankun_entry_url"
            
            # 配置 VUE_APP_WEBSITE（PC前端访问地址）
            if [ "$INSTALLED_PC" = true ]; then
                if grep -q "^VUE_APP_WEBSITE=" "$env_file" 2>/dev/null; then
                    # macOS 和 Linux 兼容的 sed 命令
                    if [[ "$OSTYPE" == "darwin"* ]]; then
                        sed -i '' "s|^VUE_APP_WEBSITE=.*|VUE_APP_WEBSITE=$pc_url|" "$env_file"
                    else
                        sed -i "s|^VUE_APP_WEBSITE=.*|VUE_APP_WEBSITE=$pc_url|" "$env_file"
                    fi
                else
                    echo "VUE_APP_WEBSITE=$pc_url" >> "$env_file"
                fi
                log_success "已配置 VUE_APP_WEBSITE=$pc_url"
            fi
        fi
        
        # 配置 ECShopX_mobile-frontend
        if [ "$project_name" = "ECShopX_mobile-frontend" ]; then
            # 使用 sed 修改或添加 APP_BASE_URL
            if grep -q "^APP_BASE_URL=" "$env_file" 2>/dev/null; then
                # macOS 和 Linux 兼容的 sed 命令
                if [[ "$OSTYPE" == "darwin"* ]]; then
                    sed -i '' "s|^APP_BASE_URL=.*|APP_BASE_URL=$api_base_url|" "$env_file"
                else
                    sed -i "s|^APP_BASE_URL=.*|APP_BASE_URL=$api_base_url|" "$env_file"
                fi
            else
                echo "APP_BASE_URL=$api_base_url" >> "$env_file"
            fi
            log_success "已配置 APP_BASE_URL=$api_base_url"
            
            # 配置 APP_PLATFORM（如果提供）
            if [ -n "$app_id" ]; then
                if grep -q "^APP_PLATFORM=" "$env_file" 2>/dev/null; then
                    # macOS 和 Linux 兼容的 sed 命令
                    if [[ "$OSTYPE" == "darwin"* ]]; then
                        sed -i '' "s|^APP_PLATFORM=.*|APP_PLATFORM=$app_id|" "$env_file"
                    else
                        sed -i "s|^APP_PLATFORM=.*|APP_PLATFORM=$app_id|" "$env_file"
                    fi
                else
                    echo "APP_PLATFORM=$app_id" >> "$env_file"
                fi
                log_success "已配置 APP_PLATFORM=$app_id"
            fi
            
            # 配置 APP_I18N_ORIGIN_LANG（如果提供）
            if [ -n "$default_lang" ]; then
                if grep -q "^APP_I18N_ORIGIN_LANG=" "$env_file" 2>/dev/null; then
                    # macOS 和 Linux 兼容的 sed 命令
                    if [[ "$OSTYPE" == "darwin"* ]]; then
                        sed -i '' "s|^APP_I18N_ORIGIN_LANG=.*|APP_I18N_ORIGIN_LANG=$default_lang|" "$env_file"
                    else
                        sed -i "s|^APP_I18N_ORIGIN_LANG=.*|APP_I18N_ORIGIN_LANG=$default_lang|" "$env_file"
                    fi
                else
                    echo "APP_I18N_ORIGIN_LANG=$default_lang" >> "$env_file"
                fi
                log_success "已配置 APP_I18N_ORIGIN_LANG=$default_lang"
            fi
        fi
        
        # 配置 ECShopX_web-frontend
        if [ "$project_name" = "ECShopX_web-frontend" ]; then
            local pc_default_country_code=""

            # 使用 sed 修改或添加 NUXT_PUBLIC_API_BASE
            if grep -q "^NUXT_PUBLIC_API_BASE=" "$env_file" 2>/dev/null; then
                # macOS 和 Linux 兼容的 sed 命令
                if [[ "$OSTYPE" == "darwin"* ]]; then
                    sed -i '' "s|^NUXT_PUBLIC_API_BASE=.*|NUXT_PUBLIC_API_BASE=$api_base_url|" "$env_file"
                else
                    sed -i "s|^NUXT_PUBLIC_API_BASE=.*|NUXT_PUBLIC_API_BASE=$api_base_url|" "$env_file"
                fi
            else
                echo "NUXT_PUBLIC_API_BASE=$api_base_url" >> "$env_file"
            fi
            log_success "已配置 NUXT_PUBLIC_API_BASE=$api_base_url"

            # 配置 PC 装修预览允许接收的后台 origin
            if grep -q "^NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS=" "$env_file" 2>/dev/null; then
                if [[ "$OSTYPE" == "darwin"* ]]; then
                    sed -i '' "s|^NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS=.*|NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS=$NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS|" "$env_file"
                else
                    sed -i "s|^NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS=.*|NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS=$NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS|" "$env_file"
                fi
            else
                echo "NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS=$NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS" >> "$env_file"
            fi
            log_success "已配置 NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS=$NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS"
            
            # 配置 NUXT_PUBLIC_COMPANY_ID（默认值为1）：已有值可能对应已安装店铺，重跑脚本时不覆盖
            if grep -q "^NUXT_PUBLIC_COMPANY_ID=" "$env_file" 2>/dev/null; then
                log_info "NUXT_PUBLIC_COMPANY_ID 已存在，保留当前值"
            else
                echo "NUXT_PUBLIC_COMPANY_ID=1" >> "$env_file"
                log_success "NUXT_PUBLIC_COMPANY_ID 不存在，已配置默认值 1"
            fi
            
            # 配置 NUXT_PUBLIC_DEFAULT_COUNTRY_CODE（如果提供）
            if [ -n "$default_lang" ]; then
                pc_default_country_code=$(pc_api_country_code_for_lang "$default_lang")
                if grep -q "^NUXT_PUBLIC_DEFAULT_COUNTRY_CODE=" "$env_file" 2>/dev/null; then
                    # macOS 和 Linux 兼容的 sed 命令
                    if [[ "$OSTYPE" == "darwin"* ]]; then
                        sed -i '' "s|^NUXT_PUBLIC_DEFAULT_COUNTRY_CODE=.*|NUXT_PUBLIC_DEFAULT_COUNTRY_CODE=$pc_default_country_code|" "$env_file"
                    else
                        sed -i "s|^NUXT_PUBLIC_DEFAULT_COUNTRY_CODE=.*|NUXT_PUBLIC_DEFAULT_COUNTRY_CODE=$pc_default_country_code|" "$env_file"
                    fi
                else
                    echo "NUXT_PUBLIC_DEFAULT_COUNTRY_CODE=$pc_default_country_code" >> "$env_file"
                fi
                log_success "已配置 NUXT_PUBLIC_DEFAULT_COUNTRY_CODE=$pc_default_country_code"
            fi
        fi

        env_after=$(cat "$env_file" 2>/dev/null || true)
        if [ "$env_before" != "$env_after" ]; then
            FRONTEND_ENV_CHANGED=true
            log_info "$project_name .env 已更新"
        fi
    else
        # 容器运行中，配置容器内的 .env 文件
        # 检查容器内目录是否存在
        if ! docker exec "$target_container" sh -c "test -d $container_path" 2>/dev/null; then
            log_warning "容器 $target_container 内 $container_path 目录不存在，跳过配置"
            return 0
        fi

        local env_before=""
        local env_after=""
        env_before=$(docker exec "$target_container" sh -c "cd $container_path && [ -f .env ] && cat .env" 2>/dev/null || true)
        
        # 在容器内配置 .env 文件
        docker exec "$target_container" sh -c "
            cd $container_path && \
            if [ ! -f .env ]; then
                if [ -f .env.example ]; then
                    cp .env.example .env
                else
                    touch .env
                fi
            fi
        " 2>/dev/null || true
        
        # 配置 ECShopX_admin-frontend
        if [ "$project_name" = "ECShopX_admin-frontend" ]; then
            docker exec "$target_container" sh -c "
                cd $container_path && \
                if grep -q '^VUE_APP_BASE_API=' .env 2>/dev/null; then
                    sed -i 's|^VUE_APP_BASE_API=.*|VUE_APP_BASE_API=$api_base_url|' .env
                else
                    echo 'VUE_APP_BASE_API=$api_base_url' >> .env
                fi
            " 2>/dev/null || true
            log_success "已配置容器内 VUE_APP_BASE_API=$api_base_url"
            
            # 配置 VUE_APP_DEFAULT_LANG（如果提供）
            if [ -n "$default_lang" ]; then
                docker exec "$target_container" sh -c "
                    cd $container_path && \
                    if grep -q '^VUE_APP_DEFAULT_LANG=' .env 2>/dev/null; then
                        sed -i 's|^VUE_APP_DEFAULT_LANG=.*|VUE_APP_DEFAULT_LANG=$default_lang|' .env
                    else
                        echo 'VUE_APP_DEFAULT_LANG=$default_lang' >> .env
                    fi
                " 2>/dev/null || true
                log_success "已配置容器内 VUE_APP_DEFAULT_LANG=$default_lang"
            fi
            
            # 配置 VUE_APP_QIANKUN_ENTRY
            docker exec "$target_container" sh -c "
                cd $container_path && \
                if grep -q '^VUE_APP_QIANKUN_ENTRY=' .env 2>/dev/null; then
                    sed -i 's|^VUE_APP_QIANKUN_ENTRY=.*|VUE_APP_QIANKUN_ENTRY=$qiankun_entry_url|' .env
                else
                    echo 'VUE_APP_QIANKUN_ENTRY=$qiankun_entry_url' >> .env
                fi
            " 2>/dev/null || true
            log_success "已配置容器内 VUE_APP_QIANKUN_ENTRY=$qiankun_entry_url"
            
            # 配置 VUE_APP_WEBSITE（PC前端访问地址）
            if [ "$INSTALLED_PC" = true ]; then
                docker exec "$target_container" sh -c "
                    cd $container_path && \
                    if grep -q '^VUE_APP_WEBSITE=' .env 2>/dev/null; then
                        sed -i 's|^VUE_APP_WEBSITE=.*|VUE_APP_WEBSITE=$pc_url|' .env
                    else
                        echo 'VUE_APP_WEBSITE=$pc_url' >> .env
                    fi
                " 2>/dev/null || true
                log_success "已配置容器内 VUE_APP_WEBSITE=$pc_url"
            fi
        fi
        
        # 配置 ECShopX_mobile-frontend
        if [ "$project_name" = "ECShopX_mobile-frontend" ]; then
            docker exec "$target_container" sh -c "
                cd $container_path && \
                if grep -q '^APP_BASE_URL=' .env 2>/dev/null; then
                    sed -i 's|^APP_BASE_URL=.*|APP_BASE_URL=$api_base_url|' .env
                else
                    echo 'APP_BASE_URL=$api_base_url' >> .env
                fi
            " 2>/dev/null || true
            log_success "已配置容器内 APP_BASE_URL=$api_base_url"
            
            # 配置 APP_PLATFORM（如果提供）
            if [ -n "$app_id" ]; then
                docker exec "$target_container" sh -c "
                    cd $container_path && \
                    if grep -q '^APP_PLATFORM=' .env 2>/dev/null; then
                        sed -i 's|^APP_PLATFORM=.*|APP_PLATFORM=$app_id|' .env
                    else
                        echo 'APP_PLATFORM=$app_id' >> .env
                    fi
                " 2>/dev/null || true
                log_success "已配置容器内 APP_PLATFORM=$app_id"
            fi
            
            # 配置 APP_I18N_ORIGIN_LANG（如果提供）
            if [ -n "$default_lang" ]; then
                docker exec "$target_container" sh -c "
                    cd $container_path && \
                    if grep -q '^APP_I18N_ORIGIN_LANG=' .env 2>/dev/null; then
                        sed -i 's|^APP_I18N_ORIGIN_LANG=.*|APP_I18N_ORIGIN_LANG=$default_lang|' .env
                    else
                        echo 'APP_I18N_ORIGIN_LANG=$default_lang' >> .env
                    fi
                " 2>/dev/null || true
                log_success "已配置容器内 APP_I18N_ORIGIN_LANG=$default_lang"
            fi
        fi
        
        # 配置 ECShopX_web-frontend
        if [ "$project_name" = "ECShopX_web-frontend" ]; then
            local pc_default_country_code=""

            docker exec "$target_container" sh -c "
                cd $container_path && \
                if grep -q '^NUXT_PUBLIC_API_BASE=' .env 2>/dev/null; then
                    sed -i 's|^NUXT_PUBLIC_API_BASE=.*|NUXT_PUBLIC_API_BASE=$api_base_url|' .env
                else
                    echo 'NUXT_PUBLIC_API_BASE=$api_base_url' >> .env
                fi
            " 2>/dev/null || true
            log_success "已配置容器内 NUXT_PUBLIC_API_BASE=$api_base_url"

            docker exec "$target_container" sh -c "
                cd $container_path && \
                if grep -q '^NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS=' .env 2>/dev/null; then
                    sed -i 's|^NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS=.*|NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS=$NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS|' .env
                else
                    echo 'NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS=$NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS' >> .env
                fi
            " 2>/dev/null || true
            log_success "已配置容器内 NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS=$NUXT_PUBLIC_DECORATION_ADMIN_ORIGINS"
            
            # 配置 NUXT_PUBLIC_COMPANY_ID（默认值为1）：已有值可能对应已安装店铺，重跑脚本时不覆盖
            docker exec "$target_container" sh -c "
                cd $container_path && \
                if grep -q '^NUXT_PUBLIC_COMPANY_ID=' .env 2>/dev/null; then
                    true
                else
                    echo 'NUXT_PUBLIC_COMPANY_ID=1' >> .env
                fi
            " 2>/dev/null || true
            if docker exec "$target_container" sh -c "cd $container_path && grep -q '^NUXT_PUBLIC_COMPANY_ID=' .env" 2>/dev/null; then
                log_info "容器内 NUXT_PUBLIC_COMPANY_ID 已存在或已配置默认值"
            else
                log_warning "容器内 NUXT_PUBLIC_COMPANY_ID 配置失败"
            fi
            
            # 配置 NUXT_PUBLIC_DEFAULT_COUNTRY_CODE（如果提供）
            if [ -n "$default_lang" ]; then
                pc_default_country_code=$(pc_api_country_code_for_lang "$default_lang")
                docker exec "$target_container" sh -c "
                    cd $container_path && \
                    if grep -q '^NUXT_PUBLIC_DEFAULT_COUNTRY_CODE=' .env 2>/dev/null; then
                        sed -i 's|^NUXT_PUBLIC_DEFAULT_COUNTRY_CODE=.*|NUXT_PUBLIC_DEFAULT_COUNTRY_CODE=$pc_default_country_code|' .env
                    else
                        echo 'NUXT_PUBLIC_DEFAULT_COUNTRY_CODE=$pc_default_country_code' >> .env
                    fi
                " 2>/dev/null || true
                log_success "已配置容器内 NUXT_PUBLIC_DEFAULT_COUNTRY_CODE=$pc_default_country_code"
            fi
        fi

        env_after=$(docker exec "$target_container" sh -c "cd $container_path && [ -f .env ] && cat .env" 2>/dev/null || true)
        if [ "$env_before" != "$env_after" ]; then
            FRONTEND_ENV_CHANGED=true
            log_info "$project_name .env 已更新"
        fi
    fi
}

# ===========================================
# 编译 ECShopX_admin-frontend
# ===========================================

build_admin() {
    if [ "$SKIP_ADMIN" = true ]; then
        log_info "跳过 ECShopX_admin-frontend 编译（--skip-admin）"
        return 0
    fi
    
    ADMIN_DIR="$PARENT_DIR/ECShopX_admin-frontend"
    
    if [ ! -d "$ADMIN_DIR" ]; then
        log_warning "ECShopX_admin-frontend 目录不存在，跳过编译"
        return 0
    fi
    
    log_info "=========================================="
    log_info "开始编译 ECShopX_admin-frontend（管理后台）..."
    log_info "=========================================="
    
    if [ ! -f "$ADMIN_DIR/package.json" ]; then
        log_error "ECShopX_admin-frontend/package.json 不存在"
        return 1
    fi

    # 检查容器内目录是否存在并确保正确挂载
    log_info "检查容器内目录挂载状态..."
    if ! check_container_directory "/data/httpd/ECShopX_admin-frontend" "$ADMIN_DIR" "ECShopX_admin-frontend"; then
        return 1
    fi

    load_existing_frontend_lang
    log_info "配置 ECShopX_admin-frontend 的 .env 文件..."
    configure_frontend_env "$ADMIN_DIR" "ECShopX_admin-frontend" "$API_BASE_URL" "" "$SELECTED_LANG" "$PC_URL" "$QIANKUN_ENTRY_URL"

    # 检查是否已有编译产物；前端 .env 变化时必须重新编译
    if [ -d "$ADMIN_DIR/dist" ] && [ -f "$ADMIN_DIR/dist/index.html" ]; then
        if [ "$FRONTEND_ENV_CHANGED" = true ]; then
            log_info "检测到已有编译产物，但前端 .env 已变化，需要重新编译"
        else
            log_info "检测到已有编译产物，跳过编译"
            log_info "如需重新编译，请删除 ECShopX_admin-frontend/dist 目录"
            return 0
        fi
    fi

    ensure_selected_lang
    log_info "配置 ECShopX_admin-frontend 的 .env 文件..."
    configure_frontend_env "$ADMIN_DIR" "ECShopX_admin-frontend" "$API_BASE_URL" "" "$SELECTED_LANG" "$PC_URL" "$QIANKUN_ENTRY_URL"
    
    log_info "安装 npm 依赖..."
    # 使用绝对路径并先验证目录存在
    docker exec "$CONTAINER_NAME" sh -c "
        if [ ! -d /data/httpd/ECShopX_admin-frontend ]; then
            echo '错误: 目录不存在'
            exit 1
        fi
        cd /data/httpd/ECShopX_admin-frontend || exit 1
        npm install --legacy-peer-deps
    " > /tmp/npm_admin_output.log 2>&1 &
    local npm_pid=$!
    
    # 显示进度动画
    local spinstr='|/-\'
    while kill -0 $npm_pid 2>/dev/null; do
        local temp=${spinstr#?}
        printf "\r${CYAN}[INFO]${NC} 安装 ECShopX_admin-frontend npm 依赖中 ${spinstr:0:1}"
        spinstr=$temp${spinstr%"$temp"}
        sleep 0.2
    done
    wait $npm_pid
    local npm_exit=$?
    
    if [ $npm_exit -ne 0 ]; then
        printf "\r${RED}[ERROR]${NC} ECShopX_admin-frontend npm install 失败"
        printf "%50s" ""
        echo ""
        cat /tmp/npm_admin_output.log 2>/dev/null | tail -20
        log_info "请检查容器内目录状态: docker exec $CONTAINER_NAME ls -la /data/httpd/ECShopX_admin-frontend"
        return 1
    else
        printf "\r${GREEN}[SUCCESS]${NC} ECShopX_admin-frontend npm 依赖安装完成"
        printf "%50s" ""
        echo ""
    fi
    
    # 从 ECShopX 配置文件读取业务模式
    read_product_model_from_env

    if [ "$SELECTED_PLATFORM" = "standard" ]; then
        BUILD_CMD="build:b2c"
        BUILD_MODE_NAME="B2C"
    else
        BUILD_CMD="build:bbc"
        BUILD_MODE_NAME="BBC (B2B2C)"
    fi
    log_info "使用业务模式: $BUILD_MODE_NAME"
    echo ""

    log_info "开始前端环境编译"
    log_info "编译时长依赖本地硬件性能，耗时较长请耐心等待"
    log_info "举列：Mac M2芯片预计整体耗时15分钟"
    echo ""

    log_info "执行编译命令: npm run $BUILD_CMD"
    docker exec "$CONTAINER_NAME" sh -c "cd /data/httpd/ECShopX_admin-frontend && npm run $BUILD_CMD" > /tmp/build_admin_output.log 2>&1 &
    local build_pid=$!
    
    # 显示进度动画
    local spinstr='|/-\'
    while kill -0 $build_pid 2>/dev/null; do
        local temp=${spinstr#?}
        printf "\r${CYAN}[INFO]${NC} 编译 ECShopX_admin-frontend 中 ${spinstr:0:1}"
        spinstr=$temp${spinstr%"$temp"}
        sleep 0.2
    done
    wait $build_pid
    local build_exit=$?
    
    if [ $build_exit -ne 0 ]; then
        printf "\r${RED}[ERROR]${NC} ECShopX_admin-frontend 编译失败"
        printf "%50s" ""
        echo ""
        cat /tmp/build_admin_output.log 2>/dev/null | tail -20
        return 1
    else
        printf "\r${GREEN}[SUCCESS]${NC} ECShopX_admin-frontend 编译完成"
        printf "%50s" ""
        echo ""
    fi
    
    if docker exec "$CONTAINER_NAME" sh -c "[ -f /data/httpd/ECShopX_admin-frontend/dist/index.html ]"; then
        log_success "ECShopX_admin-frontend 编译成功"
        return 0
    else
        log_error "ECShopX_admin-frontend 编译输出不完整"
        return 1
    fi
}

# ===========================================
# 编译 ECShopX_web-frontend
# ===========================================

build_pc() {
    if [ "$SKIP_PC" = true ]; then
        log_info "跳过 ECShopX_web-frontend 编译（--skip-pc）"
        return 0
    fi
    
    PC_DIR="$PARENT_DIR/ECShopX_web-frontend"
    
    if [ ! -d "$PC_DIR" ]; then
        log_warning "ECShopX_web-frontend 目录不存在，跳过编译"
        return 0
    fi
    
    log_info "=========================================="
    log_info "开始编译 ECShopX_web-frontend（PC前端）..."
    log_info "=========================================="
    
    if [ ! -f "$PC_DIR/package.json" ]; then
        log_error "ECShopX_web-frontend/package.json 不存在"
        return 1
    fi
    
    if ! ensure_web_container_running; then
        return 1
    fi

    # 检查容器内目录是否存在并确保正确挂载
    log_info "检查容器内目录挂载状态..."
    if ! check_container_directory "/data/httpd/ECShopX_web-frontend" "$PC_DIR" "ECShopX_web-frontend"; then
        return 1
    fi

    load_existing_frontend_lang
    log_info "配置 ECShopX_web-frontend 的 .env 文件..."
    configure_frontend_env "$PC_DIR" "ECShopX_web-frontend" "$PC_API_URL" "" "$SELECTED_LANG"

    # 检查是否已有可启动的 Nuxt 生产产物（.nuxt 可能由 pnpm install/postinstall 生成，不能代表已编译完成）
    if docker exec "$WEB_CONTAINER_NAME" sh -c "[ -f /data/httpd/ECShopX_web-frontend/.output/server/index.mjs ]" 2>/dev/null || \
       [ -f "$PC_DIR/.output/server/index.mjs" ]; then
        if [ "$FRONTEND_ENV_CHANGED" = true ]; then
            log_info "检测到已有 Nuxt 生产编译产物，但前端 .env 已变化，需要重新编译"
            need_build=true
        else
            log_info "检测到已有 Nuxt 生产编译产物，跳过编译"
            log_info "如需重新编译，请删除 ECShopX_web-frontend/.output 目录"
            # 即使跳过编译，也需要启动Nuxt服务
            need_build=false
        fi
    else
        need_build=true
    fi
    
    # 如果跳过编译，直接启动Nuxt服务，不需要确认语言
    if [ "$need_build" = false ]; then
        # 启动 Nuxt 服务
        log_info "启动 Nuxt 服务（监听3000端口）..."
        
        # 检查是否已有Nuxt进程在运行
        if docker exec "$WEB_CONTAINER_NAME" sh -c "pgrep -f 'nuxt.*3000|pnpm.*preview|pnpm.*dev|node .*\\.output/server/index\\.mjs' > /dev/null" 2>/dev/null; then
            log_info "Nuxt 服务已在运行"
        else
            # 启动已编译的 Nuxt 服务
            docker exec -d "$WEB_CONTAINER_NAME" sh -c "
                cd /data/httpd/ECShopX_web-frontend && \
                NITRO_HOST=0.0.0.0 NITRO_PORT=3000 HOST=0.0.0.0 PORT=3000 nohup node .output/server/index.mjs > /var/log/nuxt.log 2>&1 &
            " 2>/dev/null || true
            
            # 等待Nuxt服务启动
            sleep 3
        fi
        
        # 重新加载nginx配置
        docker exec "$CONTAINER_NAME" sh -c "nginx -s reload" 2>/dev/null || true
        return 0
    fi
    
    # 只有在需要编译时才确认默认语言和配置.env
    if [ "$need_build" = true ]; then
        ensure_selected_lang
        log_info "配置 ECShopX_web-frontend 的 .env 文件..."
        configure_frontend_env "$PC_DIR" "ECShopX_web-frontend" "$PC_API_URL" "" "$SELECTED_LANG"
        
        log_info "启用 pnpm 并安装依赖..."
        # 使用绝对路径并先验证目录存在
        docker exec "$WEB_CONTAINER_NAME" sh -c "
            if [ ! -d /data/httpd/ECShopX_web-frontend ]; then
                echo '错误: 目录不存在'
                exit 1
            fi
            cd /data/httpd/ECShopX_web-frontend || exit 1
            corepack enable
            PNPM_HOME=/tmp/pnpm pnpm install --store-dir /tmp/pnpm-store
        " > /tmp/npm_pc_output.log 2>&1 &
        local npm_pid=$!
        
        # 显示进度动画
        local spinstr='|/-\'
        while kill -0 $npm_pid 2>/dev/null; do
            local temp=${spinstr#?}
            printf "\r${CYAN}[INFO]${NC} 安装 ECShopX_web-frontend pnpm 依赖中 ${spinstr:0:1}"
            spinstr=$temp${spinstr%"$temp"}
            sleep 0.2
        done
        wait $npm_pid
        local npm_exit=$?
        
        if [ $npm_exit -ne 0 ]; then
            printf "\r${RED}[ERROR]${NC} ECShopX_web-frontend pnpm install 失败"
            printf "%50s" ""
            echo ""
            cat /tmp/npm_pc_output.log 2>/dev/null | tail -20
            log_info "请检查容器内目录状态: docker exec $WEB_CONTAINER_NAME ls -la /data/httpd/ECShopX_web-frontend"
            return 1
        else
            printf "\r${GREEN}[SUCCESS]${NC} ECShopX_web-frontend pnpm 依赖安装完成"
            printf "%50s" ""
            echo ""
        fi
        
        log_info "执行编译（pnpm build）..."
        docker exec "$WEB_CONTAINER_NAME" sh -c "cd /data/httpd/ECShopX_web-frontend && PNPM_HOME=/tmp/pnpm pnpm build" > /tmp/build_pc_output.log 2>&1 &
        local build_pid=$!
        
        # 显示进度动画
        local spinstr='|/-\'
        while kill -0 $build_pid 2>/dev/null; do
            local temp=${spinstr#?}
            printf "\r${CYAN}[INFO]${NC} 编译 ECShopX_web-frontend 中 ${spinstr:0:1}"
            spinstr=$temp${spinstr%"$temp"}
            sleep 0.2
        done
        wait $build_pid
        local build_exit=$?
        
        if [ $build_exit -ne 0 ]; then
            printf "\r${RED}[ERROR]${NC} ECShopX_web-frontend 编译失败"
            printf "%50s" ""
            echo ""
            cat /tmp/build_pc_output.log 2>/dev/null | tail -20
            return 1
        else
            printf "\r${GREEN}[SUCCESS]${NC} ECShopX_web-frontend 编译完成"
            printf "%50s" ""
            echo ""
        fi
        # 验证编译产物
        if ! docker exec "$WEB_CONTAINER_NAME" sh -c "[ -f /data/httpd/ECShopX_web-frontend/.output/server/index.mjs ]" 2>/dev/null; then
            log_error "ECShopX_web-frontend 编译输出不完整"
            return 1
        fi
    fi
    
    # 检查可启动的 Nuxt 生产编译产物
    if docker exec "$WEB_CONTAINER_NAME" sh -c "[ -f /data/httpd/ECShopX_web-frontend/.output/server/index.mjs ]" 2>/dev/null; then
        log_success "ECShopX_web-frontend 编译成功"
        
        # 启动 Nuxt 服务
        log_info "启动 Nuxt 服务（监听3000端口）..."
        
        # 检查是否已有Nuxt进程在运行
        if docker exec "$WEB_CONTAINER_NAME" sh -c "pgrep -f 'nuxt.*3000|pnpm.*preview|pnpm.*dev|node .*\\.output/server/index\\.mjs' > /dev/null" 2>/dev/null; then
            log_info "Nuxt 服务已在运行，重启服务..."
            docker exec "$WEB_CONTAINER_NAME" sh -c "pkill -f 'nuxt.*3000|pnpm.*preview|pnpm.*dev|node .*\\.output/server/index\\.mjs'" 2>/dev/null || true
            sleep 2
        fi
        
        # 在后台启动Nuxt服务
        log_info "使用生产模式启动（node .output/server/index.mjs）..."
        docker exec -d "$WEB_CONTAINER_NAME" sh -c "
            cd /data/httpd/ECShopX_web-frontend && \
            NITRO_HOST=0.0.0.0 NITRO_PORT=3000 HOST=0.0.0.0 PORT=3000 nohup node .output/server/index.mjs > /var/log/nuxt.log 2>&1 &
        " || {
            log_error "Nuxt 服务启动失败"
            log_info "请检查日志: docker exec $WEB_CONTAINER_NAME tail -f /var/log/nuxt.log"
            return 1
        }
        
        # 等待Nuxt服务启动
        log_info "等待 Nuxt 服务启动..."
        local nuxt_ready=false
        for i in {1..30}; do
            if docker exec "$WEB_CONTAINER_NAME" sh -c "wget -q --spider http://127.0.0.1:3000 >/dev/null 2>&1 || wget -S -O /dev/null http://127.0.0.1:3000 2>&1 | grep -q 'HTTP/'" 2>/dev/null; then
                nuxt_ready=true
                break
            fi
            sleep 2
            if [ $((i % 5)) -eq 0 ]; then
                printf "\r${CYAN}[INFO]${NC} 等待 Nuxt 服务启动... ($i/30)"
            fi
        done
        
        if [ "$nuxt_ready" = true ]; then
            printf "\r${GREEN}[SUCCESS]${NC} Nuxt 服务已启动并监听3000端口"
            printf "%50s" ""
            echo ""
            if [ "$INSTALLED_VSHOP" = true ]; then
                log_info "H5前端访问地址: $H5_URL"
            fi
            if [ "$INSTALLED_PC" = true ]; then
                log_info "PC前端访问地址: $PC_URL"
            fi
        else
            printf "\r${YELLOW}[WARNING]${NC} Nuxt 服务启动超时，但可能仍在启动中"
            printf "%50s" ""
            echo ""
            log_info "请检查日志: docker exec $WEB_CONTAINER_NAME tail -f /var/log/nuxt.log"
            if [ "$INSTALLED_VSHOP" = true ]; then
                log_info "H5前端访问地址: $H5_URL"
            fi
            if [ "$INSTALLED_PC" = true ]; then
                log_info "PC前端访问地址: $PC_URL"
            fi
        fi
        
        # 重新加载nginx配置以确保8082端口配置生效
        log_info "重新加载 Nginx 配置..."
        docker exec "$CONTAINER_NAME" sh -c "nginx -s reload" 2>/dev/null || {
            log_warning "Nginx 配置重载失败，可能需要重启容器"
        }
    else
        log_error "ECShopX_web-frontend 编译输出不完整"
        return 1
    fi
}

# ===========================================
# 编译 ECShopX_mobile-frontend
# ===========================================

build_vshop() {
    if [ "$SKIP_VSHOP" = true ]; then
        log_info "跳过 ECShopX_mobile-frontend 编译（--skip-vshop）"
        return 0
    fi
    
    VSHOP_DIR="$PARENT_DIR/ECShopX_mobile-frontend"
    
    if [ ! -d "$VSHOP_DIR" ]; then
        log_warning "ECShopX_mobile-frontend 目录不存在，跳过编译"
        return 0
    fi
    
    log_info "=========================================="
    log_info "开始编译 ECShopX_mobile-frontend（H5前端）..."
    log_info "=========================================="
    
    if [ ! -f "$VSHOP_DIR/package.json" ]; then
        log_error "ECShopX_mobile-frontend/package.json 不存在"
        return 1
    fi

    # 检查容器内目录是否存在并确保正确挂载
    log_info "检查容器内目录挂载状态..."
    if ! check_container_directory "/data/httpd/ECShopX_mobile-frontend" "$VSHOP_DIR" "ECShopX_mobile-frontend"; then
        return 1
    fi
    
    # 从 ECShopX 配置文件读取业务模式
    read_product_model_from_env

    if [ "$SELECTED_PLATFORM" = "standard" ]; then
        BUILD_MODE_NAME="B2C"
    else
        BUILD_MODE_NAME="BBC (B2B2C)"
    fi
    log_info "使用业务模式: $BUILD_MODE_NAME"

    ensure_selected_lang
    log_info "配置 ECShopX_mobile-frontend 的 .env 文件..."
    configure_frontend_env "$VSHOP_DIR" "ECShopX_mobile-frontend" "$MOBILE_API_URL" "$SELECTED_PLATFORM" "$SELECTED_LANG"

    # 检查是否已有编译产物；前端 .env 变化时必须重新编译
    if [ -d "$VSHOP_DIR/dist" ]; then
        if [ "$FRONTEND_ENV_CHANGED" = true ]; then
            log_info "检测到已有编译产物，但前端 .env 已变化，需要重新编译 H5"
        else
            log_info "检测到已有编译产物，跳过编译"
            log_info "如需重新编译，请删除 ECShopX_mobile-frontend/dist 目录"
            return 0
        fi
    fi

    if [ "$FRONTEND_ENV_CHANGED" = true ]; then
        log_info "复用已更新的 ECShopX_mobile-frontend .env 配置"
    else
        log_info "配置 ECShopX_mobile-frontend 的 .env 文件..."
        configure_frontend_env "$VSHOP_DIR" "ECShopX_mobile-frontend" "$MOBILE_API_URL" "$SELECTED_PLATFORM" "$SELECTED_LANG"
    fi
    
    log_info "安装 npm 依赖..."
    # 使用绝对路径并先验证目录存在
    docker exec "$CONTAINER_NAME" sh -c "
        if [ ! -d /data/httpd/ECShopX_mobile-frontend ]; then
            echo '错误: 目录不存在'
            exit 1
        fi
        cd /data/httpd/ECShopX_mobile-frontend || exit 1
        npm install --legacy-peer-deps
    " > /tmp/npm_vshop_output.log 2>&1 &
    local npm_pid=$!
    
    # 显示进度动画
    local spinstr='|/-\'
    while kill -0 $npm_pid 2>/dev/null; do
        local temp=${spinstr#?}
        printf "\r${CYAN}[INFO]${NC} 安装 ECShopX_mobile-frontend npm 依赖中 ${spinstr:0:1}"
        spinstr=$temp${spinstr%"$temp"}
        sleep 0.2
    done
    wait $npm_pid
    local npm_exit=$?
    
    if [ $npm_exit -ne 0 ]; then
        printf "\r${RED}[ERROR]${NC} ECShopX_mobile-frontend npm install 失败"
        printf "%50s" ""
        echo ""
        cat /tmp/npm_vshop_output.log 2>/dev/null | tail -20
        log_info "请检查容器内目录状态: docker exec $CONTAINER_NAME ls -la /data/httpd/ECShopX_mobile-frontend"
        return 1
    else
        printf "\r${GREEN}[SUCCESS]${NC} ECShopX_mobile-frontend npm 依赖安装完成"
        printf "%50s" ""
        echo ""
    fi
    
    log_info "执行编译（npm run build:h5）..."
    docker exec "$CONTAINER_NAME" sh -c "cd /data/httpd/ECShopX_mobile-frontend && npm run build:h5" > /tmp/build_vshop_output.log 2>&1 &
    local build_pid=$!
    
    # 显示进度动画
    local spinstr='|/-\'
    while kill -0 $build_pid 2>/dev/null; do
        local temp=${spinstr#?}
        printf "\r${CYAN}[INFO]${NC} 编译 ECShopX_mobile-frontend 中 ${spinstr:0:1}"
        spinstr=$temp${spinstr%"$temp"}
        sleep 0.2
    done
    wait $build_pid
    local build_exit=$?
    
    if [ $build_exit -ne 0 ]; then
        printf "\r${RED}[ERROR]${NC} ECShopX_mobile-frontend 编译失败"
        printf "%50s" ""
        echo ""
        cat /tmp/build_vshop_output.log 2>/dev/null | tail -20
        return 1
    else
        printf "\r${GREEN}[SUCCESS]${NC} ECShopX_mobile-frontend 编译完成"
        printf "%50s" ""
        echo ""
    fi
    
    if docker exec "$CONTAINER_NAME" sh -c "[ -d /data/httpd/ECShopX_mobile-frontend/dist ]"; then
        log_success "ECShopX_mobile-frontend 编译成功"
        return 0
    else
        log_error "ECShopX_mobile-frontend 编译输出目录不存在"
        return 1
    fi
}

# ===========================================
# 导入 Demo 数据
# ===========================================

import_demo_data() {
    # 如果全局变量为空，尝试从 ECShopX 配置文件读取
    read_product_model_from_env

    if [ -z "$SELECTED_PLATFORM" ]; then
        log_info "未选择业务模式，跳过 Demo 数据导入"
        return 0
    fi

    local demo_dir="/data/httpd/ECShopX/docker-dev/demo"

    # 检查数据库中 items 表是否已有数据
    local item_count
    item_count=$(docker exec "$CONTAINER_NAME" sh -c \
        "mysql -u$MYSQL_USER -p'$MYSQL_PASSWORD' -h127.0.0.1 $MYSQL_DATABASE -sN -e 'SELECT COUNT(*) FROM items;'" 2>/dev/null)

    if [ -n "$item_count" ] && [ "$item_count" -gt 0 ] 2>/dev/null; then
        log_info "数据库中已存在商品数据（items 表有 ${item_count} 条记录），跳过 Demo 数据导入"
        return 0
    fi

    local sql_file=""

    if [ "$SELECTED_PLATFORM" = "platform" ]; then
        # BBC 模式，只有一个 bbc.sql
        sql_file="bbc.sql"
        echo ""
        echo -n "是否导入 BBC Demo 数据？ [Y/n]: "
        read -r import_answer < /dev/tty
        if [ -n "$import_answer" ] && [ "$import_answer" != "Y" ] && [ "$import_answer" != "y" ] && [ "$import_answer" != "yes" ] && [ "$import_answer" != "YES" ]; then
            log_info "跳过 Demo 数据导入"
            return 0
        fi
    elif [ "$SELECTED_PLATFORM" = "standard" ]; then
        # B2C 模式，让用户选择行业
        echo ""
        log_info "请选择要导入的 B2C Demo 数据（行业）："
        log_info "  1) 美妆"
        log_info "  2) 运动"
        log_info "  3) 包袋"
        log_info "  0) 跳过，不导入"
        echo ""
        while true; do
            read -r -p "请输入选项 (0-3，默认: 0): " DEMO_CHOICE < /dev/tty
            DEMO_CHOICE=${DEMO_CHOICE:-0}
            case "$DEMO_CHOICE" in
                0)
                    log_info "跳过 Demo 数据导入"
                    return 0
                    ;;
                1)
                    sql_file="bc_beauty.sql"
                    break
                    ;;
                2)
                    sql_file="b2c_sports.sql"
                    break
                    ;;
                3)
                    sql_file="b2c_bags.sql"
                    break
                    ;;
                *)
                    log_error "无效的选项，请输入 0-3"
                    ;;
            esac
        done
    fi

    if [ -z "$sql_file" ]; then
        return 0
    fi

    # 检查容器内 SQL 文件是否存在
    if ! docker exec "$CONTAINER_NAME" sh -c "test -f '$demo_dir/$sql_file'" 2>/dev/null; then
        log_error "Demo 数据文件不存在: $demo_dir/$sql_file"
        return 1
    fi

    log_info "正在导入 Demo 数据: $sql_file ..."
    docker exec "$CONTAINER_NAME" sh -c \
        "mysql -u$MYSQL_USER -p'$MYSQL_PASSWORD' -h127.0.0.1 $MYSQL_DATABASE < '$demo_dir/$sql_file'" > /tmp/demo_import.log 2>&1 &
    local import_pid=$!

    local spinstr='|/-\'
    while kill -0 $import_pid 2>/dev/null; do
        local temp=${spinstr#?}
        printf "\r${CYAN}[INFO]${NC} 导入 Demo 数据中 ${spinstr:0:1}"
        spinstr=$temp${spinstr%"$temp"}
        sleep 0.2
    done
    wait $import_pid
    local import_exit=$?

    if [ $import_exit -ne 0 ]; then
        printf "\r${RED}[ERROR]${NC} Demo 数据导入失败"
        printf "%50s" ""
        echo ""
        cat /tmp/demo_import.log 2>/dev/null | tail -20
        return 1
    else
        printf "\r${GREEN}[SUCCESS]${NC} Demo 数据导入完成（$sql_file）"
        printf "%50s" ""
        echo ""
    fi
}


# ===========================================
# 开源安装统计上报（可选）
# ===========================================

# 计算 MD5（大写十六进制），入参为原始字符串
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

# 按文档：除 sign 外键名升序，拼接 k + serialize(v)，MD5(secret + 拼接 + secret) 大写
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

# 从 ECShopX 项目根目录 composer.json 读取 "version" 字段（用于开源安装统计）
get_ecshopx_version_from_composer() {
    local f="${PROJECT_ROOT}/composer.json"
    local v=""
    [ -r "$f" ] || {
        echo "0.0.0"
        return 0
    }
    if command -v php &>/dev/null; then
        v=$(php -r '$j=json_decode(file_get_contents($argv[1]),true);echo isset($j["version"])?(string)$j["version"]:"";' "$f" 2>/dev/null || true)
    fi
    if [ -z "$v" ]; then
        v=$(sed -n '1,40s/^[[:space:]]*"version"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' "$f" | head -1)
    fi
    [ -n "$v" ] || v="0.0.0"
    echo "$v"
}

get_open_source_instance_id() {
    local mac=""
    if [ "${OS:-}" = "macos" ]; then
        mac=$(ifconfig 2>/dev/null | awk '/ether/ {print tolower($2); exit}' || true)
    elif [ "${OS:-}" = "linux" ]; then
        local f
        for f in /sys/class/net/*/address; do
            [ ! -f "$f" ] && continue
            case "$f" in
                */lo/address) continue ;;
            esac
            mac=$(tr '[:upper:]' '[:lower:]' < "$f" || true)
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
    local instance_id
    instance_id=$(get_open_source_instance_id)
    local version
    version=$(get_ecshopx_version_from_composer)
    local timestamp
    timestamp=$(date +%s)
    local sign
    sign=$(open_source_stat_sign "$secret" "$product" "$instance_id" "$version" "$timestamp")
    [ -z "$sign" ] && return 0

    local base="${gateway%/}"
    local url="${base}/usercenter/open_source/stat/report"
    local inst_esc ver_esc
    inst_esc=$(escape_json_string "$instance_id")
    ver_esc=$(escape_json_string "$version")

    local body
    body=$(printf '{"product":"%s","instance_id":"%s","version":"%s","timestamp":%s,"sign":"%s"}' \
        "$product" "$inst_esc" "$ver_esc" "$timestamp" "$sign")

    curl -sS --max-time 15 -o /dev/null -X POST "$url" \
        -H 'Content-Type: application/json' \
        -d "$body" 2>/dev/null || true
    return 0
}

# ===========================================
# 显示完成信息
# ===========================================

show_success_info() {
    # 计算执行时间
    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))
    MINUTES=$((DURATION / 60))
    SECONDS=$((DURATION % 60))
    
    echo ""
    log_success "=========================================="
    log_success "恭喜你，安装成功"
    log_success "温馨提示：移动商城、PC商城前端显示空白，并非系统问题，需前往管理后台配置模板数据。"
    
    # 根据安装的项目显示温馨提示
    if [ "$INSTALLED_VSHOP" = true ] || [ "$INSTALLED_PC" = true ]; then
        local tip_msg="温馨提示："
        if [ "$INSTALLED_VSHOP" = true ] && [ "$INSTALLED_PC" = true ]; then
            tip_msg="${tip_msg}移动商城、PC商城前端显示空白，并非系统问题，需前往管理后台配置模板数据。"
        elif [ "$INSTALLED_VSHOP" = true ]; then
            tip_msg="${tip_msg}移动商城前端显示空白，并非系统问题，需前往管理后台配置模板数据。"
        elif [ "$INSTALLED_PC" = true ]; then
            tip_msg="${tip_msg}PC商城前端显示空白，并非系统问题，需前往管理后台配置模板数据。"
        fi
        log_success "$tip_msg"
    fi
    
    log_success "=========================================="
    echo ""
    log_info "服务信息："
    
    if [ "$INSTALLED_ADMIN" = true ]; then
        log_info "  管理后台: $ADMIN_URL"
    fi
    
    if [ "$INSTALLED_VSHOP" = true ]; then
        log_info "  H5前端:   $H5_URL"
    fi
    
    if [ "$INSTALLED_PC" = true ]; then
        log_info "  PC前端:   $PC_URL"
    fi
    
    log_info "  API 接口: $API_BASE_URL"
    log_info "  MySQL:    localhost:3306 (用户: $MYSQL_USER, 密码: $MYSQL_PASSWORD)"
    log_info "  Redis:    localhost:6379 (密码: $REDIS_PASSWORD)"
    echo ""
    
    # 检查微信小程序编译产物（仅在安装了移动商城时显示）
    if [ "$INSTALLED_VSHOP" = true ]; then
        VSHOP_DIR="$PARENT_DIR/ECShopX_mobile-frontend"
        if [ -d "$VSHOP_DIR/dist" ]; then
            log_info "微信小程序："
            log_info "  微信开发者工具打开目录: $VSHOP_DIR/dist"
            echo ""
        fi
    fi
    log_info "常用命令："
    log_info "  查看日志:     $DOCKER_COMPOSE_CMD -f $DOCKER_COMPOSE_FILE logs -f"
    log_info "  主服务状态:   docker exec $CONTAINER_NAME supervisorctl status"
    log_info "  进入主容器:   docker exec -it $CONTAINER_NAME sh"
    log_info "  进入Web容器:  docker exec -it $WEB_CONTAINER_NAME sh"
    log_info "  Web前端日志:  docker exec $WEB_CONTAINER_NAME tail -f /var/log/nuxt.log"
    log_info "  停止服务:     $DOCKER_COMPOSE_CMD -f $DOCKER_COMPOSE_FILE down"
    log_info "  重启服务:     $DOCKER_COMPOSE_CMD -f $DOCKER_COMPOSE_FILE restart"
    log_info "  重新构建:     $0 --rebuild"
    echo ""
    log_info "执行时间: ${MINUTES}分${SECONDS}秒"
    report_open_source_install_stat
    log_success "=========================================="
}

# ===========================================
# 主函数
# ===========================================

main() {
    # 解析命令行参数
    parse_args "$@"
    
    echo "=========================================="
    echo "  ECShopX 开发环境设置 (Docker)"
    echo "  支持: ECShopX / ECShopX_admin-frontend / ECShopX_mobile-frontend / ECShopX_web-frontend"
    echo "=========================================="
    echo ""
    
    detect_os
    check_docker
    configure_public_urls
    
    # 检查并克隆前端项目（在启动容器之前）
    check_and_clone_frontend
    
    # 检查容器状态
    if check_container_status; then
        run_docker
    fi
    
    # 配置 PHP 应用
    configure_env
    configure_application
    configure_cron_and_supervisor_queues
    
    # 编译前端项目
    build_pc || log_warning "PC商城（ECShopX_web-frontend）编译未完成"
    build_admin || log_warning "管理后台（ECShopX_admin-frontend）编译未完成"
    build_vshop || log_warning "移动商城（ECShopX_mobile-frontend）编译未完成"
    
    # 导入 Demo 数据
    import_demo_data || log_warning "Demo 数据导入未完成"
    
    # Show success info
    show_success_info
}

# 执行主函数
main "$@"
