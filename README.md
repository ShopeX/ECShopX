<p align="center"><img width="500" height="auto" alt="logo1" src="https://github.com/user-attachments/assets/097cbde6-cedd-42d0-a2d8-a0a0430289b5" /></p>

# 
Welcome to the ECShopX Open Source E-Commerce System! ECShopX provides a comprehensive set of e-commerce capabilities to help you build a unique online store from the ground up.

ECShopX is a powerful, multi-language e-commerce platform that allows flexible configuration of various business models, including B2C, B2B2C, Supplier-to-Business-to-Consumer, and Online–Offline Integration.  
It offers a complete front-end solution covering mobile storefronts, desktop storefronts, store operation tools, in-store sales assistant tools, and store delivery tools.

If you need a fully integrated, end-to-end e-commerce solution, please contact shopeX.

欢迎使用 ECShopX 开源电商系统！ECShopX 开源软件提供丰富的电商能力，帮助您从零搭建一个独特的在线商店。

ECShopX 是一个功能强大的多语言电商平台，支持灵活配置不同商业模式，例如 B2C、B2B2C、S2B2C，O2O，
提供完整的前端解决方案，包括移动端商城、PC端商城，门店店务工具、门店导购工具、门店配送工具。

如果您需要提供一套完整的电商解决方案，请联系 shopeX。

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
```
php artisan doctrine:migrations:migrate
```

### init shop_menu Data
```
php artisan menu:upload "storage/static/platform_menu.json"
```

### Add Language and Initialize Language Environment
```
php artisan lang:init {lang} 
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
Each ECShopX source file included in this distribution is licensed under Apache 2.0 or the terms and conditions of the applicable Commercial License agreement between Licensee/Customer and shopeX.

Open Software License (Apache 2.0) – Please see LICENSE.txt for the full text of the Apache 2.0 license.

Subject to Licensee's/Customer's payment of fees and compliance with the terms and conditions of the applicable Commercial License agreement between Licensee/Customer and shopeX, the terms and conditions of the applicable Commercial License agreement between Licensee/Customer and shopeX supersede the Apache 2.0 license for each source file.  

每个包含在本发行版中的 ECShopX 源文件，均依据 Apache 2.0 开源许可证 或者 被许可方/客户与商派之间适用的商业授权文件中的条款与条件 进行授权。

开源软件许可协议（Apache 2.0） —— 请参阅 LICENSE.txt 文件以获取 Apache 2.0 协议的完整文本。

在被许可方/客户已支付相关费用并遵守其与shopeX之间适用商业授权文件条款与条件的前提下，
该商业授权文件中的条款与条件将取代 Apache 2.0 协议，成为每个源文件的实际适用许可条款。
