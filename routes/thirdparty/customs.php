<?php
/*
|--------------------------------------------------------------------------
| 海关
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */

$api->version('v1', function($api) {
    $api->group(['namespace' => 'ThirdPartyBundle\Http\ThirdApi\V1\Action'], function($api) {
        //海关数据请求
        $api->post('/third/customs/platData', ['as' => 'third.customs.platData', 'uses'=>'Customs@setPlatData']);
        //订单数据下发接口
        $api->post('/third/customs/getOrderData', ['as' => 'third.customs.get.orderData', 'uses'=>'Customs@getOrderData']);
        //上报结果回调接口
        $api->post('/third/customs/updateOrderData', ['as' => 'third.customs.update.orderData', 'uses'=>'Customs@updateOrderData']);
    });
});
