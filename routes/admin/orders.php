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
        // 订单相关接口
        $api->post('/order/create', ['name' => '导购员创建订单', 'as' => 'admin.wxapp.order.create',  'uses'=>'Orders@createOrders']);
        $api->get('/order/getlist', ['name' => '导购员获取用户订单列表', 'as' => 'admin.wxapp.order.getlist',  'uses'=>'Orders@getOrdersList']);
        $api->get('/order/getinfo', ['name' => '导购员获取用户订单详情', 'as' => 'admin.wxapp.order.getinfo',  'uses'=>'Orders@getOrdersInfo']);
        $api->get('/order/getsalespersonlist', ['name' => '获取导购分润订单列表', 'as' => 'admin.wxapp.order.getsalespersonlist',  'uses'=>'Orders@getSalespersonOrderList']);
        $api->get('/order/getSalepersonOrdersList', ['name' => '获取店铺订单列表', 'as' => 'admin.wxapp.order.getsalespersonorderslist',  'uses'=>'Orders@getSalespersonOrdersList']);
        $api->post('/order/delivery', ['name' => '导购员订单发货', 'role' => '1', 'as' => 'admin.wxapp.order.delivery',  'uses'=>'Orders@delivery']);
        $api->post('/order/process/{orderId}', ['name' => '导购员订单发货', 'role' => '4', 'as' => 'admin.wxapp.order.process',  'uses'=>'Orders@getOrderProcessLog']);
        $api->get('/trackerpull', ['name' => '订单物流查询', 'role' => '1', 'as' => 'admin.wxapp.order.trackerpull',  'uses'=>'Orders@trackerpull']);
        $api->post('/order/ziti', ['name' => '订单自提核销', 'as' => 'admin.wxapp.order.ziti',  'uses'=>'NormalOrder@finishOrderZiti']);
        $api->get('/order/detail', ['name' => '订单详情', 'as' => 'admin.wxapp.order.detail',  'uses'=>'NormalOrder@getOrderDetail']);
        $api->post('/normalcreate', ['name' => '订单创建', 'as' => 'admin.wxapp.normal.create',  'uses'=>'NormalOrder@createUserOrder']);
        $api->get('/cartcheckout', ['name' => '购物车结算列表', 'as' => 'admin.wxapp.cart.checkout',  'uses'=>'NormalOrder@cartCheckout']);
        $api->get('/orderstatus', ['name' => '获取订单支付状态', 'as' => 'admin.wxapp.orders.status', 'uses'=>'NormalOrder@getPayStatus']);
        // 物流相关接口
        $api->get('/logistics/list', ['name' => '获取启用物流公司列表', 'as' => 'admin.wxapp.logistics.list',  'uses'=>'CompanyRelLogistics@getCompanyLogisticsList']);
    });
});
