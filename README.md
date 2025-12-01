<p align="center"><img width="600" height="416" alt="logo2" src="https://github.com/user-attachments/assets/abf6c4c7-8cb1-477e-aea4-9e526cd8225a" /></p>

# 
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

## 技术架构
* 后端框架：基于高性能PHP框架，采用MVCL分层架构。
* 数据库：MySQL 5.7+
* 缓存支持：Redis / Memcached
* 部署方式：支持传统部署与Docker容器化部署。

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

## 系统要求
- php >= 7.4
- lumen = 8.3
- mysql >= 5.7
- redis >= 4.0
  
## 安装指南
### Configure the .env file
* Update database settings
* Update Redis settings
* Update other settings

### Installation
```
composer install
```

```
cp .env.full .env
```
按需修改您的信息，最小闭环请修改DB REDIS相关信息即可

### Generate APP_KEY
```
php artisan key:generate
```

### Update Database
> The initial login password is 
> admin Shopex123
```
php artisan doctrine:migrations:migrate
```

### Add Language and Initialize Language Environment
> If you don't need to add more languages, you don't need to execute this command;The sample value of {lang} like 'zh-CN' 'en-CN'
```
php artisan lang:init {lang} 
```
#### Vim NGINX Config
> If you use nginx, you can use the following file as a template
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

### Start the Server
Launch using 'php server'
```
php -S 127.0.0.1:9058 -t public
```
## 开发文档
* 官方文档：请访问 [ECShopX官方文档中心] (链接地址)
* API参考：完整的API接口文档。
* 开发者指南：包含扩展开发、主题制作等高级教程。

## 常见问题
1.	安装时提示权限错误？ 请确保 storage/ 和 bootstrap/cache/ 目录具有写权限。
2.	如何新增一种业务模式？ 参考开发者指南中的“模块开发”章节，通过创建新模块实现。

## 许可证
本项目采用 Apache-2.0 开源许可证。

## 支持
* 📖 文档：请首先查阅官方文档。
* 🐛 问题反馈：请在 [GitHub Issues] 中提交。

## 致谢
感谢所有为 ECShopX 做出贡献的开发者、用户以及商派背后的全球品牌客户们！

## License
Each ECShopX source file included in this distribution is licensed under the Apache License 2.0, together with the additional terms imposed by ShopeX.

Open Software License (Apache 2.0) – Please see LICENSE.txt for the full text of the Apache 2.0 license.

每个包含在本发行版中的 ECShopX 源文件，均依据 Apache 2.0 开源许可证与ShopeX商派附加条款进行授权。

开源软件许可协议（Apache 2.0） —— 请参阅 LICENSE.txt 文件以获取 Apache 2.0 协议的完整文本。
