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
    $api->group(['prefix' => 'h5app', 'namespace' => 'HfPayBundle\Http\FrontApi\V1\Action',  'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function($api) {
        // 获取经销员入驻信息-已支持h5
        $api->get('/wxapp/hfpay/userapply',  ['as' => 'front.h5app.hfpay.info',  'uses' => 'HfpayDealer@getInfo', 'name' => '获取经销员入驻信息']);
        // 保存经销员入驻信息
        $api->post('/wxapp/hfpay/applysave',  ['as' => 'front.h5app.hfpay.save',  'uses' => 'HfpayDealer@save', 'name' => '保存经销员入驻信息']);
        // 获取单条银行卡信息
        $api->get('/wxapp/hfpay/bankinfo',  ['as' => 'front.h5app.hfpay.bankinfo',  'uses' => 'HfpayBank@getInfo', 'name' => '获取单条银行卡信息']);
        //获取多条银行卡信息
        $api->get('/wxapp/hfpay/banklist',  ['as' => 'front.h5app.hfpay.banklist',  'uses' => 'HfpayBank@getList', 'name' => '获取多条银行卡信息']);
        //保存提现银行卡
        $api->post('/wxapp/hfpay/banksave',  ['as' => 'front.h5app.hfpay.banksave',  'uses' => 'HfpayBank@save', 'name' => '保存提现银行卡']);
        //解除并删除绑定银行卡
        $api->post('/wxapp/hfpay/bankdel',  ['as' => 'front.h5app.hfpay.bankdel',  'uses' => 'HfpayBank@unBindBank', 'name' => '解除并删除绑定银行卡']);
    });
});

