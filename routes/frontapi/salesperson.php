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

  
/* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ taro小程序、h5、app端、pc端 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
$api->version('v1', function ($api) {

    $api->group(['prefix' => 'h5app', 'namespace' => 'SalespersonBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        // 业务员店铺相关
        $api->get('/wxapp/salesperson/salemanShopList', ['name' => '业务员店铺列表', 'as' => 'h5app.wxapp.salesperson.salemanShopList',  'uses'=>'ShopSalespersonController@SalemanShopList']);
        //业务员关系绑定24小时有效
        $api->post('/wxapp/salesperson/bindusersalesperson', ['name' => '业务员关系绑定24小时有效', 'as' => 'h5app.wxapp.salesperson.bindusersalesperson', 'uses'=>'ShopSalespersonController@bindusersalesperson']);

        // 店铺业务员管理-查询业务员信息
        $api->get( '/wxapp/salespersonadmin/storemanagerinfo', ['name' => '查询管理信息', 'as' => 'h5app.wxapp.salespersonadmin.storemanagerinfo',  'uses'=>'ShopSalespersonController@storemanagerinfo']);
        // 店铺业务员管理-增加业务员
        $api->post('/wxapp/salespersonadmin/addsalesperson', ['name' => '增加业务员', 'as' => 'h5app.wxapp.salespersonadmin.addsalesperson', 'uses'=>'ShopSalespersonController@addsalesperson']);
        // 店铺业务员管理-更新业务员
        $api->post('/wxapp/salespersonadmin/updatesalesperson', ['name' => '更新业务员', 'as' => 'h5app.wxapp.salespersonadmin.updatesalesperson', 'uses'=>'ShopSalespersonController@updatesalesperson']);
        // 店铺业务员管理-查询业务员列表
        $api->get('/wxapp/salespersonadmin/salespersonlist', ['name' => '查询业务员列表', 'as' => 'h5app.wxapp.salespersonadmin.salespersonlist', 'uses'=>'ShopSalespersonController@salespersonlist']);
        // 店铺业务员管理-查询业务员信息
        $api->get('/wxapp/salespersonadmin/salespersoninfo', ['name' => '查询业务员列表', 'as' => 'h5app.wxapp.salespersonadmin.salespersoninfo', 'uses'=>'ShopSalespersonController@salespersoninfo']);
        // 店铺业务员管理-查询业绩
        $api->get('/wxapp/salespersonadmin/brokagestaticlist', ['name' => '查询业绩统计', 'as' => 'h5app.wxapp.salespersonadmin.brokagestaticlist', 'uses'=>'ShopSalespersonController@brokagestaticlist']);
 
    });


    // 导购货架相关api
    $api->group(['prefix' => 'h5app', 'namespace' => 'SalespersonBundle\Http\FrontApi\V1\Action', 'middleware' => ['api.auth'], 'providers' => 'qywxapp'], function ($api) {

        // 导购店铺相关
        $api->get('/wxapp/salesperson/distributorlist', ['name' => '获取导购店铺列表', 'as' => 'h5app.wxapp.salesperson.distributor.list',  'uses'=>'ShopSalespersonController@getDistributorDataList']);
        // 校验导购员的店铺信息
        $api->get('/wxapp/salesperson/distributor/is_valid', ['name' => '校验导购员的店铺信息', 'as' => 'h5app.wxapp.salesperson.distributor.is_valid',  'uses'=>'ShopSalespersonController@checkDistributorIsValid']);
        // 导购端购物车相关
        $api->get('/wxapp/salesperson/cartdataadd', ['name' => '导购员购物车新增', 'as' => 'h5app.wxapp.salesperson.cartdata.add',  'uses'=>'SalespersonCartController@cartdataAdd']);
        $api->post('/wxapp/salesperson/scancodeAddcart', ['name' => '扫条形码加入购物车', 'as' => 'h5app.wxapp.salesperson.cartdata.goods.detail',  'uses'=>'SalespersonCartController@scanCodeSales']);
        $api->get('/wxapp/salesperson/cartdatalist', ['name' => '获取导购员购物车', 'as' => 'h5app.wxapp.salesperson.cartdata.list',  'uses'=>'SalespersonCartController@getCartdataList']);
        //修改购物车选中状态
        $api->put('/wxapp/salesperson/cartupdate/checkstatus', ['name' => '修改购物车选中状态', 'as' => 'h5app.wxapp.salesperson.cartupdate.checkstatus', 'uses' => 'SalespersonCartController@updateCartCheckStatus']);
        $api->get('/wxapp/salesperson/cartcount',  ['name' => '获取购物车数量', 'as' => 'h5app.wxapp.salesperson.cartcount',  'uses'=>'SalespersonCartController@getCartItemCount']);
        $api->get('/wxapp/salesperson/salesPromotion', ['name' => '获取导购员促销单', 'as' => 'h5app.wxapp.salesperson.salesPromotion',  'uses'=>'SalespersonCartController@createSalesPromotion']);
        $api->post('/wxapp/salesperson/bainfo', ['name' => '更新导购敏感信息', 'as' => 'h5app.wxapp.salesperson.bainfo', 'uses'=>'SalespersonController@updateBaInfo']);

    });

    $api->group(['prefix' => 'h5app', 'namespace' => 'GoodsBundle\Http\FrontApi\V1\Action', 'middleware' => ['api.auth'], 'providers' => 'qywxapp'], function ($api) {
        // 商品相关
        $api->get('/wxapp/goods/salesperson/items', ['name' => '获取商品列表', 'as' => 'h5app.wxapp.goods.salesperson.getList',  'uses'=>'SalespersonItems@getItemList']);
        $api->get('/wxapp/goods/salesperson/itemsinfo', ['name' => '获取商品详情', 'as' => 'h5app.wxapp.goods.salesperson.detail',  'uses'=>'SalespersonItems@getItemsDetail']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
