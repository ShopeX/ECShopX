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

$api->version('v1', function($api) {
    $api->group(['prefix' => '/admin/wxapp', 'namespace' => 'KaquanBundle\Http\AdminApi\V1\Action', 'middleware' => ['api.auth', 'distributorlog'], 'providers' => 'adminwxapp'], function($api) {
        //  卡券核销相关
        $api->get('/card_consume', ['name' => '卡券扫码核销', 'as' => 'admin.wxapp.card.consume',  'uses'=>'UserDiscount@userCardConsume']);
        $api->get('/user_card_detail', ['name' => '卡券核销获取', 'as' => 'admin.wxapp.card.get',  'uses'=>'UserDiscount@getUserCardDetail']);
        // 导购优惠券相关
        $api->post('/salespersongivecoupons', ['name' => '导购员发放优惠券给会员', 'as' => 'front.wxapp.coupons.give', 'uses' => 'UserDiscount@giveUserCoupons']);
        $api->post('/salespersongivecoupons/{id}', ['name' => '导购员重试发放优惠券给会员', 'as' => 'front.wxapp.coupons.givetry', 'uses' => 'UserDiscount@tryGiveUserCoupons']);
        $api->get('/permissioncoupons', ['name' => '获取导购员可发放优惠券列表', 'as'=>'admin.wxapp.give_card.get', 'uses'=>'UserDiscount@getPermissionCouponsList']);
        $api->get('/getusercoupons', ['name' => '导购员获取用户已领取的优惠券列表', 'as'=>'admin.wxapp.user.card.list', 'uses'=>'UserDiscount@getUserCouponsList']);
        $api->get('/couponrecord', ['name' => '导购员获取赠券记录', 'as'=>'admin.wxapp.user.card.couponrecord', 'uses'=>'UserDiscount@getCouponsRecord']);
        $api->get('/sendCouponList', ['name' => '导购员赠券记录', 'as'=>'admin.wxapp.user.card.send_coupon_list', 'uses'=>'UserDiscount@getSendCouponsList']);
    });
    $api->group(['prefix' => '/admin/wxapp', 'namespace' => 'SalespersonBundle\Http\AdminApi\V1\Action', 'middleware' => ['api.auth', 'distributorlog'], 'providers' => 'adminwxapp'], function($api) {
        // 导购优惠券相关
        $api->get('/salesperson/coupon', ['name' => '获取导购员可发放优惠券列表', 'as'=>'admin.wxapp.coupon.salesperson.list', 'uses'=>'SalespersonCouponController@lists']);
    });
});

$api->version('v2', function($api) {
    $api->group(['prefix' => '/admin/wxapp', 'namespace' => 'KaquanBundle\Http\AdminApi\V2\Action', 'middleware' => ['api.auth', 'distributorlog'], 'providers' => 'adminwxapp'], function($api) {
        // 导购优惠券相关
        $api->get('/couponrecord', ['name' => '导购员获取赠券记录', 'as'=>'admin.wxapp.user.card.couponrecord', 'uses'=>'UserDiscount@getCouponsRecord']);
    });
});
