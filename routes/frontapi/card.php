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
    $api->group(['prefix' => 'h5app', 'namespace' => 'KaquanBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        // 获取卡券详情-已支持h5
        $api->get('/wxapp/getCardDetail/{cardId}',    ['as' => 'front.wxapp.card.datail', 'uses' => 'DiscountCard@getDiscountCardDetail']);
        // 用户领取卡券-已支持h5
        $api->get('/wxapp/user/receiveCard',   ['as' => 'front.wxapp.user.get.card', 'uses' => 'UserDiscount@receiveCard']);
        // 用户核销卡券-已支持h5
        $api->get('/wxapp/user/consumCard',    ['as' => 'front.wxapp.user.consum.card', 'uses' => 'UserDiscount@ConsumCard']);
        // 用户删除已领取的卡券-已支持h5
        $api->get('/wxapp/user/removeCard',    ['as' => 'front.wxapp.user.remove.card', 'uses' => 'UserDiscount@DeleteUserCard']);

        // 用户使用兑换券
        $api->post('/wxapp/user/exchangeCard', ['as' => 'front.wxapp.user.exchange.card', 'uses' => 'UserDiscount@exchangeCard']);
        // 获取用户兑换券使用详情
        $api->get('/wxapp/user/exchangeCardInfo', ['as' => 'front.wxapp.user.exchange.card.info', 'uses' => 'UserDiscount@exchangeCardInfo']);

        // 获取用户已领取的优惠券列表-已支持h5
        $api->get('/wxapp/user/getCardList',   ['as' => 'front.wxapp.user.card.list', 'uses' => 'UserDiscount@getUserCardList']);
        // 获取用户已领取的优惠券详情-已支持h5
        $api->get('/wxapp/user/getCardDetail', ['as' => 'front.wxapp.user.card.detail', 'uses' => 'UserDiscount@getUserDiscountDetail']);
        //用户端核销卡券（暂时自助核销使用）
        $api->get('/wxapp/user/usedCard',      ['as' => 'front.wxapp.user.card.usedCard', 'uses' => 'UserDiscount@userUsedCard']);

        // 当前等级用户抢券
        $api->post('/wxapp/user/currentGardCardPackage',   ['as' => 'front.wxapp.user.gard.package', 'uses' => 'UserDiscount@currentGardCardPackage']);
        // 领取模版卡券包
        $api->post('/wxapp/user/receiveCardPackage',   ['as' => 'front.wxapp.user.receive.package', 'uses' => 'UserDiscount@receivesPackage']);
        // 没有显示的卡券包列表
        $api->get('/wxapp/user/showCardPackage',   ['as' => 'front.wxapp.user.get.showCardPackage', 'uses' => 'UserDiscount@showCardPackage']);
        // 确认卡券包已前端显示
        $api->post('/wxapp/user/confirmPackageShow',   ['as' => 'front.wxapp.user.confirm.package', 'uses' => 'UserDiscount@confirmPackageReceivesShow']);
        // 得到的卡券包中卡券信息
        $api->get('/wxapp/user/getBindCardList',   ['as' => 'front.wxapp.user.get.bindCardList', 'uses' => 'UserDiscount@getCardListByBindType']);
    });

    $api->group(['prefix' => 'h5app', 'namespace' => 'KaquanBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        // 获取付费会员信息-已支持h5
        $api->get('/wxapp/vipgrades/uservip', ['as' => 'front.wxapp.vipgrades.uservip', 'uses' => 'VipGradeController@getUserVipGrade']);
        // 购买付费会员折扣卡-已支持h5
        $api->post('/wxapp/vipgrades/buy',    ['as' => 'front.wxapp.vipgrades.buy',  'uses' => 'VipGradeController@buyDataVipGrade']);
    });

    $api->group(['prefix' => 'h5app', 'namespace' => 'KaquanBundle\Http\FrontApi\V2\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        // 获取用户已领取的优惠券列表(新)-已支持h5
        $api->get('/wxapp/user/newGetCardList', ['as' => 'front.wxapp.user.card.newlist', 'uses' => 'UserDiscount@getUserCardList']);
        $api->get('/wxapp/user/newGetCardDetail', ['as' => 'front.wxapp.user.card.newdetail', 'uses' => 'UserDiscount@getUserDiscountDetail']);
        // 获取用户已领取的优惠券列表，现只用于 我的优惠券列表
        $api->get('/wxapp/user/getUserCardList', ['as' => 'front.wxapp.user.card.newlist', 'uses' => 'UserDiscount@getMyUserCardList']);
    });
});

$api->version('v1', function ($api) {
    $api->group(['prefix' => 'h5app', 'namespace' => 'KaquanBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'],  function ($api) {
        // 获取卡券列表-已支持h5
        $api->get('/wxapp/getCardList',    ['as' => 'front.wxapp.card.list', 'uses' => 'DiscountCard@getDiscountCardList']);
        // 获取付费会员等级卡列表-已支持h5
        $api->get('/wxapp/vipgrades/list', ['as' => 'front.wxapp.vipgrades.list', 'uses' => 'VipGradeController@listDataVipGrade']);
        $api->get('/wxapp/membercard/grades', ['as' => 'front.wxapp.membercard.list', 'uses' => 'VipGradeController@getGradeList']);
    });
    $api->group(['prefix' => 'h5app', 'namespace' => 'KaquanBundle\Http\FrontApi\V2\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {
        // 获取付费会员等级卡列表(新)-已支持h5
        $api->get('/wxapp/vipgrades/newlist', ['as' => 'front.wxapp.vipgrades.newlist', 'uses' => 'VipGradeController@listDataVipGrade']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app端、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
