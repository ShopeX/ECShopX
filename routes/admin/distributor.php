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
    $api->group(['prefix' => '/admin/wxapp', 'namespace' => 'SalespersonBundle\Http\AdminApi\V1\Action', 'middleware' => ['api.auth', 'distributorlog'], 'providers' => 'adminwxapp'], function($api) {
        $api->get('/getinfo', ['name' => '获取导购自己的信息', 'as' => 'admin.wxapp.getinfo',  'uses'=>'ShopSalespersonController@getSelfInfo']);
        $api->get('/distributorlist', ['name' => '获取导购店铺列表', 'as' => 'admin.wxapp.distributor.list',  'uses'=>'ShopSalespersonController@getDistributorDataList']);
        $api->get('/shoplist', ['name' => '获取门店列表', 'as' => 'admin.wxapp.shop.list',  'uses'=>'ShopSalespersonController@getShopDataList']);
        $api->get('/salespersonCount', ['name' => '获取导购数据统计', 'as' => 'admin.wxapp.salesperson.count',  'uses'=>'ShopSalespersonController@getSalespersonCountData']);
        // 导购端二维码相关
        $api->get('/salespersonQrcode', ['name' => '获取导购二维码获取', 'as' => 'admin.wxapp.salesperson.qrcode',  'uses'=>'ShopSalespersonController@getSalespersonContactQrCode']);
        $api->post('/salespersonQrcode', ['name' => '导购二维码更新', 'as' => 'admin.wxapp.update.salesperson.qrcode',  'uses'=>'ShopSalespersonController@updateSalespersonContactQrCode']);
        // 导购端购物车相关
        $api->get('/cartdataadd', ['name' => '导购员购物车新增', 'as' => 'admin.wxapp.cartdata.add',  'uses'=>'SalespersonCartController@cartdataAdd']);
        $api->post('/items/scancodeAddcart', ['name' => '扫条形码加入购物车', 'as' => 'admin.wxapp.cartdata.goods.detail',  'uses'=>'SalespersonCartController@scanCodeSales']);
        $api->post('/scancodeAddcart', ['name' => '扫条形码加入购物车', 'as' => 'admin.wxapp.cartdata.goods.detail',  'uses'=>'SalespersonCartController@scanCodeSales']);
        $api->get('/cartdataupdate', ['name' => '导购员购物车更新', 'as' => 'admin.wxapp.cartdata.update',  'uses'=>'SalespersonCartController@updateCartdata']);
        $api->get('/cartdatalist', ['name' => '获取导购员购物车', 'as' => 'admin.wxapp.cartdata.list',  'uses'=>'SalespersonCartController@getCartdataList']);
        $api->get('/cartdatadel', ['name' => '导购员购物车删除', 'as' => 'admin.wxapp.cartdata.del',  'uses'=>'SalespersonCartController@delCartdata']);
        $api->get('/salesPromotion', ['name' => '获取导购员促销单', 'as' => 'admin.wxapp.cartdata.del',  'uses'=>'SalespersonCartController@createSalesPromotion']);
        // 导购端统计相关(旧)
        $api->get('/statistics', ['name' => '获取导购统计(旧)', 'as' => 'admin.wxapp.shop.statistics',  'uses'=>'StatisticsController@lists']);
        $api->get('/statistics/typelist', ['name' => '获取导购统计类型(旧)', 'as' => 'admin.wxapp.shop.statistics.typelist',  'uses'=>'StatisticsController@typeList']);
        // 导购端通知相关
        $api->get('/noticeunreadcount', ['name' => '获取导购员未读消息数量', 'as' => 'admin.wxapp.notice.unread', 'uses'=>'SalespersonNoticeController@getUnreadNum']);
        $api->get('/noticelist', ['name' => '获取导购员通知列表', 'as' => 'admin.wxapp.notice.list', 'uses'=>'SalespersonNoticeController@getNoticeList']);
        $api->get('/notice', ['name' => '获取导购员通知详情并设为已读', 'as' => 'admin.wxapp.notice.notice', 'uses'=>'SalespersonNoticeController@getNoticeDetail']);
        // 导购端排名相关
        $api->get('/leaderboard', ['name' => '获取导购端首页排名', 'as' => 'admin.wxapp.leaderboard.info', 'uses'=>'SalespersonLeaderboardController@getLeaderboardInfo']);
        $api->get('/leaderboard/salesperson', ['name' => '获取导购排名列表', 'as' => 'admin.wxapp.leaderboard.salesperson', 'uses'=>'SalespersonLeaderboardController@getSalespersonLeaderboardList']);
        $api->get('/leaderboard/distributor', ['name' => '获取店铺排名列表', 'as' => 'admin.wxapp.leaderboard.distributor', 'uses'=>'SalespersonLeaderboardController@getDistributorLeaderboardList']);
        // 导购端任务相关
        $api->get('/salesperson/task', ['name' => '获取导购任务列表', 'as' => 'admin.wxapp.task.lists', 'uses'=>'SalespersonTaskController@lists']);
        $api->get('/salesperson/task/{taskId}', ['name' => '获取导购任务详情', 'as' => 'admin.wxapp.task.info', 'uses'=>'SalespersonTaskController@info']);

        $api->post('/shop/checkSign', ['as' => 'admin.wxapp.shop.check', 'uses' => 'ShopSalespersonController@checkSign']); //扫码之后调用
        $api->post('/shop/signin', ['as' => 'admin.wxapp.shop.signin', 'uses' => 'ShopSalespersonController@signin']); //签到
        $api->post('/shop/signout', ['as' => 'front.wxapp.shop.signout', 'uses' => 'ShopSalespersonController@signout']); //签退
    });
    $api->group(['prefix' => '/admin/wxapp', 'namespace' => 'SalespersonBundle\Http\AdminApi\V1\Action', 'providers' => 'adminwxapp'], function ($api) {
        $api->get('/bydistributor/salespersonQrcode/{company_id}', ['name' => '根据门店获取导购二维码', 'as' => 'admin.wxapp.bydistributor.salesperson.qrcode', 'uses'=>'ShopSalespersonController@getSalespersonContactQrCodeByDistributor']);
    });
    $api->group(['prefix' => '/h5app', 'namespace' => 'SalespersonBundle\Http\AdminApi\V1\Action', 'providers' => 'adminwxapp'], function ($api) {
        $api->get('/wxapp/bydistributor/salespersonQrcode/{company_id}', ['name' => '根据门店获取导购二维码', 'as' => 'admin.wxapp.bydistributor.salesperson.qrcode', 'uses'=>'ShopSalespersonController@getSalespersonContactQrCodeByDistributor']);
    });
});

$api->version('v2', function($api) {
    $api->group(['prefix' => '/admin/wxapp', 'namespace' => 'SalespersonBundle\Http\AdminApi\V2\Action', 'middleware' => ['api.auth', 'distributorlog'], 'providers' => 'adminwxapp'], function($api) {
        $api->get('/salespersonCount', ['name' => '获取导购当月统计信息', 'as' => 'admin.wxapp.salesperson.count',  'uses'=>'ShopSalespersonController@getSalespersonCountData']);
        $api->get('/salespersonFee', ['name' => '统计导购分润信息', 'as' => 'admin.wxapp.salesperson.fee',  'uses'=>'ShopSalespersonController@getSalespersonProfitFee']);
    });
});
