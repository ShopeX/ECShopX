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
    // 企业相关信息
    $api->group(['prefix' => 'h5app', 'namespace' => 'OrdersBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        $api->get('/wxapp/payment/config', ['name' => '获取支付参数', 'as' => 'front.wxapp.payment.config',  'uses'=>'WxappPayment@doPayment']);
        $api->post('/wxapp/payment_deposit', ['as' => 'front.wxapp.payment.deposit',  'uses'=>'WxappPayment@depositPayment']);
    });

    $api->group(['prefix' => 'h5app', 'namespace' => 'OrdersBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app', 'providers' => 'jwt'], function($api) {
        $api->post('/wxapp/payment', ['name' => '获取支付需要的参数， 积分以及预存款直接扣除', 'as' => 'front.wxapp.payment.payment',  'uses'=>'WxappPayment@payment']);
        $api->post('/wxapp/payment/query', ['name' => '支付结果查询', 'as' => 'front.wxapp.payment.query',  'uses'=>'WxappPayment@query']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app端、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
