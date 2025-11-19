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
    $api->group(['prefix' => '/admin/wxapp', 'namespace' => 'OrdersBundle\Http\AdminApi\V1\Action', 'middleware' => ['api.auth', 'distributorlog'], 'providers' => 'adminwxapp'], function($api) {
        $api->get('/trade', ['name' => '获取交易列表', 'as' => 'admin.wxapp.trade.list',  'uses'=>'Trade@getTradelist']);
        $api->get('/right', ['name' => '获取权益详情', 'as' => 'admin.wxapp.right.info',  'uses'=>'Rights@getRightsDetail']);
        $api->post('/right/consume', ['name' => '核销权益', 'as' => 'admin.wxapp.right.consume',  'uses'=>'Rights@consumeRights']);
        $api->get('/right/list', ['name' => '获取指定会员权益列表', 'as' => 'admin.wxapp.right.list',  'uses'=>'Rights@getRightsList']);
        $api->get('/consumer/list', ['name' => '获取会员核销记录列表', 'as' => 'admin.wxapp.consumer.list',  'uses'=>'Rights@getRightsConsumerList']);
    });
});


