#!/bin/bash
# 远程一键安装脚本模板
# 用法: curl -fsSL https://shopex.cn/install.sh | bash

set -e

REPO_URL="${REPO_URL:-https://gitee.com/ShopeX/ECShopX.git}"

# 终端颜色（无 GUM 时使用，避免未定义变量）
ERROR='\033[0;31m'
SUCCESS='\033[0;32m'
NC='\033[0m'

# ---------------------------------------------------------------------------
# UI 与通用辅助函数
# ---------------------------------------------------------------------------

ui_error() {
    local msg="$*"
    if [[ -n "$GUM" ]]; then
        "$GUM" log --level error "$msg"
    else
        echo -e "${ERROR}✗${NC} ${msg}"
    fi
}

ui_success() {
    local msg="$*"
    if [[ -n "$GUM" ]]; then
        local mark
        mark="$("$GUM" style --foreground "#00e5cc" --bold "✓")"
        echo "${mark} ${msg}"
    else
        echo -e "${SUCCESS}✓${NC} ${msg}"
    fi
}

ui_info() {
    local msg="$*"
    if [[ -n "${GUM:-}" ]]; then
        "$GUM" log --level info "$msg"
    else
        echo "[install] ${msg}"
    fi
}

is_root() {
    [[ "$(id -u)" -eq 0 ]]
}

require_sudo() {
    if [[ "$OS" != "linux" ]]; then
        return 0
    fi
    if is_root; then
        return 0
    fi
    if command -v sudo &> /dev/null; then
        if ! sudo -n true >/dev/null 2>&1; then
            ui_info "需要管理员权限，请输入密码"
            sudo -v
        fi
        return 0
    fi
    ui_error "Linux 下安装需要 sudo，当前系统未找到 sudo"
    echo "  请先安装 sudo 或使用 root 用户重新运行。"
    exit 1
}

run_quiet_step() {
    local desc="$1"
    shift
    ui_info "$desc..."
    if "$@" >/dev/null 2>&1; then
        return 0
    fi
    echo "[install] 步骤失败，输出如下:" >&2
    if ! "$@"; then
        ui_error "步骤失败: $desc"
        exit 1
    fi
}

# ---------------------------------------------------------------------------
# 系统检测（结果写入全局 OS）
# ---------------------------------------------------------------------------
OS="unknown"
detect_os_or_die() {
    if [[ "$OSTYPE" == "darwin"* ]]; then
        OS="macos"
    elif [[ "$OSTYPE" == "linux-gnu"* ]] || [[ -n "${WSL_DISTRO_NAME:-}" ]]; then
        OS="linux"
    fi

    if [[ "$OS" == "unknown" ]]; then
        ui_error "不支持的操作系统"
        echo "本安装脚本仅支持 macOS 与 Linux（含 WSL）。"
        echo "Windows 请使用: iwr -useb https://openclaw.ai/install.ps1 | iex"
        exit 1
    fi

    ui_success "已检测系统: $OS"
}

# ---------------------------------------------------------------------------
# Homebrew（仅 macOS，供 Git/Docker 使用，避免依赖 Xcode 命令行工具）
# ---------------------------------------------------------------------------

ensure_homebrew() {
  [[ "$OS" != "macos" ]] && return 0
  if command -v brew &>/dev/null; then
    eval "$(brew shellenv)" 2>/dev/null || true
    return 0
  fi
  echo "[install] 未检测到 Homebrew，正在自动安装（后续 Git/Docker 将由此安装，无需 Xcode）..."
  /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
  if [[ -x /opt/homebrew/bin/brew ]]; then
    eval "$(/opt/homebrew/bin/brew shellenv)"
  elif [[ -x /usr/local/bin/brew ]]; then
    eval "$(/usr/local/bin/brew shellenv)"
  fi
  if ! command -v brew &>/dev/null; then
    ui_error "Homebrew 安装后未找到 brew 命令，请重新打开终端或执行: eval \"\$(/opt/homebrew/bin/brew shellenv)\" 后重试"
    exit 1
  fi
  ui_success "Homebrew 已安装"
}

# ---------------------------------------------------------------------------
# Git 安装（兼容 macos / linux 多包管理器；macOS 使用 Homebrew 安装以避开 Xcode）
# ---------------------------------------------------------------------------

install_git() {
  if [[ "$OS" == "macos" ]]; then
    ensure_homebrew
    run_quiet_step "正在安装 Git（通过 Homebrew，无需 Xcode）" brew install git
    eval "$(brew shellenv)" 2>/dev/null || true
  elif [[ "$OS" == "linux" ]]; then
    require_sudo
    if command -v apt-get &>/dev/null; then
      if is_root; then
        run_quiet_step "正在更新软件包索引" apt-get update -qq
        run_quiet_step "正在安装 Git" apt-get install -y -qq git
      else
        run_quiet_step "正在更新软件包索引" sudo apt-get update -qq
        run_quiet_step "正在安装 Git" sudo apt-get install -y -qq git
      fi
    elif command -v dnf &>/dev/null; then
      if is_root; then
        run_quiet_step "正在安装 Git" dnf install -y -q git
      else
        run_quiet_step "正在安装 Git" sudo dnf install -y -q git
      fi
    elif command -v yum &>/dev/null; then
      if is_root; then
        run_quiet_step "正在安装 Git" yum install -y -q git
      else
        run_quiet_step "正在安装 Git" sudo yum install -y -q git
      fi
    elif command -v zypper &>/dev/null; then
      if is_root; then
        run_quiet_step "正在安装 Git" zypper install -y git
      else
        run_quiet_step "正在安装 Git" sudo zypper install -y git
      fi
    else
      ui_error "未检测到可用包管理器，请手动安装 Git: https://git-scm.com/downloads"
      exit 1
    fi
  else
    ui_error "无法识别操作系统，请手动安装 Git: https://git-scm.com/downloads"
    exit 1
  fi
  ui_success "Git 已安装"
}

# ---------------------------------------------------------------------------
# Docker 安装（兼容 macos / linux 多包管理器）
# ---------------------------------------------------------------------------

install_docker() {
  if [[ "$OS" == "macos" ]]; then
    ensure_homebrew
    run_quiet_step "正在安装 Docker Desktop" brew install --cask docker
    ui_success "Docker Desktop 已安装，请从应用程序中启动并完成初始化"
    return 0
  fi

  if [[ "$OS" != "linux" ]]; then
    ui_error "无法在此系统自动安装 Docker，请参考: https://docs.docker.com/engine/install/"
    exit 1
  fi

  require_sudo

  # 确保有 curl（get.docker.com 或后续步骤可能需要）
  if ! command -v curl &>/dev/null; then
    if command -v apt-get &>/dev/null; then
      if is_root; then
        run_quiet_step "正在更新软件包索引" apt-get update -qq
        run_quiet_step "正在安装 curl" apt-get install -y -qq curl
      else
        run_quiet_step "正在更新软件包索引" sudo apt-get update -qq
        run_quiet_step "正在安装 curl" sudo apt-get install -y -qq curl
      fi
    elif command -v dnf &>/dev/null; then
      if is_root; then
        run_quiet_step "正在安装 curl" dnf install -y -q curl
      else
        run_quiet_step "正在安装 curl" sudo dnf install -y -q curl
      fi
    elif command -v yum &>/dev/null; then
      if is_root; then
        run_quiet_step "正在安装 curl" yum install -y -q curl
      else
        run_quiet_step "正在安装 curl" sudo yum install -y -q curl
      fi
    else
      ui_error "需要 curl 来安装 Docker，请先安装 curl"
      exit 1
    fi
  fi

  # Linux: 优先使用包管理器，否则使用官方安装脚本
  if command -v apt-get &>/dev/null; then
    if is_root; then
      run_quiet_step "正在更新软件包索引" apt-get update -qq
      run_quiet_step "正在安装 Docker" apt-get install -y -qq docker.io
    else
      run_quiet_step "正在更新软件包索引" sudo apt-get update -qq
      run_quiet_step "正在安装 Docker" sudo apt-get install -y -qq docker.io
    fi
  elif command -v dnf &>/dev/null; then
    if is_root; then
      run_quiet_step "正在安装 Docker" dnf install -y -q docker
    else
      run_quiet_step "正在安装 Docker" sudo dnf install -y -q docker
    fi
  elif command -v yum &>/dev/null; then
    if is_root; then
      run_quiet_step "正在安装 Docker" yum install -y -q docker
    else
      run_quiet_step "正在安装 Docker" sudo yum install -y -q docker
    fi
  elif command -v zypper &>/dev/null; then
    if is_root; then
      run_quiet_step "正在安装 Docker" zypper install -y docker
    else
      run_quiet_step "正在安装 Docker" sudo zypper install -y docker
    fi
  else
    run_quiet_step "正在通过 get.docker.com 安装 Docker" sh -c "curl -fsSL https://get.docker.com | sh"
  fi

  # 将当前用户加入 docker 组（非 root 时）
  if ! is_root; then
    local who="${SUDO_USER:-$(whoami)}"
    if getent group docker &>/dev/null; then
      run_quiet_step "正在将用户加入 docker 组" sudo usermod -aG docker "$who" || true
    fi
  fi

  # 启动 Docker 服务（Linux）
  if command -v systemctl &>/dev/null; then
    if is_root; then
      systemctl start docker 2>/dev/null || true
      systemctl enable docker 2>/dev/null || true
    else
      sudo systemctl start docker 2>/dev/null || true
      sudo systemctl enable docker 2>/dev/null || true
    fi
  elif command -v service &>/dev/null; then
    if is_root; then
      service docker start 2>/dev/null || true
    else
      sudo service docker start 2>/dev/null || true
    fi
  fi

  ui_success "Docker 已安装。若当前用户需免 sudo 运行 docker，请重新登录或执行: newgrp docker"
}

# ---------------------------------------------------------------------------
# Docker Compose 检测与安装（dev-setup.sh 依赖）
# ---------------------------------------------------------------------------

has_docker_compose() {
  command -v docker-compose &>/dev/null || docker compose version &>/dev/null 2>&1
}

install_docker_compose() {
  if [[ "$OS" == "macos" ]]; then
    ensure_homebrew
    run_quiet_step "正在安装 Docker Compose" brew install docker-compose
    ui_success "Docker Compose 已安装"
    return 0
  fi
  if [[ "$OS" != "linux" ]]; then
    return 0
  fi
  require_sudo
  if command -v apt-get &>/dev/null; then
    if is_root; then
      run_quiet_step "正在安装 Docker Compose 插件" apt-get install -y -qq docker-compose-plugin
    else
      run_quiet_step "正在安装 Docker Compose 插件" sudo apt-get install -y -qq docker-compose-plugin
    fi
  elif command -v dnf &>/dev/null; then
    if is_root; then
      run_quiet_step "正在安装 Docker Compose" dnf install -y -q docker-compose-plugin
    else
      run_quiet_step "正在安装 Docker Compose" sudo dnf install -y -q docker-compose-plugin
    fi
  elif command -v yum &>/dev/null; then
    if is_root; then
      run_quiet_step "正在安装 Docker Compose" yum install -y -q docker-compose-plugin
    else
      run_quiet_step "正在安装 Docker Compose" sudo yum install -y -q docker-compose-plugin
    fi
  else
    if is_root; then
      run_quiet_step "正在安装 Docker Compose 独立版" sh -c 'curl -fsSL https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m) -o /usr/local/bin/docker-compose && chmod +x /usr/local/bin/docker-compose'
    else
      run_quiet_step "正在安装 Docker Compose 独立版" sudo sh -c 'curl -fsSL https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m) -o /usr/local/bin/docker-compose && chmod +x /usr/local/bin/docker-compose'
    fi
  fi
  ui_success "Docker Compose 已安装"
}

ensure_docker_compose() {
  if has_docker_compose; then
    ui_success "已检测到 Docker Compose"
    return 0
  fi
  install_docker_compose
}

ensure_git() {
  # macOS 上系统自带的 git 可能是占位符，会触发 Xcode 安装；只有通过 Homebrew 安装的 git 才可靠用于 git clone
  if [[ "$OS" == "macos" ]]; then
    if command -v brew &>/dev/null; then
      if brew list git &>/dev/null; then
        ui_success "已检测到 Git（Homebrew）"
        return 0
      fi
    fi
    # 无 brew 或 brew 下未安装 git：先确保 Homebrew 再安装 git
    install_git
    return 0
  fi
  if command -v git &>/dev/null; then
    ui_success "已检测到 Git"
    return 0
  fi
  install_git
}

ensure_docker() {
  if command -v docker &>/dev/null; then
    ui_success "已检测到 Docker: $(docker --version)"
    return 0
  fi
  install_docker
}

# ---------------------------------------------------------------------------
# 主流程
# ---------------------------------------------------------------------------

detect_os_or_die
echo "[install] 检查依赖: Git、Docker、Docker Compose (OS=$OS)..."
[[ "$OS" == "macos" ]] && ensure_homebrew
ensure_git
ensure_docker
ensure_docker_compose

echo ""
echo "[install] 当前目录: $(pwd)"
confirm="n"
if [ -e /dev/tty ]; then
    read -r -p "是否在当前目录安装？(y/n): " confirm </dev/tty
fi
case "$confirm" in
    [yY]|[yY][eE][sS]) ;;
    *)
        echo "[install] 请先切换到目标目录后再重新运行本脚本。"
        echo "  例如: cd /path/to/your/project && curl -fsSL https://shopex.cn/install.sh | bash"
        exit 1
        ;;
esac
INSTALL_DIR="$(pwd)"
# 仓库目录名（从 REPO_URL 解析，如 ECShopX）
REPO_DIR="${REPO_DIR:-$(basename "$REPO_URL" .git)}"
echo "[install] 安装目录: $INSTALL_DIR"
echo ""

if [ ! -d "$REPO_DIR/.git" ]; then
  echo "[install] 克隆仓库..."
  git clone --depth 1 "$REPO_URL"
  cd "$REPO_DIR"
else
  echo "[install] 已存在仓库，拉取最新..."
  cd "$REPO_DIR"
  git pull --rebase || true
fi

if [ -f "dev-setup.sh" ]; then
  echo "[install] 运行 dev-setup.sh..."
  bash dev-setup.sh
else
  echo "[install] 完成。未找到 dev-setup.sh，请手动进入 $REPO_DIR 执行后续步骤。"
fi
