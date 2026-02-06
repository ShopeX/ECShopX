<p align="center"><img width="600" height="auto" alt="logo" src="logo.png" /></p>

# 
<p align="center"><a href="README.md">English</a> / 简体中文</p>
  
一个功能强大、架构灵活的企业级交易上商城平台，原生支持B2C、B2B2C、S2B2C、O2O等10余种商业模式，提供统一后台管理多端商城，助力企业快速构建数字化商业基座。

## 项目介绍
ECShopX 是商派基于23年服务全球知名品牌企业的经验沉淀，推出的开源商城系统。它采用模块化架构，支持灵活扩展和个性化定制开发，为企业提供从商品、订单、会员、营销到财务结算的全链路官方商城解决方案。

## 适用场景
* B2C品牌私域商城：构建官方小程序、APP、PC官网、H5等多端DTC商城。
* B2C员工内购福利平台：支持多品牌集团开展“员工&亲友内购业务”。
* BBC多商户平台：打造类似京东、美团的“自营+多商户入驻”模式的B2B2C在线平台。
* SBC供应链协同：建立连接品牌、经销商与终端门店的S2B2C供应链平台。
* O2O品牌云店+即时零售：O2O品牌云店可实现线上线下一体化的商品、会员、营销与库存管理；支持线上下单，附近门店自提和即时配送等场景。
* O2O经销商云店：专为品牌企业赋能经销商开展线上业务的解决方案。该系统通过搭建统一平台，聚合所有经销商门店资源，实现"线上下单、门店发货/自提"的O2O模式。核心价值在于打通品牌-经销商-消费者的全链路业务场景。

## 核心特性
### 多模式商城
* 统一后台管理：一套系统统一管理B2C、B2B2C、S2B2C等多种业务模式。
* 全渠道多端适配：无缝支持小程序、APP、H5、PC端，数据完全同步。
* 多租户隔离：支持平台内多商户独立运营，数据与权限严格隔离。

### 商品、订单与营销
* 店铺管理：展示当前店铺信息店铺名称、地址、店铺号、是否自提、是否快递配送、商家自配送、店铺状态；支持店铺码、店铺支付配置、店铺装修
* 商品管理：展示商品标题、SKU编码、是否赠品、商品类型、库存、市场价、销售价、店铺销售状态，上下架状态、销售分类；批量修改
* 订单管理：支持按待支付、待发货、待退款、待自提、已取消、已完成等状态快速筛选订单；
* 智能营销：内置优惠券、积分、会员等级、拼团、秒杀等多种营销工具。
* 内容管理：内置图文视频种草社区，打造私域专属“小红书”
* 模版管理：支持丰富的自定义场景与行业模版

### 会员与权限
* 统一会员：打通各端会员体系，实现积分、等级、资产全域通用。
* 精细化权限：支持平台方、供应商、门店、导购等多角色精细化权限控制。

### 系统集成与扩展
* 开放API：提供丰富的RESTful API，便于与ERP、WMS、CRM等第三方系统集成。
* 模块化设计：核心功能高度模块化，便于二次开发与功能扩展。

## Docker 部署方式（推荐）

使用自动化部署脚本快速搭建开发环境，包含 PHP、Nginx、MySQL、Redis 等服务。

### 前置要求

- 软件：Docker 和 Docker Compose（macOS 推荐 Docker Desktop）
- 系统：macOS 或 Linux 系统

### 快速开始
- 克隆项目到本地
- 进入根目录，运行部署脚本：

```bash
bash dev-setup.sh
```
- 依据脚本提示部署系统  

脚本会自动完成：
1. 检测 Docker 环境
2. 克隆前端项目（如不存在）
3. 启动 Docker 容器（PHP、Nginx、MySQL、Redis）
4. 配置 PHP 应用（安装依赖、数据库迁移、初始化管理员密码）
5. 编译前端项目（管理后台、H5、PC商城）

### 脚本选项

```bash
bash dev-setup.sh --rebuild    # 重新构建镜像
bash dev-setup.sh --skip-admin # 跳过管理后台编译
bash dev-setup.sh --skip-vshop # 跳过移动商城编译
bash dev-setup.sh --skip-pc    # 跳过PC商城编译
```

### 访问地址

- **管理后台**: http://localhost:8080
- **H5前端**: http://localhost:8081
- **PC前端**: http://localhost:8082
- **API接口**: http://localhost:8080/api/

### 常用命令

```bash
# 查看日志
docker-compose -f docker-compose.dev.yml logs -f

# 查看服务状态
docker exec ecshopx-dev supervisorctl status

# 进入容器
docker exec -it ecshopx-dev sh

# 停止服务
docker-compose -f docker-compose.dev.yml down

# 重启服务
docker-compose -f docker-compose.dev.yml restart
```

### 注意事项

- 首次部署需要 10-30 分钟（下载镜像、安装依赖、编译前端）
- 前端项目会克隆到与当前项目同级的目录
- 脚本运行时会提示设置管理员密码
- 确保端口 8080、8081、8082、3306、6379 未被占用

## 自行部署

### 系统要求
- php >= 7.4
- lumen = 8.3
- mysql >= 5.7
- redis >= 4.0

### 配置 .env 文件
* Update database settings
* Update Redis settings
* Update other settings

### 安装
```
composer install
```

```
cp .env.full .env
```
按需修改您的信息，最小闭环请修改DB REDIS相关信息即可

### 生成 APP_KEY
```
php artisan key:generate
```

### 更新数据库
> The initial login password is 
> admin Shopex123
```
php artisan doctrine:migrations:migrate
```

### 添加语言包并初始化语言环境
> If you don't need to add more languages, you don't need to execute this command;The sample value of {lang} like 'zh-CN' 'en-CN'
```
php artisan lang:init {lang} 
```
#### NGINX 配置模板
> 如果您使用 nginx，则可以使用以下文件作为模板。
```
server {
    listen 80;
    #{need fix A}  hostname
    server_name opendemo.test;
    #{need fix B}  The compiled code is below dist/
    set $frontend_dir /Users/kris/data/httpd/ecx/product/github.com/demo/ECShopX_admin-frontend/dist/;

    location /api/ {
        access_log /usr/local/etc/nginx/log/ecx.test.log;
        proxy_pass http://localhost:8005;
        proxy_set_header        Host $host;
        proxy_set_header        X-Real-IP $remote_addr;
        proxy_set_header        X-Forwarded-For                $proxy_add_x_forwarded_for;
        client_max_body_size    32m;
        client_body_buffer_size 256k;
    }
    location /storage/ {
        access_log /usr/local/etc/nginx/log/ecx.test.log;
        proxy_pass http://localhost:8005;
        proxy_set_header        Host $host;
        proxy_set_header        X-Real-IP $remote_addr;
        proxy_set_header        X-Forwarded-For $proxy_add_x_forwarded_for;
        client_max_body_size    32m;
        client_body_buffer_size 256k;
    }

    location /wechatAuth/ {
        proxy_pass http://localhost:8005;
        proxy_set_header        Host $host;
        proxy_set_header        X-Real-IP $remote_addr;
        proxy_set_header        X-Forwarded-For $proxy_add_x_forwarded_for;
        client_max_body_size    32m;
        client_body_buffer_size 256k;
    }

    location / {
        root  $frontend_dir;
        index  index.html index.htm;
        try_files $uri $uri/ /index.html =404;
        client_max_body_size    32m;
    }

}

server {
    client_max_body_size    32m;

    listen 8005;

    #{need fix A}  hostname
    server_name opendemo.test;

    #{need fix C}  The path of the backend code goes to /public 
    set $backend_dir /Users/kris/data/httpd/ecx/product/github.com/demo/ECShopX/public;


    root  $backend_dir;

    location / {
        client_max_body_size    32m;
        try_files $uri $uri/ /index.php$is_args$args;
    }

    set $real_script_name $request_filename;

    if ($request_filename ~ "^(.+?\.php)/.+$") {
        set $real_script_name $1;
    }

    if (!-e $real_script_name) {
        rewrite ^/(.*)$ /index.php/$1 last;
    }

    location ~ \.php$ {
	client_max_body_size  32m;
        #add_header Access-Control-Allow-Origin *;
        add_header 'Access-Control-Allow-Origin' '*' always;
        add_header Access-Control-Allow-Headers "Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With";
        add_header Access-Control-Expose-Headers "Authorization";
        add_header Access-Control-Allow-Methods "DELETE, GET, HEAD, POST, PUT, OPTIONS, TRACE, PATCH";
        access_log /usr/local/etc/nginx/log/espier-xxx.log;
        if ($request_method = OPTIONS ) {
            return 200;
        }

        fastcgi_pass 127.0.0.1:9074;
        fastcgi_read_timeout 150;
        fastcgi_index index.php;
        fastcgi_buffers 4 128k;
        fastcgi_buffer_size 128k;
        fastcgi_busy_buffers_size 128k;
        fastcgi_temp_file_write_size 256k;
        #fastcgi_temp_path /dev/shm;
        fastcgi_param SCRIPT_FILENAME      $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### 启动服务
Launch using 'php server'
```
php -S 127.0.0.1:9058 -t public
```

### 系统初始管理员账号密码
用户名：admin  
密码: Shopex123

## 许可证
本项目采用 Apache-2.0 开源许可证。  
每个包含在本发行版中的 ECShopX 源文件，均依据 Apache 2.0 开源许可证进行授权。

开源软件许可协议（Apache 2.0） —— 请参阅 LICENSE.txt 文件以获取 Apache 2.0 协议的完整文本。

## 贡献 
我们欢迎所有形式的贡献！
请阅读 [CONTRIBUTING.md](CONTRIBUTING.md) 了解如何参与。

## 支持
* 文档：请首先查阅[官方文档](https://doc.shopex.cn/ecshopx/docs/readme.html)
* 🐛 问题反馈：请在 [Issues] 中提交。

## 致谢
感谢所有为 ECShopX 做出贡献的开发者、用户以及商派背后的全球品牌客户们！

