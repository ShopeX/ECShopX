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

    $api->group(['namespace' => 'SystemLinkBundle\Http\ThirdApi\V1\Action','prefix'=>'systemlink', 'middleware' => ['ShopexErpCheck']], function($api) {

         // ome获取订单详情
        $api->post('ome', ['as' => 'ome.api',  'uses'=>'Verify@omeApi']);
        $api->post('ome/{method}', ['as' => 'ome.api',  'uses'=>'Verify@omeApi']);
        $api->post('ome/createitems', ['as' => 'ome.items.create', 'uses' => 'Item@createItems']);

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

