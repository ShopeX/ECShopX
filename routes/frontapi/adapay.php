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
    // adapay
    $api->group(['prefix' => 'h5app/wxapp/adapay', 'namespace' => 'AdaPayBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        // 获取分销员认证信息
        $api->get('/popularize/cert', ['name' => '获取分销员认证信息', 'as' => 'popularize.get_cert', 'uses' => 'AdapayPromoter@getCertInfo']);
        // 新建分销员认证信息
        $api->post('/popularize/create_cert', ['name' => '新建分销员认证信息', 'as' => 'popularize.create_cert', 'uses' => 'AdapayPromoter@createCert']);
        // 更新分销员认证状态
        $api->post('/popularize/update_cert', ['name' => '编辑分销员认证信息', 'as' => 'popularize.update_cert', 'uses' => 'AdapayPromoter@updateCert']);

        $api->post('/bank/list', ['name' => '获取银行列表', 'as' => 'wxapp.adapay.bank.list', 'uses' => 'Account@getBanksLists']);
    });

    // 不需要登录
    $api->group(['prefix' => 'h5app/wxapp/adapay', 'namespace' => 'AdaPayBundle\Http\FrontApi\V1\Action', 'middleware' => ['frontnoauth:h5app'], 'providers' => 'jwt'], function ($api) {
        $api->get('/bank/list', ['name' => '获取银行列表', 'as' => 'wxapp.adapay.bank.list', 'uses' => 'Account@getBanksLists']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
