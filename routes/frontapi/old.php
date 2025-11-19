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

// 老的小程序包括，一普的小程序，用的wepy写的，暂时保留

/* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ 微信小程序端接口 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
$api->version('v1', function ($api) {
    // 小程序登录
    $api->group(['namespace' => 'WechatBundle\Http\FrontApi\V1\Action'], function ($api) {
        $api->post('/wxapp/login', [ 'as' => 'front.wxapp.login',  'uses'=>'Wxapp@checkLogin']);
    });

    $api->group(['namespace' => 'EspierBundle\Http\FrontApi\V1\Action'], function ($api) {
    // 上传相关
        $api->get('/wxapp/espier/image_upload_token', ['middleware' => ['dingoguard:wechat', 'api.auth'], 'providers' => 'wxapp', 'as' => 'espier.wxapp.image.uptoken.get',  'uses'=>'UploadFile@getPicUploadToken']);
        $api->get('/wxapp/espier/address', ['middleware' => ['dingoguard:wechat', 'api.auth'], 'providers' => 'wxapp', 'as' => 'espier.wxapp.address.get',  'uses'=>'AddressController@get']);
        $api->post('/wxapp/espier/upload', ['middleware' => ['dingoguard:wechat', 'api.auth'], 'providers' => 'wxapp', 'as' => 'espier.wxapp.upload',  'uses'=>'UploadFile@uploadImage']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ 微信小程序端接口 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */

/* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ 微信小程序端接口 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
$api->version('v1', function ($api) {
    $api->group(['namespace' => 'KaquanBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:wechat', 'api.auth'], 'providers' => 'wxapp'], function ($api) {
        $api->get('/wxapp/user/receiveCard',   ['as' => 'front.wxapp.user.get.card', 'uses' => 'UserDiscount@receiveCard']);
    });
});

/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ 微信小程序端接口 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */

/* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ 微信小程序端接口 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
$api->version('v1', function ($api) {
    $api->group(['namespace' => 'CompanysBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:wechat'], function ($api) {
        $api->get('/wxapp/shops/wxshops',              ['as' => 'front.shops.lists',          'uses' => 'Shops@getWxShopsList']);
        $api->get('/wxapp/shops/wxshops/{wx_shop_id}', ['as' => 'front.shops.detail',         'uses' => 'Shops@getWxShopsDetail']);
        $api->get('/wxapp/shops/getNearestWxShops',    ['as' => 'front.shops.nearestwxshops', 'uses' => 'Shops@getNearestWxShops']);
    });
});

$api->version('v2', function ($api) {
    $api->group(['namespace' => 'CompanysBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:wechat'], function ($api) {
        $api->get('/wxapp/shops/wxshops',              ['as' => 'front.shops.lists',          'uses' => 'Shops@getWxShopsList']);
        $api->get('/wxapp/shops/wxshops/{wx_shop_id}', ['as' => 'front.shops.detail',         'uses' => 'Shops@getWxShopsDetail']);
        $api->get('/wxapp/shops/getNearestWxShops',    ['as' => 'front.shops.nearestwxshops', 'uses' => 'Shops@getNearestWxShops']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ 微信小程序端接口 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */

/* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ 微信小程序端接口 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
$api->version('v1', function ($api) {
    $api->group(['namespace' => 'DataCubeBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:wechat', 'api.auth'], 'providers' => 'wxapp'], function ($api) {
        $api->post('/wxapp/track/viewnum',    ['as' => 'front.wxapp.track.viewnum', 'uses' => 'Track@addViewNum']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ 微信小程序端接口 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */

/* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ 微信小程序端接口 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
$api->version('v1', function ($api) {
    // 企业相关信息
    $api->group(['namespace' => 'DepositBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:wechat', 'api.auth'], 'providers' => 'wxapp'], function ($api) {
        $api->post('/weapp/deposit/recharge', ['as' => 'front.wxapp.deposit.recharge',  'uses'=>'Recharge@recharge']);
        $api->get('/weapp/deposit/rechargerules', ['as' => 'front.wxapp.deposit.rechargerules',  'uses'=>'Recharge@getRechargeRuleList']);
        $api->get('/weapp/deposit/recharge/agreement', [ 'as' => 'front.wxapp.deposit.recharge.agreement',  'uses'=>'Recharge@getRechargeAgreementByCompanyId']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ 微信小程序端接口 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */

/* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ 微信小程序端接口 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
$api->version('v1', function ($api) {
    $api->group(['namespace' => 'GoodsBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:wechat' ], function ($api) {
        $api->get('/wxapp/goods/items',               ['as' => 'goods.items.lists',  'uses' => 'Items@getItemsList']);
        $api->get('/wxapp/goods/items/{item_id}',     ['as' => 'goods.items.detail', 'uses' => 'Items@getItemsDetail']);
    });
});

$api->version('v2', function ($api) {
    // 服务类商品相关信息
    $api->group(['namespace' => 'GoodsBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:wechat' ], function ($api) {
        $api->get('/wxapp/goods/items',               ['as' => 'goods.items.lists',  'uses' => 'Items@getItemsList']);
        $api->get('/wxapp/goods/items/{item_id}',     ['as' => 'goods.items.detail', 'uses' => 'Items@getItemsDetail']);
    });
});

/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ 微信小程序端接口 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */

/* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ 微信小程序端接口 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
$api->version('v1', function ($api) {
    // 会员相关信息
    $api->group(['namespace' => 'MembersBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:wechat','api.auth'], 'providers' => 'wxapp'], function ($api) {
        $api->get('/wxapp/member',  ['as' => 'front.wxapp.member.info',  'uses' => 'Members@getMemberInfo']);
        $api->put('/wxapp/member',  ['as' => 'front.h5app.member.update',  'uses' => 'Members@updateMember']);
        $api->get('/wxapp/member/setting',  ['as' => 'front.wxapp.member.setting',  'uses' => 'Members@getRegSetting']);
        $api->get('/wxapp/member/agreement',  ['as' => 'front.wxapp.member.agreement',  'uses' => 'Members@getRegAgreementSetting']);
        $api->post('/wxapp/member',  ['as' => 'front.wxapp.member.create',  'uses' => 'Members@creatMember']);
        $api->get('/wxapp/barcode', ['as' => 'front.wxapp.barcode',      'uses' => 'Members@getBarcode']);
        $api->get('/wxapp/member/decryptPhoneInfo', ['as' => 'front.wxapp.member.decryptPhoneInfo',      'uses' => 'Members@getDecryptPhoneNumber']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ 微信小程序端接口 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */

/* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ 微信小程序端接口 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
$api->version('v1', function ($api) {
    // 订单权益信息
    $api->group(['namespace' => 'OrdersBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:wechat', 'api.auth'], 'providers' => 'wxapp'], function ($api) {
        // 订单相关
        $api->post('/wxapp/order', ['as' => 'front.wxapp.order.create',  'uses'=>'WxappOrder@createOrder']);
        $api->get('/wxapp/orders',  ['as' => 'front.wxapp.order.list',  'uses'=>'WxappOrder@getOrderList']);
        $api->get('/wxapp/groupOrders',  ['as' => 'front.wxapp.grouporder.list',  'uses'=>'WxappOrder@getGroupOrderList']);
        $api->get('/wxapp/groupOrders/{teamId}',  ['providers' => 'wxapp', 'as' => 'front.wxapp.grouporder.info',  'uses'=>'WxappOrder@getGroupOrderDetail']);
        $api->get('/wxapp/orders/count', ['as' => 'front.wxapp.orders.count', 'uses'=>'WxappOrder@countOrderAndRightsLog']);
        // 权益相关
        $api->get('/wxapp/rights',                 ['as' => 'front.wxapp.rights.list', 'uses'=>'Rights@getRightsList']);
        $api->get('/wxapp/rightsLogs',             ['as' => 'front.wxapp.rightslogs.list', 'uses'=>'Rights@getRightsLogList']);
        $api->get('/wxapp/rights/{rights_id}',     ['as' => 'front.wxapp.rights.info', 'uses'=>'Rights@getRightsDetail']);
        $api->get('/wxapp/rightscode/{rights_id}', ['as' => 'front.wxapp.rights.code', 'uses'=>'Rights@getRightsCode']);
    });
});

$api->version('v2', function ($api) {
    // 根据小程序id不需要授权请求
    $api->group(['namespace' => 'OrdersBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:wechat'], function ($api) {
        $api->get('/wxapp/groupOrders/{teamId}',  ['as' => 'front.wxapp.grouporder.info', 'uses'=>'WxappOrder@getGroupOrderDetail']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ 微信小程序端接口 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */

/* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ 微信小程序端接口 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
$api->version('v1', function ($api) {
    // 企业相关信息
    $api->group(['namespace' => 'WechatBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:wechat'], function ($api) {
        $api->get('/wxa/promotion/articles',      ['as' => 'front.wxapp.promotion.articles',      'uses' => 'Wxapp@getPromotionArticles']);
        $api->get('/wxa/promotion/articles/info', ['as' => 'front.wxapp.promotion.articles.info', 'uses' => 'Wxapp@getPromotionArticlesInfo']);
        $api->get('/wxapp/pageparams/setting', ['as' => 'front.wxapp.pageparams.setting', 'uses' => 'Wxapp@getParamByTempName']);
        $api->get('/wxapp/share/setting', ['as' => 'front.wxapp.share.setting', 'uses' => 'Wxapp@getShareSetting']);
    });
});

$api->version('v2', function ($api) {
    // 企业相关信息
    $api->group(['namespace' => 'WechatBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:wechat'], function ($api) {
        $api->get('/wxa/promotion/articles',      ['as' => 'front.wxapp.promotion.articles',      'uses' => 'Wxapp@getPromotionArticles']);
        $api->get('/wxa/promotion/articles/info', ['as' => 'front.wxapp.promotion.articles.info', 'uses' => 'Wxapp@getPromotionArticlesInfo']);
        $api->get('/wxapp/pageparams/setting', ['as' => 'front.wxapp.pageparams.setting', 'uses' => 'Wxapp@getParamByTempName']);
        $api->get('/wxapp/share/setting', ['as' => 'front.wxapp.share.setting', 'uses' => 'Wxapp@getShareSetting']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ 微信小程序端接口 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */

/* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ 微信小程序端接口 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
$api->version('v1', function ($api) {
    // 企业相关信息
    $api->group(['namespace' => 'OrdersBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:wechat', 'api.auth'], 'providers' => 'wxapp'], function ($api) {
        $api->get('/wxapp/payment/config', ['as' => 'front.wxapp.payment.config',  'uses'=>'WxappPayment@doPayment']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ 微信小程序端接口 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */

$api->version('v2', function ($api) {
    //以下为最新无需授权的接口
    $api->group(['namespace' => 'PromotionsBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:wechat'], function ($api) {
        $api->get('/wxapp/promotion/register', ['as' => 'front.wxapp.promotion.register', 'uses'=>'RegisterPromotions@getRegisterPromotionsConfig']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ 微信小程序端接口 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
