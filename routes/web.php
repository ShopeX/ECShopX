<?php

/** @var \Laravel\Lumen\Routing\Router $router */

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

$app->group(['namespace' => '\WorkWechatBundle\Http\Controllers'], function($app) {
    $app->get('/{verify_name}.txt', ['as' => 'workwechat.verify.domain', 'uses' => 'WorkWechatVerify@domain']);
});

$app->post('/wechatAuth/events', [ 'as' => 'wechat.authorized',  'uses'=>'WecachePush@authorized']);
$app->post('/wechatAuth/callback/{authorizerAppId}', ['as' => 'wechat.message.callback', 'uses'=>'WecachePush@message']);
$app->post('/wechatAuth/wxpay/notify', ['as' => 'wechat.pay.notify', 'uses'=>'PaymentNotify@handle']);
// 获取小程序码
$app->get('/wechatAuth/wxapp/qrcode.png', ['as' => 'front.wxapp.qrcode',  'uses'=>'Qrcode@getQrcode'] );


// PayPal 支付回调路由
$app->get('/payment/paypal/success', ['as' => 'paypal.success', 'uses' => 'PaymentBundle\Http\Controllers\PaypalNotify@handle']);
$app->get('/payment/paypal/cancel', ['as' => 'paypal.cancel', 'uses' => 'PaymentBundle\Http\Controllers\PaypalNotify@cancel']);
$app->post('/payment/paypal/webhook', ['as' => 'paypal.webhook', 'uses' => 'PaymentBundle\Http\Controllers\PaypalNotify@webhook']);