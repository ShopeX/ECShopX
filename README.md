<p align="center"><img width="600" height="auto" alt="logo1" src="https://github.com/user-attachments/assets/489cc6f9-9108-4db9-860d-70820c99b73a" /></p>

# 
Welcome to the ECShopX Open Source E-Commerce System! ECShopX provides a comprehensive set of e-commerce capabilities to help you build a unique online store from the ground up.

ECShopX is a powerful, multi-language e-commerce platform that allows flexible configuration of various business models, including B2C, B2B2C, Supplier-to-Business-to-Consumer, and Online–Offline Integration.  
It offers a complete front-end solution covering mobile storefronts, desktop storefronts, store operation tools, in-store sales assistant tools, and store delivery tools.

欢迎使用 ECShopX 开源电商系统！ECShopX 开源软件提供丰富的电商能力，帮助您从零搭建一个独特的在线商店。

ECShopX 是一个功能强大的多语言电商平台，支持灵活配置不同商业模式，例如 B2C、B2B2C、S2B2C，O2O，
提供完整的前端解决方案，包括移动端商城、PC端商城，门店店务工具、门店导购工具、门店配送工具。

## Get Started
### System Requirements
 - php >= 7.4
 - lumen = 8.3
 - mysql >= 5.7
 - redis >= 4.0

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

## Project overview
ECShopX adopts a Headless Architecture.

The back-end is built on PHP 7.4 using the Lumen 8.0 micro-service framework.  
The mobile application is developed with the Taro 3.0 framework, while the desktop web application is implemented using Vue.js 2.0.

ECShopX 采用前后端分离的系统架构设计。 

后端基于 PHP 7.4，并构建于 Lumen 8.0 微服务框架之上，具备高性能、轻量化及可扩展的特性。  
移动端应用基于 Taro 3.0 多端统一框架开发，支持主流小程序与移动 H5，PC 端应用基于 Vue.js 2.0 构建，实现组件化、模块化的前端工程体系。


## License
Each ECShopX source file included in this distribution is licensed under the Apache License 2.0, together with the additional terms imposed by ShopeX.

Open Software License (Apache 2.0) – Please see LICENSE.txt for the full text of the Apache 2.0 license.

每个包含在本发行版中的 ECShopX 源文件，均依据 Apache 2.0 开源许可证与ShopeX商派附加条款进行授权。

开源软件许可协议（Apache 2.0） —— 请参阅 LICENSE.txt 文件以获取 Apache 2.0 协议的完整文本。
