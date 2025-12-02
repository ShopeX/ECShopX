<p align="center"><img width="600" height="416" alt="logo2" src="https://github.com/user-attachments/assets/abf6c4c7-8cb1-477e-aea4-9e526cd8225a" /></p>

# 
<p align="center">English / <a href="README_cn.md">简体中文</a></p>
  
A powerful and flexibly architected enterprise-grade trading marketplace platform, natively supporting over 10 business models including B2C, B2B2C, S2B2C, and O2O. It provides a unified backend to manage multi-storefront operations, empowering businesses to rapidly build their digital commerce foundation.

## Project Overview
ECShopX is an open-source e-commerce system developed by ShopeX, leveraging 23 years of practical experience in digital project implementation. Built on a modular architecture, it enables flexible scalability and personalized customization, offering enterprises a comprehensive official commerce solution that spans the entire business process—from product and order management to membership, marketing, and financial settlement.

## Use Cases
* B2C Brand Private Domain Mall: Build a multi-terminal DTC (Direct-to-Consumer) mall covering official Mini Programs, apps, PC official websites, H5, etc.
* B2C Employee Purchase & Welfare Platform: Enable multi-brand groups to launch "employee & friends-and-family internal purchase businesses".
* B2B2C Multi-Merchant Platform: Create an online B2B2C platform with the "self-operated + merchant settlement" model, similar to JD.com and Meituan.
* S2B2C Supply Chain Collaboration: Establish an S2B2C supply chain platform connecting brands, distributors, and terminal stores.
* O2O Brand Cloud Store + Instant Retail: The O2O brand cloud store enables integrated online-offline management of commodities, members, marketing, and inventory; it supports scenarios such as online ordering, click-and-collect at nearby stores, and instant delivery.
* O2O Distributor Cloud Store: A mall platform solution for brand owners to empower distributors in developing online O2O businesses.

## Core Features
### Multi-Mode E-commerce Mall
* Unified Backend Management: A single system to uniformly manage various business models such as B2C, B2B2C, and S2B2C.
* Omni-Channel & Multi-Terminal Adaptation: Seamlessly supports Mini Programs, APPs, H5 pages, and PC endpoints with fully synchronized data across all platforms.
* Multi-Tenant Isolation: Supports independent operations for multiple merchants within the platform, ensuring strict data and permission isolation.

### Products, Orders, and Marketing
* Store Management：Display current store information including store name, address, store number, self-pickup availability, express delivery availability, merchant self-delivery, and store status; support store code, store payment configuration, and store decoration.
* Product Management：Display product title, SKU code, gift item status, product type, inventory, market price, selling price, store sales status, shelving status, and sales category; support batch modification.
* Order Management：Support quick filtering of orders by statuses such as pending payment, pending delivery, pending refund, pending pickup, cancelled, and completed.
* Intelligent Marketing：Built-in multiple marketing tools including coupons, points, member levels, group buying, and flash sales.
* Content Management：Built-in image, text and video grass-planting community to create a private domain-exclusive "Xiaohongshu".
* Template Management：Support a wealth of custom scenarios and industry templates.

### Membership and Permissions
* Unified membership marketing: Integrate membership systems across all channels to enable universal use of points, tiers, and assets throughout the entire ecosystem.
* Fine-grained permission management: Support detailed access control for multiple roles such as platform operators, suppliers, stores, and sales advisors.

### System Integration and Expansion
* Open API: Provide a rich set of RESTful APIs to facilitate integration with third-party systems such as ERP, WMS, CRM, etc.
* Modular design: The core functions are highly modular, making it easy to perform secondary development and extend functionalities.

## System Requirements
- php >= 7.4
- lumen = 8.3
- mysql >= 5.7
- redis >= 4.0
  
## Installation Guide
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
Adjust your info as needed; for a minimal closed loop, just modify the DB and Redis settings.

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
### NGINX Config Template
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

## License
This project is licensed under the Apache-2.0 open-source license.  
Each ECShopX source file included in this distribution is licensed under the Apache License 2.0, together with the additional terms imposed by ShopeX.

Open Software License (Apache 2.0) – Please see LICENSE.txt for the full text of the Apache 2.0 license.

## Contribution 
We welcome contributions in all forms! Please read [CONTRIBUTING.md](CONTRIBUTING.md) to learn how to participate.

## Support
* Documentation: Please refer to the [official documentation](https://op.shopex.cn/doc_ecshopx_dev/docs/) first.
* 🐛 Issue Reporting: Submit via [GitHub Issues].

## Acknowledgments
Thank you to all developers and users who have contributed to ECShopX!
