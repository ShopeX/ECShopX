# ECShopX 宝塔打包与一键部署（API + 管理后台 + H5 + PC 商城）

脚本目录：`baota/`（项目根下一层；**不会**打进 zip，仅打包机使用）。

| 路径 | 内容 |
|------|------|
| `/api/`、`/storage/`、`/wechatAuth/` | 后端 API |
| `/admin/` | 管理后台静态资源 |
| `/mobile/` | H5 商城静态资源 |
| `/web/` | PC 商城 Nuxt SSR（`web/.output/`，Supervisor 监听 127.0.0.1:3000） |
| `/images/`、`/assets/` | 反代到 Nuxt（`/web/images/`、`/web/assets/`），兼容前端写死的根路径资源 |

## 文件说明

| 文件 | 作用 |
|------|------|
| `auto_install.json` | PHP 版本、扩展、运行目录 `/public`、解禁函数、默认管理员账号 |
| `install.sh` | 解压后自动执行：注入 DB、装依赖、密钥、迁移、Demo、域名绑定、管理员、短信场景、权限、队列/Nuxt/计划任务 |
| `env.production.tpl` | `.env` 模板（`BT_DB_*` 占位符由 **install.sh** 注入，非面板；打包时写入 `PRODUCT_MODEL`） |
| `nginx.rewrite` | 伪静态（API + `/admin` + `/mobile` + `/web` + `/images`/`/assets`），须在包根目录 |
| `pack.sh` | 组装 zip（composer + 前端 → `public/admin`、`public/mobile`、`web/.output`） |

## 包结构

zip 顶层目录名为 `ECShopX-bbc` 或 `ECShopX-b2c`（不是裸 `ECShopX/`）：

```
ECShopX-bbc/   （或 ECShopX-b2c/）
├── auto_install.json
├── install.sh
├── nginx.rewrite
├── .env                    ← 已写入对应 PRODUCT_MODEL
├── public/
│   ├── index.php
│   ├── admin/              ← 管理后台 dist
│   └── mobile/             ← H5 dist/h5
├── web/
│   └── .output/            ← PC Nuxt SSR 构建产物
├── docker-dev/demo/        ← 仅含本模式 Demo SQL
├── vendor/                 ← 默认预装；PACK_COMPOSER=false 时安装机再装
└── ...
```

## 打包

在 `ECShopX` 根目录：

```bash
# 默认一次产出 BBC + B2C 两个 zip（版本号来自 composer.json）：
#   ecshopx-{version}-bbc-baota.zip  （PRODUCT_MODEL=platform，Demo: bbc.sql）
#   ecshopx-{version}-b2c-baota.zip  （PRODUCT_MODEL=standard，Demo: b2c_sports.sql）
cd ECShopX
bash baota/pack.sh

# 可选：指定输出目录（默认项目根目录）
# bash baota/pack.sh /path/to/output

# 仅打单包（显式设置 PACK_PLATFORM）：
# PACK_PLATFORM=platform bash baota/pack.sh   # → ecshopx-{version}-bbc-baota.zip
# PACK_PLATFORM=standard bash baota/pack.sh  # → ecshopx-{version}-b2c-baota.zip

# 若已事先编译好 dist / .output，可跳过编译：
# PACK_FRONTEND_BUILD=false bash baota/pack.sh
```

**打包机 Node（nvm）**：admin/mobile 使用 Node **16.20**，PC 使用 Node **20.19** + pnpm，由 `pack.sh` 通过 nvm 自动切换。需预先安装 [nvm](https://github.com/nvm-sh/nvm) 并确保对应版本可用（首次会自动 `nvm install`）。

```bash
# 可选：自定义 Node / pnpm 版本
# PACK_NODE_ADMIN_MOBILE=16.20 PACK_NODE_PC=20.19 PACK_PNPM_VERSION=10.13.0 bash baota/pack.sh

# 可选：PC 构建跳过 nvm，直接指定 Node 20 可执行文件
# WEB_NODE_BIN=/path/to/node20 bash baota/pack.sh
```

可选环境变量：

| 变量 | 默认 | 说明 |
|------|------|------|
| `PACK_COMPOSER` | `true` | 预装 vendor |
| `PACK_FRONTEND` | `true` | 打入前端 |
| `PACK_FRONTEND_BUILD` | `true` | `false` 时跳过编译，直接使用已有 dist / `.output`（重新编译需 nvm：admin/mobile → Node 16.20，PC → Node 20.19 + pnpm） |
| `PACK_PLATFORM` | （未设置） | 显式设置时仅打单包：`platform`(BBC) / `standard`(B2C)；未设置则默认双包 |
| `PACK_DEFAULT_LANG` | `zhcn` | 默认语言：`zhcn` / `en`（管理后台 `VUE_APP_DEFAULT_LANG`、H5 `APP_I18N_ORIGIN_LANG`） |
| `PACK_ADMIN_SCRIPT` | 由模式推导（`bbc`→`build:bbc`，`b2c`→`build:b2c`） | 可手动覆盖管理后台 npm script（**仅单包模式**） |
| （编译注入） | — | 管理后台：`VUE_APP_PUBLIC_PATH=/admin/`、`VUE_APP_BASE_API=/api`；H5：`APP_PUBLIC_PATH=/mobile/`、`APP_ROUTER_BASENAME=/mobile`、`APP_BASE_URL=/api/h5app/wxapp/`、`APP_IMAGE_CDN`（见下表）、`APP_PLATFORM`；PC：`NUXT_APP_BASE_URL=/web/`、`NUXT_PUBLIC_API_BASE=/api/h5app`、`NUXT_PUBLIC_BUSINESS_MODE=bbc\|b2c` |
| `APP_IMAGE_CDN` | `https://ecshopx-vshop-images.oss-cn-shanghai.aliyuncs.com` | H5 静态图 CDN（多数图不在本地包内，构建时注入） |
| `ADMIN_FRONTEND_DIR` | `../ECShopX_admin-frontend` | 管理后台仓库 |
| `MOBILE_FRONTEND_DIR` | `../ECShopX_mobile-frontend` | H5 仓库 |
| `WEB_FRONTEND_DIR` | `../ECShopX_web-frontend` | PC Nuxt 仓库 |
| `PACK_NODE_ADMIN_MOBILE` | `16.20` | admin/mobile 构建 Node 版本（nvm） |
| `PACK_NODE_PC` | `20.19` | PC 构建 Node 版本（nvm） |
| `PACK_PNPM_VERSION` | `10.13.0` | PC 构建 pnpm 版本 |
| `WEB_NODE_BIN` | （未设置） | 设则 PC 构建跳过 nvm，直接使用该 Node 20+ 可执行文件 |

## 部署

1. 宝塔 → **软件商店 → 一键部署 → 导入项目**，上传 `ecshopx-{version}-bbc-baota.zip` 或 `ecshopx-{version}-b2c-baota.zip`
2. **务必勾选创建数据库**（并记住库名/密码）；否则 `install.sh` 无法注入 `DB_*`
3. PHP 选 **8.2 或 8.3**（见 `auto_install.json` 的 `php_versions`），绑定域名
4. 面板会执行 `install.sh`（日志：`storage/logs/bt-install.log`），然后删除该脚本
5. 确认伪静态已加载包根目录的 `nginx.rewrite`（若未生效，在站点伪静态中粘贴该文件内容）

前置：PHP 8.2+（扩展见 `auto_install.json`）、MySQL、Redis、**Node ≥20**（PC Nuxt 运行）、**Supervisor**（队列 + `ecshopx-nuxt`）、**127.0.0.1:3000 未被占用**。

宝塔环境：`install.sh` 会自动探测 `/www/server/panel/pyenv/bin/supervisorctl -c /etc/supervisor/supervisord.conf` 与 `/www/server/nodejs/v20.*/bin/node`（Node 版本管理器），计划任务写入 `www` 用户 crontab。

Supervisor 配置：`install.sh` 优先写入宝塔「Supervisor 管理器」目录 `/www/server/panel/plugin/supervisor/profile/*.ini`（`ecshopx-queue-*`、`ecshopx-nuxt`）；若该目录不存在则回退到 `/etc/supervisor.d` 或 `/etc/supervisor/conf.d` 并使用 `.conf`。

PC Nuxt：`install.sh` 注册 Supervisor `ecshopx-nuxt`，环境变量含 `NUXT_PUBLIC_API_BASE=/api/h5app`、`NUXT_INTERNAL_API_BASE=http://127.0.0.1/api/h5app`、`NUXT_PUBLIC_COMPANY_ID`（取自 `.env` 的 `SYSTEM_MAIN_COMPANYS_ID`，默认 `1`）。多站点或 API 非本机回环时，需按实际 Host 调整 `NUXT_INTERNAL_API_BASE`。

## install.sh 做了什么

1. **注入 DB**：若 `.env` 仍为 `BT_DB_*`，通过宝塔 `public.M` API（兼容拆分后的 `site.db`/`database.db` 与加密密码）写入真实账号；无关联库则失败退出
2. composer（无 `vendor` 时）
3. 生成 `APP_KEY` / `JWT_SECRET` / `REDIS_PASSWORD`（已有则跳过）
4. `doctrine:migrations:migrate --force`（占位符未清除则直接失败，不假装成功）
5. 按 `PRODUCT_MODEL` 导入 Demo（`platform` → `bbc.sql`；`standard` → `b2c_sports.sql`；`items` 已有数据则跳过）
6. **绑定商户域名**：站点名像域名/IP 时，将 `companys.pc_domain` / `h5_domain` 设为该 Host（`company_id` = `SYSTEM_MAIN_COMPANYS_ID`）
7. 初始化管理员密码（与 `auto_install.json` 中 `admin_username` / `admin_password` 一致，默认 `admin` / `EcShopX@2026`），并写入 `storage/logs/admin-credentials.txt`
8. 阿里云短信场景初始化（`aliyunsms:scene:initialize 1`；失败仅记录日志，不中断安装）
9. 目录权限、`public/storage` 软链；若存在则再建 `public/images`、`public/assets` → `web/.output/public/...`
10. Supervisor 队列（`default` / `quick` / `seckill` / `slow` / `sms`，对齐 `docker-new/supervisor/super-queue.ini`）+ PC Nuxt（`ecshopx-nuxt`，需 Node ≥20）+ crontab `schedule:run`

可重复执行：密钥/依赖/cron 幂等；迁移由 Doctrine 跟踪。

## 部署后

1. 改 `.env` 的 `APP_URL` 为实际域名（无尾斜杠）
2. 访问 `https://你的域名/admin/`、`/mobile/`、`/web/`、`/api/`
3. 确认 Supervisor 中有 `ecshopx-queue-{default,quick,seckill,slow,sms}`、`ecshopx-nuxt`，计划任务有 `schedule:run`
4. 若 Logo / 静态图 404：确认伪静态含 `/images/`、`/assets/` 反代，且 `ecshopx-nuxt` 在跑

## 常见问题

- **仍是 BT_DB_NAME**：部署时未创建/关联数据库；看 `storage/logs/bt-install.log`，手工改 `.env` 后执行迁移。
- **前端 404 / 白屏**：dist 未按 `/admin/`、`/mobile/`、`/web/` 构建；用上文环境变量重编后再 `pack.sh`。
- **伪静态未生效**：确认**站点根目录**有 `nginx.rewrite`，或在面板伪静态中粘贴该文件内容（线上包内无 `baota/` 目录）。
- **队列未起来**：`install.sh` 会 `reread/update` 并显式 `restart` 五个队列；若仍停，看 `supervisorctl status` 与 `storage/logs/supervisor-queue-*.log`。
- **PC `/web/` 502**：多为旧 Node 占 3000 或 `ecshopx-nuxt` 未起来；安装脚本会先释放 3000 再启动，日志见 `storage/logs/supervisor-nuxt.log`。
- **H5 图片 404 / 落到错误域名**：确认打包时注入了 `APP_IMAGE_CDN`（默认阿里云 OSS）；改 CDN 后须 `PACK_FRONTEND_BUILD=true` 重编 H5。
- **Demo 能登但 PC 像空站**：检查 `companys.pc_domain`/`h5_domain` 是否为当前站点域名，以及 Nuxt 的 `NUXT_PUBLIC_COMPANY_ID`。

## 字段规范

`auto_install.json` 依据宝塔一键部署文档（论坛 thread-33063）。
