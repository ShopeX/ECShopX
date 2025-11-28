<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */

/* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ taro小程序、h5、app端、pc端 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
$api->version('v1', function ($api) {
    $api->group(['prefix' => 'h5app', 'namespace' => 'CompanysBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {
        // 获取微信门店列表-已支持h5
        $api->get('/wxapp/shops/wxshops',              ['name' => '获取微信门店列表','as' => 'front.shops.lists',          'uses' => 'Shops@getWxShopsList']);
        // 获取单个微信门店详情-已支持h5
        $api->get('/wxapp/shops/wxshops/{wx_shop_id}', ['name' => '获取单个微信门店详情','as' => 'front.shops.detail',         'uses' => 'Shops@getWxShopsDetail']);
        $api->get('/wxapp/shops/getNearestWxShops',    ['name' => '获取最近门店','as' => 'front.shops.nearestwxshops', 'uses' => 'Shops@getNearestWxShops']);
        $api->get('/wxapp/shops/info', ['name' => '获取站点的基本信息','as' => 'front.shops.info', 'uses' => 'Shops@getBaseInfo']);
        $api->get('/wxapp/shops/protocol', ['name' => '获取站点的协议信息','as' => 'front.shops.protocol', 'uses' => 'ProtocolController@get']);
        $api->get('/wxapp/shops/protocolUpdateTime', ['name' => '获取站点的协议发布时间','as' => 'front.shops.protocolupdatetime', 'uses' => 'ProtocolController@getUpdateTime']);

        $api->get('/wxapp/shops/protocolsaleman', ['name' => '获取业务员协议信息','as' => 'front.shops.protocolsaleman', 'uses' => 'ProtocolController@protocolsaleman']);

    });

        //文章相关接口
    $api->group(['prefix' => 'h5app', 'namespace' => 'CompanysBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {
        // 文章列表-已支持h5
        $api->get('/wxapp/article/management',              ['name' => '文章列表','as' => 'article.list', 'uses'=>'ArticleController@listDataArticle']);
        // 文章详情-已支持h5
        $api->get('/wxapp/article/management/{article_id}', ['name'=>'文章详情','as' => 'article.info', 'uses'=>'ArticleController@infoDataArticle']);
        // 文章关注
        $api->get('/wxapp/article/focus/{article_id}', [ 'name' => '文章关注','as' => 'article.list', 'uses'=>'ArticleController@articleFocus']);
        // 获取文章关注数量
        $api->get('/wxapp/article/focus/num/{article_id}', [ 'name' => '文章关注总数量','as' => 'article.list', 'uses'=>'ArticleController@ArticleFocusNum']);
        // 文章点赞数量获取
        $api->get('/wxapp/article/praise/num/{article_id}', [ 'name' => '文章点赞总数','as' => 'article.list', 'uses'=>'ArticleController@articlePraiseNum']);
        // 获取文章栏目
        $api->get('/wxapp/article/category', [ 'name' => '获取文章栏目列表','as' => 'article.category.list', 'uses'=>'ArticleController@getCategory']);
        // 获取文章的所有省份列表
        $api->get('/wxapp/article/province', [ 'name' => '获取文章的所有省份列表','as' => 'article.province.result', 'uses'=>'ArticleController@getAllProvince']);
    });

    //需验证接口
    $api->group(['prefix' => 'h5app', 'namespace' => 'CompanysBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        // 文章点赞
        $api->get('/wxapp/article/praise/{article_id}', [ 'name' => '文章点赞','as' => 'article.list', 'uses'=>'ArticleController@articlePraise']);
        // 文章点赞验证
        $api->get('/wxapp/article/praise/check/{article_id}', [ 'name' => '文章点赞验证','as' => 'article.list', 'uses'=>'ArticleController@articlePraiseCheck']);
        //获取文章列表接口
        $api->get('/wxapp/article/usermanagement',              ['name' => '文章列表','as' => 'article.list', 'uses'=>'ArticleController@listDataArticle']);
        //获取文章详情接口
        $api->get('/wxapp/article/usermanagement/{article_id}', ['name' => '文章详情','as' => 'article.info', 'uses'=>'ArticleController@infoDataArticle']);
        // 文章列表点赞数量和点赞状态
        $api->get('/wxapp/article/praises/getcountresult', [ 'name' => '批量获取文章点赞数量和点赞状态','as' => 'article.praise.result', 'uses'=>'ArticleController@getArticlePraiseData']);

        // 检查用户是否绑定店务
        $api->get('/wxapp/distributor/bind/checkout', [ 'name' => '检查用户是否绑定店务','as' => 'distributor.bind.checkout', 'uses'=>'DistributorController@checkDistributor']);
        // 检查用户是否是配送员
        $api->get('/wxapp/distributor/deliverystaff/checkout', [ 'name' => '检查用户是否是配送员','as' => 'distributor.deliverystaff.checkout', 'uses'=>'DistributorController@checkDeliveryStaff']);
    });

    $api->group(['prefix' => 'h5app', 'namespace' => 'CompanysBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {
        // 获取默认货币配置-已支持h5
        $api->get('/wxapp/currencyGetDefault', ['as' => 'currency.default', 'uses'=>'CurrencyController@getDefaultCurrency']);
    });

     //商城相关接口
    $api->group(['prefix' => 'h5app', 'namespace' => 'CompanysBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {
        $api->get('/wxapp/company/setting', [ 'name' => '获取商城配置信息','as' => 'company.setting', 'uses'=>'CompanyController@getCompanySetting']);
        $api->get('/wxapp/setting/weburl', ['name' => '获取配置外部链接', 'as' => 'company.setting.weburl.get', 'uses'=>'CompanyController@getWebUrlSetting']);
        $api->get('/wxapp/company/logistics/list', ['name'=>'获取公司启用物流列表', 'as' => 'company.logistics.list',  'uses'=>'CompanyController@getCompanyLogisticsList']);
        //获取评价配置状态
        $api->get('/traderate/getstatus', ['name'=>'获取评价状态','as' => 'front.wxapp.trade.rate.status', 'uses' => 'CompanyController@getRateSettingStatus']);
        // 非自提无店铺状态获取
        $api->get('/wxapp/nostores/getstatus', ['as' => 'front.wxapp.nostores.status', 'uses' => 'CompanyController@getNostoresStatus']);
        //获取公司启用的物流列表
        $api->get('/wxapp/company/logistics/enableList', ['name'=>'获取公司启用的物流列表', 'as' => 'company.logistics.enableList',  'uses'=>'CompanyController@getLogisticsEnableList']);
        //获取商品价格显示设置
        $api->get('/wxapp/setting/itemPrice', [ 'name' => '获取商品价格显示设置','as' => 'company.setting.itemPrice.get', 'uses'=>'CompanyController@getItemPriceSetting']);
        //获取pc和h5隐私
        $api->get('/wxapp/company/privacy_setting_ck', ['name' => '获取pc和h5隐私', 'as' => 'company.privacy.setting.get', 'uses'=>'CompanyController@getPrivacySetting']);
    });

    $api->group(['prefix' => 'h5app', 'namespace' => 'CompanysBundle\Http\FrontApi\V1\Action'], function ($api) {
        $api->get('/wxapp/shops/setting', ['name' => '配置门店通用配置信息', 'as' => 'front.shops.setting.get', 'uses'=>'Shops@getWxShopsSetting']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app端、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
