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
    // 售后相关api
    $api->group(['prefix' => 'h5app', 'namespace' => 'AftersalesBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        // 创建售后单-已支持h5
        $api->post('/wxapp/aftersales',                ['name' => '订单售后申请','as' => 'aftersales.create',   'uses' => 'Aftersales@apply']);
        // 编辑售后单-已支持h5
        $api->post('/wxapp/aftersales/modify',         ['name' => '编辑售后单', 'as' => 'aftersales.modify',   'uses' => 'Aftersales@modify']);
        // 获取售后单列表-已支持h5
        $api->get('/wxapp/aftersales',                 ['name' => '获取售后列表', 'as' => 'aftersales.list',     'uses' => 'Aftersales@getAftersalesList']);
        // 获取售后单详情-已支持h5
        $api->get('/wxapp/aftersales/info', ['name' => '获取售后单详情','as' => 'aftersales.info',     'uses' => 'Aftersales@getAftersalesDetail']);
        // 售后消费者回寄-已支持h5
        $api->post('/wxapp/aftersales/sendback',       ['name' => '售后消费者回寄', 'as' => 'aftersales.sendback', 'uses' => 'Aftersales@sendback']);
        // 售后关闭-已支持h5
        $api->post('/wxapp/aftersales/close',          ['name' => '售后关闭','as' => 'aftersales.close',    'uses' => 'Aftersales@closeConfirm']);
        //获取售后商品价格
        $api->get('/wxapp/aftersales/item/price', ['name' => '获取售后商品价格','as' => 'aftersales.item.price', 'uses' => 'Aftersales@getRefundAmount']);
        //获取售后原因列表
        $api->get('/wxapp/aftersales/reason/list', ['name' => '获取售后原因列表','as' => 'aftersales.reason.list', 'uses' => 'Reason@getSreasonList']);
        // 获取售后申请提醒内容
        $api->get('/wxapp/aftersales/remind/detail', ['name' => '售后提醒内容获取','as' => 'aftersales.remind', 'uses' => 'Aftersales@getRemind']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
