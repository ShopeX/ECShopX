<?php

/*
|--------------------------------------------------------------------------
| SaasErp 接口
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */

$api->version('v1', function($api) {

    $api->group(['namespace' => 'ThirdPartyBundle\Http\ThirdApi\V1\Action'], function($api) {
        //test 同步订单
        $api->get('saaserp/test/event/{order_id}', ['as' => 'saaserp.order.test',  'uses'=>'Order@testEvent']);

        //test 同步拼团订单
        $api->get('saaserp/test/group/event', ['as' => 'saaserp.order.test',  'uses'=>'Order@testGroupEvent']);

        //test 发送退款申请单
        $api->get('saaserp/test/refund/event', ['as' => 'saaserp.order.refund',  'uses'=>'Order@testRefundEvent']);

        //test 发送售后请单
        $api->get('saaserp/test/aftersales/event', ['as' => 'saaserp.order.aftersales',  'uses'=>'Order@testAftersalesEvent']);

        //test 更新售后退货物流信息
        $api->get('saaserp/test/aftersales/logi/event', ['as' => 'saaserp.order.aftersales.logi',  'uses'=>'Order@testAfterLogiEvent']);

        //test 售后买家取消
        $api->get('saaserp/test/aftersales/cancel/event', ['as' => 'saaserp.order.aftersales.cancel',  'uses'=>'Order@testAftersalesCancelEvent']);

    });

    $api->group(['namespace' => 'ThirdPartyBundle\Http\ThirdApi\V1\Action','prefix'=>'thirdparty','middleware' => ['ShopexSaasErpCheck']], function($api) {
        // SaasErp api
        $api->post('saaserp', ['as' => 'saaserp.api',  'uses'=>'Verify@saasErpApi']);
        // saasErp 连通log列表
        $api->get('/saaserp/log/list', ['as' => 'saaserp.log.list',  'uses'=>'saasErp@getLogList']);
    });

});

