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
    $api->group(['prefix' => 'h5app', 'namespace' => 'DepositBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        // 充值-已支持h5
        $api->post('/wxapp/deposit/recharge', ['as' => 'front.wxapp.deposit.recharge',  'uses'=>'Recharge@recharge']);// 充值-已支持h5
        // 充值-新写
        $api->post('/wxapp/deposit/recharge_new', ['as' => 'front.wxapp.deposit.rechargenew',  'uses'=>'Recharge@rechargeNew']);
        // 获取充值面额规则-已支持h5
        $api->get('/wxapp/deposit/rechargerules', ['as' => 'front.wxapp.deposit.rechargerules',  'uses'=>'Recharge@getRechargeRuleList']);
        // 获取储值协议-已支持h5
        $api->get('/wxapp/deposit/recharge/agreement', ['as' => 'front.wxapp.deposit.recharge.agreement',  'uses'=>'Recharge@getRechargeAgreementByCompanyId']);
        // 充值列表
        $api->get('/wxapp/deposit/list', ['as' => 'front.wxapp.deposit.list',  'uses'=>'Deposit@lists']);
        // 充值总金额
        $api->get('/wxapp/deposit/info', ['as' => 'front.wxapp.deposit.info',  'uses'=>'Deposit@info']);
        // 储值兑换积分
        $api->post('/wxapp/deposit/to/point', ['as' => 'front.wxapp.deposit.to.point',  'uses'=>'Deposit@depositToPoint']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app端、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
