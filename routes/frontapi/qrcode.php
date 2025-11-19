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
    $api->group(['prefix' => 'h5app', 'namespace' => 'WechatBundle\Http\Controllers', 'middleware' => 'frontnoauth:h5app'], function ($api) {
        $api->get('/wxapp/pcqrcode', ['as' => 'front.wxapp.pcqrcode', 'uses' => 'Qrcode@getPcQrcode']);
        $api->get('/wxapp/pcloginqrcode', ['as' => 'front.wxapp.pcloginqrcode', 'uses' => 'Qrcode@getPcLoginQrcode']);
    });
    $api->group(['prefix' => 'h5app', 'namespace' => 'WechatBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {
        // 获取小程序url链接
        $api->post('/wxapp/urllink', ['as' => 'front.wxapp.urllink', 'uses' => 'Wxapp@wxaUrlLink']);
        // 获取小程序schema链接
        $api->post('/wxapp/urlschema', ['as' => 'front.wxapp.urlschema', 'uses' => 'Wxapp@wxaUrlSchema']);
    });

    $api->group(['prefix' => 'h5app', 'namespace' => 'AliBundle\Http\Controllers', 'middleware' => 'frontnoauth:h5app'], function($api) {
        $api->get('/alipaymini/qrcode.png', ['as' => 'front.alipaymini.qrcode', 'uses' => 'Qrcode@getQrcode']);
    });
});
// $app->router->group(['namespace' => 'CommunityBundle\Http\Controllers'], function ($app) {
//     // 小程序端获取社区二维码
//     $app->get('/wechatAuth/shopwxapp/community/qrcode.png', ['as' => 'front.promotion.community.qrcode',  'uses'=>'Qrcode@getQrcode'] );
// });
// $app->router->group(['namespace' => 'WechatBundle\Http\Controllers'], function ($app) {
//     // 获取小程序码
//     $app->get('/wechatAuth/wxapp/qrcode.png', ['as' => 'front.wxapp.qrcode',  'uses'=>'Qrcode@getQrcode'] );
// });
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app端、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
