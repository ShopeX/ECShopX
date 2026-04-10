#!/bin/bash

# ECShopX 开发环境设置脚本（Docker 方式）
# 使用单容器运行所有服务：PHP-FPM、Nginx、MySQL、Redis
# 支持三个项目：ECShopX、ECShopX_admin-frontend、ECShopX_mobile-frontend

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
DOCKER_COMPOSE_FILE="$PROJECT_ROOT/docker-compose.dev.yml"

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

# 业务模式（全局变量，在 build_admin 中设置，build_vshop 中复用）
SELECTED_PLATFORM=""

# 默认语言（全局变量，在第一个编译的项目中设置，后续项目复用）
SELECTED_LANG=""

# 语言/业务模式等交互必须从 /dev/tty 读取：若仅从 stdin 读，在「管道、重定向、部分 IDE 集成终端」下会立即 EOF，
# 空变量会触发 ${VAR:-1}，表现为未确认就选用默认项 1。

# 跟踪已安装的前端项目
INSTALLED_ADMIN=false
INSTALLED_VSHOP=false
INSTALLED_PC=false

# 记录开始时间
START_TIME=$(date +%s)

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
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_step() {
    echo -e "${CYAN}[STEP]${NC} $1"
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
    echo ""
    echo "示例:"
    echo "  $0                    # 正常启动（使用缓存）"
    echo "  $0 --rebuild          # 重新构建镜像"
    echo "  $0 --skip-admin       # 跳过管理后台编译"
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
    
    # 检查 ECShopX_admin-frontend
    ADMIN_DIR="$PARENT_DIR/ECShopX_admin-frontend"
    ADMIN_REPO="https://gitee.com/ShopeX/ECShopX_admin-frontend.git"
    
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
    VSHOP_REPO="https://gitee.com/ShopeX/ECShopX_mobile-frontend.git"
    
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
    
    # 检查 ECShopX_desktop-frontend
    PC_DIR="$PARENT_DIR/ECShopX_desktop-frontend"
    PC_REPO="https://gitee.com/ShopeX/ECShopX_desktop-frontend.git"
    
    if [ ! -d "$PC_DIR" ] || [ ! -f "$PC_DIR/package.json" ]; then
        if [ ! -d "$PC_DIR" ]; then
            log_warning "ECShopX_desktop-frontend 目录不存在"
        else
            log_warning "ECShopX_desktop-frontend 目录存在但缺少 package.json"
        fi
        
        echo ""
        echo -n "是否从 Gitee 克隆PC商城（ECShopX_desktop-frontend）代码？ [Y/n]: "
        read -r answer < /dev/tty
        
        if [ -z "$answer" ] || [ "$answer" = "Y" ] || [ "$answer" = "y" ] || [ "$answer" = "yes" ] || [ "$answer" = "YES" ]; then
            if [ -d "$PC_DIR" ]; then
                log_info "清空现有目录内容..."
                find "$PC_DIR" -mindepth 1 -delete 2>/dev/null
            fi
            
            log_info "正在从 Gitee 克隆PC商城（ECShopX_desktop-frontend）..."
            git clone "$PC_REPO" "$PC_DIR" > /tmp/git_clone_pc.log 2>&1 &
            local clone_pid=$!
            
            # 显示进度动画
            local spinstr='|/-\'
            while kill -0 $clone_pid 2>/dev/null; do
                local temp=${spinstr#?}
                printf "\r${CYAN}[INFO]${NC} 克隆PC商城（ECShopX_desktop-frontend）中 ${spinstr:0:1}"
                spinstr=$temp${spinstr%"$temp"}
                sleep 0.2
            done
            wait $clone_pid
            local clone_exit=$?
            
            if [ $clone_exit -eq 0 ]; then
                printf "\r${GREEN}[SUCCESS]${NC}PC商城（ECShopX_desktop-frontend）克隆成功"
                printf "%50s" ""
                echo ""
                INSTALLED_PC=true
            else
                printf "\r${RED}[ERROR]${NC}PC商城（ECShopX_desktop-frontend）克隆失败"
                printf "%50s" ""
                echo ""
                cat /tmp/git_clone_pc.log 2>/dev/null | tail -10
                exit 1
            fi
        else
            log_warning "跳过PC商城（ECShopX_desktop-frontend）克隆"
        fi
    else
        log_info "ECShopX_desktop-frontend 目录已存在且包含 package.json，跳过克隆"
        INSTALLED_PC=true
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
                # 如果容器未运行，先启动它
                if ! is_container_running; then
                    log_info "启动现有容器..."
                    $DOCKER_COMPOSE_CMD -f "$DOCKER_COMPOSE_FILE" up -d
                    sleep 5
                    wait_for_services
                fi
                return 1  # 跳过构建
                ;;
            3)
                log_info "退出脚本"
                exit 0
                ;;
            *)
                log_info "使用现有容器..."
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
        (grep -q '^APP_URL=' .env && sed -i 's|^APP_URL=.*|APP_URL=http://localhost:8080|' .env || echo 'APP_URL=http://localhost:8080' >> .env)" 2>/dev/null || true
    
    # 选择业务模式并写入 PRODUCT_MODEL
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
# 检查容器内目录是否存在并挂载正确
# ===========================================

check_container_directory() {
    local container_path=$1
    local host_path=$2
    local project_name=$3
    local max_retries=2
    local retry_count=0
    
    # 首先检查容器是否运行
    if ! is_container_running; then
        log_warning "容器未运行，无法检查目录挂载状态"
        log_info "目录挂载将在容器启动后自动生效"
        # 对于 ECShopX_desktop-frontend，如果容器未运行，也返回成功（允许继续）
        if [ "$project_name" = "ECShopX_desktop-frontend" ]; then
            return 0
        fi
        return 0  # 容器未运行时，假设挂载会在启动后生效
    fi
    
    while [ $retry_count -lt $max_retries ]; do
        # 检查目录是否存在
        if docker exec "$CONTAINER_NAME" sh -c "test -d $container_path" 2>/dev/null; then
            # 检查 package.json 是否存在
            if docker exec "$CONTAINER_NAME" sh -c "test -f $container_path/package.json" 2>/dev/null; then
                log_success "目录挂载检查通过: $container_path"
                return 0  # 目录和文件都存在
            else
                if [ $retry_count -eq 0 ]; then
                    log_warning "容器内 $container_path/package.json 不存在，尝试重启容器以确保目录正确挂载..."
                    if ! restart_container_for_mount "$project_name"; then
                        return 1
                    fi
                    retry_count=$((retry_count + 1))
                    continue
                else
                    # 对于 ECShopX_desktop-frontend，如果重启后仍然不存在，也允许继续（可能是配置问题）
                    if [ "$project_name" = "ECShopX_desktop-frontend" ]; then
                        log_warning "容器内 $container_path/package.json 仍然不存在，但继续执行..."
                        return 0
                    fi
                    log_error "容器内 $container_path/package.json 仍然不存在"
                    log_info "请检查主机目录 $host_path 是否存在且包含 package.json"
                    return 1
                fi
            fi
        else
            if [ $retry_count -eq 0 ]; then
                log_warning "容器内 $container_path 目录不存在，尝试重启容器以确保目录正确挂载..."
                if ! restart_container_for_mount "$project_name"; then
                    return 1
                fi
                retry_count=$((retry_count + 1))
                continue
            else
                log_error "容器内 $container_path 目录仍然不存在"
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
    log_warning "检测到 $project_name 目录未正确挂载，正在重启容器..."
    
    if docker ps --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$" 2>/dev/null; then
        log_info "重启容器 $CONTAINER_NAME..."
        docker-compose -f "$DOCKER_COMPOSE_FILE" restart || {
            log_error "容器重启失败"
            return 1
        }
        
        log_info "等待容器启动..."
        sleep 5
        
        # 等待服务就绪
        wait_for_services
        
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
    elif [ "$project_name" = "ECShopX_desktop-frontend" ]; then
        container_path="/data/httpd/ECShopX_desktop-frontend"
    else
        log_warning "未知的项目名称: $project_name"
        return 0
    fi
    
    # 如果容器未运行，直接配置主机目录
    if ! is_container_running; then
        log_info "容器未运行，配置主机目录的 .env 文件..."
        local env_file="$project_dir/.env"
        
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
                    sed -i '' "s|^VUE_APP_QIANKUN_ENTRY=.*|VUE_APP_QIANKUN_ENTRY=http://localhost:8080/newpc/|" "$env_file"
                else
                    sed -i "s|^VUE_APP_QIANKUN_ENTRY=.*|VUE_APP_QIANKUN_ENTRY=http://localhost:8080/newpc/|" "$env_file"
                fi
            else
                echo "VUE_APP_QIANKUN_ENTRY=http://localhost:8080/newpc/" >> "$env_file"
            fi
            log_success "已配置 VUE_APP_QIANKUN_ENTRY=http://localhost:8080/newpc/"
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
        
        # 配置 ECShopX_desktop-frontend
        if [ "$project_name" = "ECShopX_desktop-frontend" ]; then
            # 使用 sed 修改或添加 VUE_APP_API_BASE_URL
            if grep -q "^VUE_APP_API_BASE_URL=" "$env_file" 2>/dev/null; then
                # macOS 和 Linux 兼容的 sed 命令
                if [[ "$OSTYPE" == "darwin"* ]]; then
                    sed -i '' "s|^VUE_APP_API_BASE_URL=.*|VUE_APP_API_BASE_URL=$api_base_url|" "$env_file"
                else
                    sed -i "s|^VUE_APP_API_BASE_URL=.*|VUE_APP_API_BASE_URL=$api_base_url|" "$env_file"
                fi
            else
                echo "VUE_APP_API_BASE_URL=$api_base_url" >> "$env_file"
            fi
            log_success "已配置 VUE_APP_API_BASE_URL=$api_base_url"
            
            # 配置 VUE_APP_COMPANYID（默认值为1）
            if grep -q "^VUE_APP_COMPANYID=" "$env_file" 2>/dev/null; then
                # macOS 和 Linux 兼容的 sed 命令
                if [[ "$OSTYPE" == "darwin"* ]]; then
                    sed -i '' "s|^VUE_APP_COMPANYID=.*|VUE_APP_COMPANYID=1|" "$env_file"
                else
                    sed -i "s|^VUE_APP_COMPANYID=.*|VUE_APP_COMPANYID=1|" "$env_file"
                fi
            else
                echo "VUE_APP_COMPANYID=1" >> "$env_file"
            fi
            log_success "已配置 VUE_APP_COMPANYID=1"
            
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
        fi
    else
        # 容器运行中，配置容器内的 .env 文件
        # 检查容器内目录是否存在
        if ! docker exec "$CONTAINER_NAME" sh -c "test -d $container_path" 2>/dev/null; then
            log_warning "容器内 $container_path 目录不存在，跳过配置"
            return 0
        fi
        
        # 在容器内配置 .env 文件
        docker exec "$CONTAINER_NAME" sh -c "
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
            docker exec "$CONTAINER_NAME" sh -c "
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
                docker exec "$CONTAINER_NAME" sh -c "
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
            docker exec "$CONTAINER_NAME" sh -c "
                cd $container_path && \
                if grep -q '^VUE_APP_QIANKUN_ENTRY=' .env 2>/dev/null; then
                    sed -i 's|^VUE_APP_QIANKUN_ENTRY=.*|VUE_APP_QIANKUN_ENTRY=http://localhost:8080/newpc/|' .env
                else
                    echo 'VUE_APP_QIANKUN_ENTRY=http://localhost:8080/newpc/' >> .env
                fi
            " 2>/dev/null || true
            log_success "已配置容器内 VUE_APP_QIANKUN_ENTRY=http://localhost:8080/newpc/"
        fi
        
        # 配置 ECShopX_mobile-frontend
        if [ "$project_name" = "ECShopX_mobile-frontend" ]; then
            docker exec "$CONTAINER_NAME" sh -c "
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
                docker exec "$CONTAINER_NAME" sh -c "
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
                docker exec "$CONTAINER_NAME" sh -c "
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
        
        # 配置 ECShopX_desktop-frontend
        if [ "$project_name" = "ECShopX_desktop-frontend" ]; then
            docker exec "$CONTAINER_NAME" sh -c "
                cd $container_path && \
                if grep -q '^VUE_APP_API_BASE_URL=' .env 2>/dev/null; then
                    sed -i 's|^VUE_APP_API_BASE_URL=.*|VUE_APP_API_BASE_URL=$api_base_url|' .env
                else
                    echo 'VUE_APP_API_BASE_URL=$api_base_url' >> .env
                fi
            " 2>/dev/null || true
            log_success "已配置容器内 VUE_APP_API_BASE_URL=$api_base_url"
            
            # 配置 VUE_APP_COMPANYID（默认值为1）
            docker exec "$CONTAINER_NAME" sh -c "
                cd $container_path && \
                if grep -q '^VUE_APP_COMPANYID=' .env 2>/dev/null; then
                    sed -i 's|^VUE_APP_COMPANYID=.*|VUE_APP_COMPANYID=1|' .env
                else
                    echo 'VUE_APP_COMPANYID=1' >> .env
                fi
            " 2>/dev/null || true
            log_success "已配置容器内 VUE_APP_COMPANYID=1"
            
            # 配置 VUE_APP_DEFAULT_LANG（如果提供）
            if [ -n "$default_lang" ]; then
                docker exec "$CONTAINER_NAME" sh -c "
                    cd $container_path && \
                    if grep -q '^VUE_APP_DEFAULT_LANG=' .env 2>/dev/null; then
                        sed -i 's|^VUE_APP_DEFAULT_LANG=.*|VUE_APP_DEFAULT_LANG=$default_lang|' .env
                    else
                        echo 'VUE_APP_DEFAULT_LANG=$default_lang' >> .env
                    fi
                " 2>/dev/null || true
                log_success "已配置容器内 VUE_APP_DEFAULT_LANG=$default_lang"
            fi
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
    
    # 检查是否已有编译产物
    if [ -d "$ADMIN_DIR/dist" ] && [ -f "$ADMIN_DIR/dist/index.html" ]; then
        log_info "检测到已有编译产物，跳过编译"
        log_info "如需重新编译，请删除 ECShopX_admin-frontend/dist 目录"
        return 0
    fi
    
    # 检查容器内目录是否存在并确保正确挂载
    log_info "检查容器内目录挂载状态..."
    if ! check_container_directory "/data/httpd/ECShopX_admin-frontend" "$ADMIN_DIR" "ECShopX_admin-frontend"; then
        return 1
    fi
    
    # 确认默认语言（如果还未选择）
    if [ -z "$SELECTED_LANG" ]; then
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
    else
        log_info "复用已选择的默认语言: $SELECTED_LANG"
    fi
    
    # 配置前端项目的 .env 文件
    log_info "配置 ECShopX_admin-frontend 的 .env 文件..."
    configure_frontend_env "$ADMIN_DIR" "ECShopX_admin-frontend" "http://localhost:8080/api/" "" "$SELECTED_LANG"
    
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
# 编译 ECShopX_desktop-frontend
# ===========================================

build_pc() {
    if [ "$SKIP_PC" = true ]; then
        log_info "跳过 ECShopX_desktop-frontend 编译（--skip-pc）"
        return 0
    fi
    
    PC_DIR="$PARENT_DIR/ECShopX_desktop-frontend"
    
    if [ ! -d "$PC_DIR" ]; then
        log_warning "ECShopX_desktop-frontend 目录不存在，跳过编译"
        return 0
    fi
    
    log_info "=========================================="
    log_info "开始编译 ECShopX_desktop-frontend（PC前端）..."
    log_info "=========================================="
    
    if [ ! -f "$PC_DIR/package.json" ]; then
        log_error "ECShopX_desktop-frontend/package.json 不存在"
        return 1
    fi
    
    # 检查是否已有编译产物（Nuxt项目编译后生成.nuxt或.output目录）
    if docker exec "$CONTAINER_NAME" sh -c "[ -d /data/httpd/ECShopX_desktop-frontend/.nuxt ]" 2>/dev/null || \
       docker exec "$CONTAINER_NAME" sh -c "[ -d /data/httpd/ECShopX_desktop-frontend/.output ]" 2>/dev/null || \
       [ -d "$PC_DIR/.nuxt" ] || [ -d "$PC_DIR/.output" ]; then
        log_info "检测到已有编译产物，跳过编译"
        log_info "如需重新编译，请删除 ECShopX_desktop-frontend/.nuxt 或 .output 目录"
        # 即使跳过编译，也需要启动Nuxt服务
        need_build=false
    else
        need_build=true
    fi
    
    # 检查容器内目录是否存在并确保正确挂载
    log_info "检查容器内目录挂载状态..."
    if ! check_container_directory "/data/httpd/ECShopX_desktop-frontend" "$PC_DIR" "ECShopX_desktop-frontend"; then
        return 1
    fi
    
    # 如果跳过编译，直接启动Nuxt服务，不需要确认语言
    if [ "$need_build" = false ]; then
        # 启动 Nuxt 服务
        log_info "启动 Nuxt 服务（监听3000端口）..."
        
        # 检查是否已有Nuxt进程在运行
        if docker exec "$CONTAINER_NAME" sh -c "pgrep -f 'nuxt.*3000\|node.*nuxt' > /dev/null" 2>/dev/null; then
            log_info "Nuxt 服务已在运行"
        else
            # 启动Nuxt服务
            if docker exec "$CONTAINER_NAME" sh -c "cd /data/httpd/ECShopX_desktop-frontend && npm run | grep -q 'start'" 2>/dev/null; then
                docker exec -d "$CONTAINER_NAME" sh -c "
                    cd /data/httpd/ECShopX_desktop-frontend && \
                    PORT=3000 HOST=0.0.0.0 nohup npm run start > /var/log/nuxt.log 2>&1 &
                " 2>/dev/null || true
            else
                docker exec -d "$CONTAINER_NAME" sh -c "
                    cd /data/httpd/ECShopX_desktop-frontend && \
                    PORT=3000 HOST=0.0.0.0 nohup npm run dev > /var/log/nuxt.log 2>&1 &
                " 2>/dev/null || true
            fi
            
            # 等待Nuxt服务启动
            sleep 3
        fi
        
        # 重新加载nginx配置
        docker exec "$CONTAINER_NAME" sh -c "nginx -s reload" 2>/dev/null || true
        return 0
    fi
    
    # 只有在需要编译时才确认默认语言和配置.env
    if [ "$need_build" = true ]; then
        # 确认默认语言（如果还未选择）
        if [ -z "$SELECTED_LANG" ]; then
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
        else
            log_info "复用已选择的默认语言: $SELECTED_LANG"
        fi
        
        # 配置前端项目的 .env 文件
        log_info "配置 ECShopX_desktop-frontend 的 .env 文件..."
        configure_frontend_env "$PC_DIR" "ECShopX_desktop-frontend" "http://localhost:8080" "" "$SELECTED_LANG"
        
        log_info "安装 npm 依赖..."
        # 使用绝对路径并先验证目录存在
        docker exec "$CONTAINER_NAME" sh -c "
            if [ ! -d /data/httpd/ECShopX_desktop-frontend ]; then
                echo '错误: 目录不存在'
                exit 1
            fi
            cd /data/httpd/ECShopX_desktop-frontend || exit 1
            npm install --legacy-peer-deps
        " > /tmp/npm_pc_output.log 2>&1 &
        local npm_pid=$!
        
        # 显示进度动画
        local spinstr='|/-\'
        while kill -0 $npm_pid 2>/dev/null; do
            local temp=${spinstr#?}
            printf "\r${CYAN}[INFO]${NC} 安装 ECShopX_desktop-frontend npm 依赖中 ${spinstr:0:1}"
            spinstr=$temp${spinstr%"$temp"}
            sleep 0.2
        done
        wait $npm_pid
        local npm_exit=$?
        
        if [ $npm_exit -ne 0 ]; then
            printf "\r${RED}[ERROR]${NC} ECShopX_desktop-frontend npm install 失败"
            printf "%50s" ""
            echo ""
            cat /tmp/npm_pc_output.log 2>/dev/null | tail -20
            log_info "请检查容器内目录状态: docker exec $CONTAINER_NAME ls -la /data/httpd/ECShopX_desktop-frontend"
            return 1
        else
            printf "\r${GREEN}[SUCCESS]${NC} ECShopX_desktop-frontend npm 依赖安装完成"
            printf "%50s" ""
            echo ""
        fi
        
        log_info "执行编译（npm run build）..."
        docker exec "$CONTAINER_NAME" sh -c "cd /data/httpd/ECShopX_desktop-frontend && npm run build" > /tmp/build_pc_output.log 2>&1 &
        local build_pid=$!
        
        # 显示进度动画
        local spinstr='|/-\'
        while kill -0 $build_pid 2>/dev/null; do
            local temp=${spinstr#?}
            printf "\r${CYAN}[INFO]${NC} 编译 ECShopX_desktop-frontend 中 ${spinstr:0:1}"
            spinstr=$temp${spinstr%"$temp"}
            sleep 0.2
        done
        wait $build_pid
        local build_exit=$?
        
        if [ $build_exit -ne 0 ]; then
            printf "\r${RED}[ERROR]${NC} ECShopX_desktop-frontend 编译失败"
            printf "%50s" ""
            echo ""
            cat /tmp/build_pc_output.log 2>/dev/null | tail -20
            return 1
        else
            printf "\r${GREEN}[SUCCESS]${NC} ECShopX_desktop-frontend 编译完成"
            printf "%50s" ""
            echo ""
        fi
        # 验证编译产物
        if ! docker exec "$CONTAINER_NAME" sh -c "[ -d /data/httpd/ECShopX_desktop-frontend/.nuxt ] || [ -d /data/httpd/ECShopX_desktop-frontend/.output ]" 2>/dev/null; then
            log_error "ECShopX_desktop-frontend 编译输出不完整"
            return 1
        fi
    fi
    
    # 检查编译产物（Nuxt项目编译后生成.nuxt或.output目录）
    if docker exec "$CONTAINER_NAME" sh -c "[ -d /data/httpd/ECShopX_desktop-frontend/.nuxt ] || [ -d /data/httpd/ECShopX_desktop-frontend/.output ]" 2>/dev/null; then
        log_success "ECShopX_desktop-frontend 编译成功"
        
        # 启动 Nuxt 服务
        log_info "启动 Nuxt 服务（监听3000端口）..."
        
        # 检查是否已有Nuxt进程在运行
        if docker exec "$CONTAINER_NAME" sh -c "pgrep -f 'nuxt.*3000\|node.*nuxt' > /dev/null" 2>/dev/null; then
            log_info "Nuxt 服务已在运行，重启服务..."
            docker exec "$CONTAINER_NAME" sh -c "pkill -f 'nuxt.*3000\|node.*nuxt'" 2>/dev/null || true
            sleep 2
        fi
        
        # 在后台启动Nuxt服务
        # 优先使用生产模式（npm run start），如果不存在则使用开发模式（npm run dev）
        log_info "检查可用的启动命令..."
        if docker exec "$CONTAINER_NAME" sh -c "cd /data/httpd/ECShopX_desktop-frontend && npm run | grep -q 'start'" 2>/dev/null; then
            log_info "使用生产模式启动（npm run start）..."
            docker exec -d "$CONTAINER_NAME" sh -c "
                cd /data/httpd/ECShopX_desktop-frontend && \
                PORT=3000 HOST=0.0.0.0 nohup npm run start > /var/log/nuxt.log 2>&1 &
            " || {
                log_error "Nuxt 服务启动失败"
                log_info "请检查日志: docker exec $CONTAINER_NAME tail -f /var/log/nuxt.log"
                return 1
            }
        else
            log_info "使用开发模式启动（npm run dev）..."
            docker exec -d "$CONTAINER_NAME" sh -c "
                cd /data/httpd/ECShopX_desktop-frontend && \
                PORT=3000 HOST=0.0.0.0 nohup npm run dev > /var/log/nuxt.log 2>&1 &
            " || {
                log_error "Nuxt 服务启动失败"
                log_info "请检查日志: docker exec $CONTAINER_NAME tail -f /var/log/nuxt.log"
                return 1
            }
        fi
        
        # 等待Nuxt服务启动
        log_info "等待 Nuxt 服务启动..."
        local nuxt_ready=false
        for i in {1..30}; do
            if docker exec "$CONTAINER_NAME" sh -c "curl -s http://127.0.0.1:3000 > /dev/null" 2>/dev/null; then
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
                log_info "H5前端访问地址: http://localhost:8081"
            fi
            if [ "$INSTALLED_PC" = true ]; then
                log_info "PC前端访问地址: http://localhost:8082"
            fi
        else
            printf "\r${YELLOW}[WARNING]${NC} Nuxt 服务启动超时，但可能仍在启动中"
            printf "%50s" ""
            echo ""
            log_info "请检查日志: docker exec $CONTAINER_NAME tail -f /var/log/nuxt.log"
            if [ "$INSTALLED_VSHOP" = true ]; then
                log_info "H5前端访问地址: http://localhost:8081"
            fi
            if [ "$INSTALLED_PC" = true ]; then
                log_info "PC前端访问地址: http://localhost:8082"
            fi
        fi
        
        # 重新加载nginx配置以确保8082端口配置生效
        log_info "重新加载 Nginx 配置..."
        docker exec "$CONTAINER_NAME" sh -c "nginx -s reload" 2>/dev/null || {
            log_warning "Nginx 配置重载失败，可能需要重启容器"
        }
    else
        log_error "ECShopX_desktop-frontend 编译输出不完整"
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
    
    # 检查是否已有编译产物
    if [ -d "$VSHOP_DIR/dist" ]; then
        log_info "检测到已有编译产物，跳过编译"
        log_info "如需重新编译，请删除 ECShopX_mobile-frontend/dist 目录"
        return 0
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
    
    # 确认默认语言（如果还未选择）
    if [ -z "$SELECTED_LANG" ]; then
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
    else
        log_info "复用已选择的默认语言: $SELECTED_LANG"
    fi
    
    # 配置前端项目的 .env 文件
    log_info "配置 ECShopX_mobile-frontend 的 .env 文件..."
    configure_frontend_env "$VSHOP_DIR" "ECShopX_mobile-frontend" "http://localhost:8080/api/h5app/wxapp" "$SELECTED_PLATFORM" "$SELECTED_LANG"
    
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
        log_info "  管理后台: http://localhost:8080"
    fi
    
    if [ "$INSTALLED_VSHOP" = true ]; then
        log_info "  H5前端:   http://localhost:8081"
    fi
    
    if [ "$INSTALLED_PC" = true ]; then
        log_info "  PC前端:   http://localhost:8082"
    fi
    
    log_info "  API 接口: http://localhost:8080/api/"
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
    log_info "  服务状态:     docker exec $CONTAINER_NAME supervisorctl status"
    log_info "  进入容器:     docker exec -it $CONTAINER_NAME sh"
    log_info "  停止服务:     $DOCKER_COMPOSE_CMD -f $DOCKER_COMPOSE_FILE down"
    log_info "  重启服务:     $DOCKER_COMPOSE_CMD -f $DOCKER_COMPOSE_FILE restart"
    log_info "  重新构建:     $0 --rebuild"
    echo ""
    log_info "执行时间: ${MINUTES}分${SECONDS}秒"
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
    echo "  支持: ECShopX / ECShopX_admin-frontend / ECShopX_mobile-frontend / ECShopX_desktop-frontend"
    echo "=========================================="
    echo ""
    
    detect_os
    check_docker
    
    # 检查并克隆前端项目（在启动容器之前）
    check_and_clone_frontend
    
    # 检查容器状态
    if check_container_status; then
        run_docker
    fi
    
    # 配置 PHP 应用
    configure_env
    configure_application
    
    # 编译前端项目
    build_admin || log_warning "管理后台（ECShopX_admin-frontend）编译未完成"
    build_vshop || log_warning "移动商城（ECShopX_mobile-frontend）编译未完成"
    build_pc || log_warning "PC商城（ECShopX_desktop-frontend）编译未完成"
    
    # 导入 Demo 数据
    import_demo_data || log_warning "Demo 数据导入未完成"
    
    # Show success info
    show_success_info
}

# 执行主函数
main "$@"
