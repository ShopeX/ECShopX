<?php

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

// $app = new Laravel\Lumen\Application(
//     dirname(__DIR__)
// );
$app = new AppKernel(
    realpath(__DIR__.'/../')
);

$app->withFacades();

// $app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    EspierBundle\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
|
| Now we will register the "app" configuration file. If the file exists in
| your configuration directory it will be loaded; otherwise, we'll load
| the default version. You may register other files below as needed.
|
*/

$app->configure('adapay');
$app->configure('api');
$app->configure('alipay');
$app->configure('app');
$app->configure('bank');
$app->configure('common');
$app->configure('crm');
$app->configure('licensegateway');
$app->configure('openapi');
$app->configure('order');
$app->configure('requestField');
$app->configure('requestFieldShuyun');
$app->configure('requestFieldzhcn');
$app->configure('requestFieldencn');
$app->configure('services');
$app->configure('sms');
$app->configure('trustlogin');
$app->configure('websocketServer');
$app->configure('workwechat');
$app->configure('wxa');
$app->configure('bspay');
$app->configure('wdterp');
$app->configure('jushuitan');
$app->configure('kafka');
$app->configure('oms');
$app->configure('shopexai');
$app->configure('tbitems');
$app->configure('paypal');

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

// $app->middleware([
//     App\Http\Middleware\ExampleMiddleware::class
// ]);


$app->middleware([
    App\Http\Middleware\SetLocaleMiddleware::class
]);

$app->routeMiddleware([
    'token' => EspierBundle\Middleware\ApiToken::class,
    'activated' => EspierBundle\Middleware\CheckActivedMiddleWare::class,
    'dingoguard' => EspierBundle\Middleware\DingoGuardMiddleWare::class, //  小程序、pc、h5验证中间件
    'apicache' => EspierBundle\Middleware\ApiCacheMiddleWare::class, // api缓存中间件
    'frontnoauth' => EspierBundle\Middleware\FrontNoAuthMiddleWare::class, // 小程序、pc、h5无需验证中间件
    'frontmerchantauth' => EspierBundle\Middleware\FrontMerchantAuthMiddleWare::class, // h5商户验证中间件
    'ShopexErpCheck' => SystemLinkBundle\Middleware\ShopexErpCheck::class, // erp连接中间件
    'superguard' => EspierBundle\Middleware\SuperAccountGuardMiddleWare::class,
    'shoplog' => EspierBundle\Middleware\ShopLogMiddleWare::class, // 商家操作日志中间件
    'distributorlog' => EspierBundle\Middleware\DistributorLogMiddleWare::class, // 店铺日志中间件
    'shoplogin' => EspierBundle\Middleware\ShopLoginMiddleWare::class, // 商家操作日志中间件
    'servicesign' => EspierBundle\Middleware\ServiceSignMiddleWare::class, // 接口签名中间件
    'ShopexSaasErpCheck' => ThirdPartyBundle\Middleware\ShopexSaasErpCheck::class, // saaserp中间件
    'SystemLinkOpenapiCheck' => SystemLinkBundle\Middleware\OpenApiCheck::class,
    'OpenapiCheck' => OpenapiBundle\Middleware\OpenapiCheck::class, // openapi中间件
    'DadaApiCheck' => ThirdPartyBundle\Middleware\DadaApiCheck::class, // 达达配送中间件
    'handleResponse' => OpenapiBundle\Middleware\HandleResponseMiddleware::class, // openapi返回信息中间件
    'OpenapiCommonCheck' => OpenapiBundle\Middleware\OpenapiCommonCheck::class,
    'datapass' => EspierBundle\Middleware\DataPassMiddleWare::class, // 达达日志中间件
    'JushuitanCheck' => SystemLinkBundle\Middleware\JushuitanCheck::class,// 聚水潭中间件
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

// 第三方provider
if (env('DISK_DRIVER') == 'aws') {
    $app->register(Aws\Laravel\AwsServiceProvider::class); // 亚马逊云
}
$app->register(Digbang\SafeQueue\DoctrineQueueProvider::class);
$app->loadComponent('filesystems', 'Illuminate\Filesystem\FilesystemServiceProvider', 'filesystem');
$app->register(Illuminate\Redis\RedisServiceProvider::class); // redis
$app->register(Milon\Barcode\BarcodeServiceProvider::class); // 条形码
$app->register(Maatwebsite\Excel\ExcelServiceProvider::class); // excel
$app->register(Overtrue\LaravelFilesystem\Qiniu\QiniuStorageServiceProvider::class); // 七牛
//$app->register(Freyo\Flysystem\QcloudCOSv5\ServiceProvider::class); // 腾讯云cos
$app->register(\EspierBundle\Services\CosSdk\CosServiceProvider::class); // 腾讯云cos
if (env('SENTRY_LARAVEL_DSN')) {
    $app->register(Sentry\Laravel\ServiceProvider::class); // sentry
    $app->register(Sentry\Laravel\Tracing\ServiceProvider::class); // sentry
}
$app->register(Tymon\JWTAuth\Providers\LumenServiceProvider::class); // jwt
if (env('QUEUE_DRIVER') == 'rabbitmq') {
    $app->register(VladimirYuldashev\LaravelQueueRabbitMQ\LaravelQueueRabbitMQServiceProvider::class); // rabbitmq
}

// 业务bundle中provider
$app->register(CompanysBundle\Providers\EventServiceProvider::class); // 公司账号相关事件
$app->register(DistributionBundle\Providers\EventServiceProvider::class); // 店铺相关事件
$app->register(EspierBundle\Providers\AlipayServiceProvider::class); // 支付宝
$app->register(EspierBundle\Providers\AuthorizationActivationProvider::class); // 激活
if (env('DISK_DRIVER') == 'aws') {
    $app->register(EspierBundle\Providers\AwsStorageServiceProvider::class); // 亚马逊云存储
}
$app->register(EspierBundle\Providers\DingoServiceProvider::class); // dingo api
// $app->register(Dingo\Api\Provider\LumenServiceProvider::class);
$app->register(EspierBundle\Providers\EasyWechatServiceProvider::class); // easywechat微信插件
$app->register(EspierBundle\Providers\EventServiceProvider::class); // 通用事件
$app->loadComponent('app', 'EspierBundle\Providers\FixedEncrypterRWProvider', 'fixedencrypt'); // 数据加密
$app->register(EspierBundle\Providers\FixedEncrypterRWProvider::class); // 数据加密
$app->register(EspierBundle\Providers\JwtAuthServiceProvider::class);// @todo 性能超级差
$app->register(EspierBundle\Providers\LaravelDoctrineServiceProvider::class);
$app->register(EspierBundle\Providers\LocalStorageServiceProvider::class); // 本地存储
$app->register(EspierBundle\Providers\OssStorageServiceProvider::class); // 阿里OSS存储
if (!in_array(env('APP_ENV', 'local'), ['production', 'staging'])) {
    $app->register(Espier\Swagger\Providers\SwaggerServiceProvider::class); // @todo,需要适配
}
$app->register(EspierBundle\Providers\ValidateServiceProvider::class); // 输入验证
$app->register(EspierBundle\Providers\WorkWechatServiceProvider::class); // 企业微信
$app->register(EspierBundle\Providers\WebsocketServiceProvider::class); // websocket
$app->register(EspierBundle\Providers\WxaTemplateMsgServiceProviders::class); // 小程序消息
$app->register(Espier\Swoole\Providers\ServerServiceProvider::class); // swoole
$app->register(GoodsBundle\Providers\GoodsBundleProvider::class); // 商品内部注册
$app->register(GoodsBundle\Providers\EventServiceProvider::class); // 商品相关事件
$app->register(HfPayBundle\Providers\EventServiceProvider::class); // 汇付相关事件
$app->register(MembersBundle\Providers\EventServiceProvider::class); // 会员相关事件
$app->register(OpenapiBundle\Providers\EventServiceProvider::class); // 开放API相关事件
$app->register(OrdersBundle\Providers\EventServiceProvider::class); // 订单相关事件
$app->register(ReservationBundle\Providers\EventServiceProvider::class); // 预约相关事件
$app->register(SystemLinkBundle\Providers\EventServiceProvider::class); // 第三方对接相关事件
$app->register(ThirdPartyBundle\Providers\EventServiceProvider::class); // 第三方对接相关事件
$app->register(ThirdPartyBundle\Providers\CommandServiceProvider::class); // 第三方对接相关命令
$app->register(WechatBundle\Providers\EventServiceProvider::class); // 微信相关事件
$app->register(YoushuBundle\Providers\EventServiceProvider::class); // 腾讯有数相关事件
$app->register(ChinaumsPayBundle\Providers\UmsServiceProvider::class);
$app->register(SystemLinkBundle\Providers\WdtErpServiceProvider::class); // 旺店通相关事件
$app->register(SystemLinkBundle\Providers\JushuitanServiceProvider::class);// 聚水潭相关事件
$app->register(ShopexAIBundle\Providers\ShopexAIServiceProvider::class); // ShopexAI服务

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

// $app->router->group([
//     'namespace' => 'App\Http\Controllers',
// ], function ($router) {
//     require __DIR__.'/../routes/web.php';
// });
// 路由改为通过 app/AppKernel.php 中引入路由

return $app;
