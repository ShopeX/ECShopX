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

    $api->group(['namespace' => 'SystemLinkBundle\Http\ThirdApi\V1\Action'], function($api) {
        //test 同步订单
        $api->get('test/event/{order_id}', ['as' => 'ome.order.test',  'uses'=>'Order@testEvent']);

        //test 同步拼团订单
        $api->get('test/group/event', ['as' => 'ome.order.test',  'uses'=>'Order@testGroupEvent']);

        //test 发送退款申请单
        $api->get('test/refund/event', ['as' => 'ome.order.refund',  'uses'=>'Order@testRefundEvent']);

        //test 发送售后请单
        $api->get('test/aftersales/event', ['as' => 'ome.order.aftersales',  'uses'=>'Order@testAftersalesEvent']);

        //test 更新售后退货物流信息
        $api->get('test/aftersales/logi/event', ['as' => 'ome.order.aftersales.logi',  'uses'=>'Order@testAfterLogiEvent']);

        //test 售后买家取消
        $api->get('test/aftersales/cancel/event', ['as' => 'ome.order.aftersales.cancel',  'uses'=>'Order@testAftersalesCancelEvent']);

        $api->get('ome/createitems', ['as' => 'ome.items.create', 'uses' => 'Item@createItems']);

    });

    $api->group(['namespace' => 'SystemLinkBundle\Http\ThirdApi\V1\Action','prefix'=>'systemlink', 'middleware' => ['ShopexErpCheck']], function($api) {

         // ome获取订单详情
        $api->post('ome', ['as' => 'ome.api',  'uses'=>'Verify@omeApi']);
        $api->post('ome/{method}', ['as' => 'ome.api',  'uses'=>'Verify@omeApi']);

        // 订单发票信息接收
        //$api->post('ome/updateInvoice', ['as' => 'ome.api',  'uses'=>'Order@ReceiveOrderInvoice']);

        // ome获取订单详情
        // $api->post('store.trade.fullinfo.get', ['as' => 'ome.order.info',  'uses'=>'Order@getOrderInfo']);

        // // ome订单发货
        // $api->post('store.logistics.offline.send', ['as' => 'ome.order.delivery',  'uses'=>'Delivery@createDelivery']);

        // // ome同意订单退款
        // $api->post('store.trade.refund.status.update', ['as' => 'ome.order.refund.update', 'uses' => 'Refund@updateOrderRefund']);

        // // ome拒绝订单退款
        // $api->post('store.refund.refuse', ['as' => 'ome.order.refund.refuse', 'uses' => 'Refund@closeOrderRefund']);

        // ome更新商品库存
        // $api->post('store.items.quantity.list.update', ['as' => 'ome.item.update.store', 'uses' => 'Item@updateItemStore']);
        //$api->post('ome/createitems', ['as' => 'ome.items.create', 'uses' => 'Item@createItems']);

        // // ome 更新售后申请单
        // $api->post('store.trade.aftersale.status.update', ['as' => 'ome.update.aftersales', 'uses' => 'Aftersales@updateAftersalesStatus']);
        //$api->post('ome/goods/category', ['name' => '添加分类', 'as' => 'goods.category.create', 'uses' => 'ItemsCategory@createCategory']);




    });

});

