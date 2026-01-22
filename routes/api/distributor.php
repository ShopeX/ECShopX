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
    // 微信相关信息
    $api->group(['namespace' => 'DistributionBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/distributor',                 ['name' => '创建店铺','middleware'=>'activated',  'as' => 'distributor.create',  'uses'=>'Distributor@createDistributor']);
        $api->get('/distributors',                 ['name' => '获取店铺列表','middleware'=>['activated', 'datapass'],  'as' => 'distributor.list',    'uses'=>'Distributor@getDistributorList']);
        $api->get('/distributors/info',            ['name' => '获取指定店铺信息','middleware'=>['activated','datapass'],  'as' => 'distributor.info',    'uses'=>'Distributor@getDistributorInfo']);
        $api->put('/distributor/{distributor_id}', ['name' => '更新店铺','middleware'=>'activated',  'as' => 'distributor.edit',    'uses'=>'Distributor@updateDistributor']);
        $api->put('/distributor/{distributor_id}/payment-subject', ['name' => '设置店铺收款主体','middleware'=>'activated', 'as' => 'distributor.payment.subject.set', 'uses'=>'Distributor@setPaymentSubject']);
        $api->get('/distributor/count/{distributorId}', ['name' => '获取店铺统计','middleware'=>'activated', 'as' => 'front.wxapp.distributor.count', 'uses'=>'Distributor@getDistributorCount']);
        $api->get('/distributor/wxacode',  ['name' => '获取店铺小程序码','middleware'=>'activated', 'as' => 'distributor.wxacode',           'uses'=>'Distributor@getWxaDistributorCodeStream']);

        $api->get('/distributor/easylist',                 ['name' => '获取店铺简易列表','middleware'=>'activated',  'as' => 'distributor.easy.list',    'uses'=>'Distributor@getEasyList']);

        $api->post('/distributor/items',  ['name' => '添加店铺关联店铺商品','middleware'=>'activated',  'as' => 'distributor.item.create',  'uses'=>'Distributor@saveDistributorItems']);
        $api->get('/distributor/items',   ['name' => '获取店铺关联商品列表','middleware'=>'activated',  'as' => 'distributor.item.list',  'uses'=>'Distributor@getDistributorItems']);
        $api->get('/distributor/items/export',   ['name' => '导出店铺关联商品列表','middleware'=>'activated',  'as' => 'distributor.item.exportlist',  'uses'=>'Distributor@exportDistributorItems']);
        $api->delete('/distributor/items', ['name' => '删除店铺关联商品列','middleware'=>'activated',  'as' => 'distributor.item.delete',  'uses'=>'Distributor@delDistributorItems']);
        $api->put('/distributors/item',  ['name' => '配置店铺价格或库存','middleware'=>'activated',  'as' => 'distributor.item.update',  'uses'=>'Distributor@updateDistributorItem']);

        $api->post('/distribution/basic_config', ['name' => '保存分销基础配置','middleware'=>'activated',  'as' => 'distribution.basic_config.save',  'uses'=>'BasicConfig@saveBasicConfig']);
        $api->get('/distribution/basic_config',  ['name' => '获取分销基础配置','middleware'=>'activated',  'as' => 'distributor.basic_config.get',    'uses'=>'BasicConfig@getBasicConfig']);

        $api->get('/distribution/cash_withdrawals',  ['name' => '获取佣金提现列表','middleware'=>'activated',  'as' => 'distribution.cash_withdrawal.list',    'uses'=>'CashWithdrawal@getCashWithdrawalList']);
        $api->put('/distribution/cash_withdrawal/{id}',  ['name' => '处理佣金提现申请','middleware'=>'activated',  'as' => 'distribution.cash_withdrawal.process',    'uses'=>'CashWithdrawal@processCashWithdrawal']);
        $api->get('/distributor/cash_withdrawal/payinfo/{cash_withdrawal_id}', ['name' => '获取佣金提现支付信息','middleware'=>'activated', 'as' => 'front.wxapp.cashWithdrawal.payinfo',  'uses'=>'CashWithdrawal@getMerchantTradeList']);

        $api->get('/distribution/logs', ['name' => '获取佣金记录','middleware'=>'activated', 'as' => 'front.wxapp.distribution.log',  'uses'=>'DistributeLogs@getDistributeLogs']);
        $api->get('/distribution/count', ['name' => '获取分销统计','middleware'=>'activated', 'as' => 'front.wxapp.distribution.count',  'uses'=>'DistributeLogs@getCompanyCount']);

        //获取经销商可关联的门店列表
        $api->get('/distributor/getShop', ['name' => '获取有效的门店列表','middleware'=>'activated', 'as' => 'distributor.get.shop',  'uses'=>'Distributor@getValidShopList']);

        $api->post('/distributor/default', ['name' => '设置默认门店','middleware'=>'activated',  'as' => 'distributor.default.set',    'uses'=>'Distributor@defaultSetDistributor']);

        // 添加店铺标签
        $api->post('/distributor/tag', ['name'=> '添加店铺标签','middleware'=>'activated', 'as' => 'distributor.tag.add', 'uses' =>'DistributorTags@createTags']);
        // 删除店铺标签
        $api->delete('/distributor/tag/{tagId}', ['name'=> '删除店铺标签','middleware'=>'activated', 'as' => 'distributor.tag.delete', 'uses' =>'DistributorTags@deleteTag']);
        // 更新店铺标签
        $api->put('/distributor/tag/{tagId}', ['name'=> '更新店铺标签','middleware'=>'activated', 'as' => 'distributor.tag.update', 'uses' =>'DistributorTags@updateTags']);
        // 获取店铺标签列表
        $api->get('/distributor/tag', ['name'=> '获取店铺标签列表','middleware'=>'activated', 'as' => 'distributor.tag.list', 'uses' =>'DistributorTags@getTagsList']);
        // 获取店铺标签详情
        $api->get('/distributor/tag/{tagId}', ['name'=> '获取店铺标签详情','middleware'=>'activated', 'as' => 'distributor.tag.get', 'uses' =>'DistributorTags@getTagsInfo']);
        // 商品关联标签
        $api->post('/distributor/reltag', ['name'=> '店铺关联标签','middleware'=>'activated', 'as' => 'distributor.tag.rel', 'uses' =>'DistributorTags@tagsRelDistributor']);
        // 店铺与店铺标签做解绑
        $api->post('/distributor/deltag', ['name'=> '店铺与店铺标签做解绑','middleware'=>'activated', 'as' => 'distributor.tag.del', 'uses' =>'DistributorTags@tagsRemoveDistributor']);

        //店铺门店相关接口开始
        $api->post('/shops',                 ['name' => '创建店铺门店','middleware'=>'activated',  'as' => 'shops.create',  'uses'=>'DistributorShopController@createShops']);
        $api->put('/shops/{distributor_id:[0-9]+}', ['name' => '更新店铺门店','middleware'=>'activated',  'as' => 'shops.edit',    'uses'=>'DistributorShopController@updateShops']);
        $api->delete('/shops/{distributor_id:[0-9]+}', ['name' => '删除店铺门店','middleware'=>'activated', 'as' => 'shops.delete', 'uses'=>'DistributorShopController@deleteShops']);
        $api->get('/shops/{distributor_id:[0-9]+}',  ['name' => '获取店铺门店详情','middleware'=>'activated', 'as' => 'shops.detail',           'uses'=>'DistributorShopController@getShopsDetail']);
        $api->get('/shops',                 ['name' => '获取店铺简易列表','middleware'=>'activated',  'as' => 'shops.list',    'uses'=>'DistributorShopController@getShopsList']);

        $api->post('/dshops/setDefaultShop',                 ['name' => '设置默认店铺门店','middleware'=>'activated',  'as' => 'shops.setdefault',    'uses'=>'DistributorShopController@setDefaultShop']);
        $api->post('/dshops/setShopStatus',            ['name' => '设置店铺门店状态','middleware'=>'activated',  'as' => 'shops.setstatus',    'uses'=>'DistributorShopController@setShopStatus']);
        //店铺门店相关接口结束

        // 分润相关配置
        $api->get('/distribution/config', ['name' => '获取分润配置', 'middleware'=>'activated', 'as' => 'distribution.config.get',    'uses'=>'DistributionConfig@getConfig']);
        $api->post('/distribution/config', ['name' => '保存分润配置', 'middleware'=>'activated', 'as' => 'distribution.config.save',    'uses'=>'DistributionConfig@setConfig']);

        // 店铺售后地址相关
        $api->post('/distributors/aftersalesaddress', ['name' => '添加店铺售后地址', 'middleware'=>[], 'as' => 'distribution.aftersalesaddress.save',    'uses'=>'DistributorAftersalesAddress@setDistributorAfterSalesAddress']);
        $api->put('/distributors/aftersalesaddress', ['name' => '修改店铺售后地址', 'middleware'=>[], 'as' => 'distribution.aftersalesaddress.update',    'uses'=>'DistributorAftersalesAddress@setDistributorAfterSalesAddress']);
        $api->delete('/distributors/aftersalesaddress/{address_id}', ['name' => '删除店铺售后地址', 'middleware'=>[], 'as' => 'distribution.aftersalesaddress.delete',    'uses'=>'DistributorAftersalesAddress@deleteDistributorAfterSalesAddress']);
        $api->get('/distributors/aftersalesaddress', ['name' => '获取店铺售后地址列表', 'middleware'=>['datapass'], 'as' => 'distribution.aftersalesaddress.list',    'uses'=>'DistributorAftersalesAddress@getDistributorAfterSalesAddress']);
        $api->get('/distributors/aftersalesaddress/{address_id}', ['name' => '获取店铺售后地址详情', 'middleware'=>[], 'as' => 'distribution.aftersalesaddress.get',    'uses'=>'DistributorAftersalesAddress@getDistributorAfterSalesAddressDetail']);

        $api->get('/distributors/aftersales', ['name' => '获取可退货店铺列表', 'middleware'=>'activated', 'as' => 'distributor.aftersales.list',    'uses'=>'Distributor@getOtherOfflineAftersalesDistributor']);

        $api->get('/distributor/salesperson/role', ['name' => '获取门店角色列表', 'middleware'=> 'activated', 'as' => 'distribution.salesperson.role.list',    'uses'=>'DistributorSalespersonRole@getRoleList']);
        $api->get('/distributor/salesperson/role/{salesmanRoleId}', ['name' => '获取门店角色', 'middleware'=> 'activated', 'as' => 'distribution.salesperson.role.get',    'uses'=>'DistributorSalespersonRole@getRoleInfo']);
        $api->post('/distributor/salesperson/role', ['name' => '保存门店角色', 'middleware'=> 'activated', 'as' => 'distribution.salesperson.role.create',    'uses'=>'DistributorSalespersonRole@createRole']);
        $api->put('/distributor/salesperson/role/{salesmanRoleId}', ['name' => '修改门店角色', 'middleware'=> 'activated', 'as' => 'distribution.salesperson.role.update',    'uses'=>'DistributorSalespersonRole@updateRole']);
        $api->delete('/distributor/salesperson/role/{salesmanRoleId}', ['name' => '删除门店角色', 'middleware'=> 'activated', 'as' => 'distribution.salesperson.role.delete',    'uses'=>'DistributorSalespersonRole@delRole']);

        $api->get('/distribution/getdistance', ['name' => '获取距离配置', 'middleware'=>'activated', 'as' => 'distribution.distance.get',    'uses'=>'Distributor@getDistance']);
        $api->post('/distribution/setdistance', ['name' => '保存距离配置', 'middleware'=>'activated', 'as' => 'distribution.distance.save',    'uses'=>'Distributor@setDistance']);

        //大屏相关接口

        //开屏广告
        $api->post('/shopScreen/advertisement', ['name' => '添加广告', 'middleware'=>[], 'as' => 'distribution.advertisement.add',    'uses'=>'ShopScreen@addAdvertisement']);
        $api->delete('/shopScreen/advertisement/{id}', ['name' => '删除广告', 'middleware'=>[], 'as' => 'distribution.advertisement.del',    'uses'=>'ShopScreen@deleteAdvertisement']);
        $api->put('/shopScreen/advertisement', ['name' => '排序/发布/撤回', 'middleware'=>[], 'as' => 'distribution.advertisement.setsort', 'uses' => 'ShopScreen@updateAdvertisement']);
        $api->get('/shopScreen/advertisement', ['name' => '开屏广告列表', 'middleware'=>[], 'as' => 'distribution.advertisement.getAdvertisements',    'uses'=>'ShopScreen@getAdvertisement']);

        //首页轮播
        $api->post('/shopScreen/slider', ['name' => '轮播图', 'middleware' => [], 'as' => 'distribution.shopScreen.saveSlider', 'uses' => 'ShopScreen@saveSlider']);
        $api->get('/shopScreen/slider', ['name' => '轮播图', 'middleware' => [], 'as' => 'distribution.shopScreen.saveSlider', 'uses' => 'ShopScreen@getSlider']);

        // 店铺围栏
        $api->get('/distributor/geofence', ['name' => '获取店铺围栏', 'as' => 'distribution.geofence.get', 'uses' => 'DistributorGeofenceController@get']);
        $api->post('/distributor/geofence', ['name' => '添加或更新一个店铺围栏', 'as' => 'distribution.geofence.create', 'uses' => 'DistributorGeofenceController@save']);
        $api->delete('/distributor/geofence', ['name' => '删除一个店铺围栏, 或删除该店铺下的所有围栏', 'as' => 'distribution.geofence.delete', 'uses' => 'DistributorGeofenceController@delete']);

        //自提点
        $api->get('/pickuplocation/list', ['name' => '获取自提点列表', 'middleware'=>['activated', 'datapass'], 'as' => 'pickuplocation.list.get', 'uses'=>'PickupLocation@getPickupLocationList']);
        $api->get('/pickuplocation/{id}', ['name' => '获取自提点详情', 'middleware'=>['activated', 'datapass'], 'as' => 'pickuplocation.info.get', 'uses'=>'PickupLocation@getPickupLocationInfo']);
        $api->post('/pickuplocation', ['name' => '新增自提点', 'middleware'=>['activated', 'datapass'], 'as' => 'pickuplocation.create', 'uses'=>'PickupLocation@createPickupLocation']);
        $api->put('/pickuplocation/{id}', ['name' => '更新自提点', 'middleware'=>['activated', 'datapass'], 'as' => 'pickuplocation.update', 'uses'=>'PickupLocation@updatePickupLocation']);
        $api->delete('/pickuplocation/{id}', ['name' => '删除自提点', 'middleware'=>['activated', 'datapass'], 'as' => 'pickuplocation.delete', 'uses'=>'PickupLocation@delPickupLocation']);
        $api->post('/pickuplocation/reldistributor', ['name' => '自提点关联门店', 'middleware'=>['activated', 'datapass'], 'as' => 'pickuplocation.reldistributor', 'uses'=>'PickupLocation@relDistributor']);
        $api->post('/pickuplocation/reldistributor/cancel', ['name' => '自提点取消关联门店', 'middleware'=>['activated', 'datapass'], 'as' => 'pickuplocation.reldistributor.cancel', 'uses'=>'PickupLocation@cancelRelDistributor']);

        // 商家自配配置
        $api->get('/distributor/selfdelivery/setting', ['name' => '获取商家自配送配置信息', 'as' => 'distribution.selfdelivery.setting.get', 'uses' => 'selfDelivery@getSelfDeliverySetting']);
        $api->post('/distributor/selfdelivery/setting', ['name' => '商家自配送配置信息保存', 'as' => 'distribution.selfdelivery.setting.create', 'uses' => 'selfDelivery@setSelfDeliverySetting']);

        //店铺白名单相关路由
        $api->get('/distributor/whitelist/get', ['name' => '获取店铺白名单', 'as' => 'distribution.white.list', 'uses' => 'DistributorWhiteList@getWhiteList']);
        $api->post('/distributor/whitelist/delete', ['name' => '删除白名单', 'as' => 'distribution.white.delete', 'uses' => 'DistributorWhiteList@deleteWhiteList']);
        $api->post('/distributor/whitelist/add', ['name' => '新增白名单', 'as' => 'distribution.white.add', 'uses' => 'DistributorWhiteList@addWhiteList']);
        $api->get('/distributor/whitelist/export', ['name' => '导出白名单', 'as' => 'distribution.white.export', 'uses' => 'DistributorWhiteList@exportWhiteList']);

        // 店铺进店规则设置
        $api->get('/distributor/config/inRule', ['name' => '获取店铺进店规格', 'as' => 'distribution.config.inRule.get', 'uses' => 'DistributionConfig@getInRule']);
        $api->post('/distributor/config/inRule', ['name' => '保存店铺进店规格', 'as' => 'distribution.config.inRule.save', 'uses' => 'DistributionConfig@saveInRule']);

        $api->get('/distributor/getAreaByAddress', ['name' => '根据地址获取地区信息', 'as' => 'shops.getAreaByAddress', 'uses' => 'DistributorShopController@getAreaByAddress']);
    });

    $api->group(['namespace' => 'SalespersonBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/distributor/salesmans', ['name' => '获取店铺导购员列表', 'middleware' => 'activated', 'as' => 'distribution.salesman.list.get', 'uses' => 'SalespersonController@getSalesmanList']);
        $api->put('/distributor/salesman/{salesmanId}', ['name' => '更新店铺导购员', 'middleware' => 'activated', 'as' => 'distribution.salesman.update', 'uses' => 'SalespersonController@updateSalesman']);
        $api->get('/distributor/salesman/role', ['name' => '获取店铺导购员权限集合', 'middleware' => 'activated', 'as' => 'distribution.salesman.role.list', 'uses' => 'SalespersonController@getSalesmanRoleList']);
        $api->put('/distributor/salesman/role/{salesmanId}', ['name' => '更新店铺导购员权限', 'middleware' => 'activated', 'as' => 'distribution.salesman.role.update', 'uses' => 'SalespersonController@updateSalesmanRole']);
        $api->post('/distributor/salesman', ['name' => '新增店铺导购员', 'middleware' => 'activated', 'as' => 'distribution.salesman.add', 'uses' => 'SalespersonController@addSalesman']);
        $api->get('/distributor/salemanCustomerComplaints', ['name' => '获取店铺导购员客诉列表', 'middleware' => 'activated', 'as' => 'distribution.salesmancustomercomplaints.list.get', 'uses' => 'SalespersonController@getSalemanCustomerComplaintsList']);
        $api->post('/distributor/salemanCustomerComplaints', ['name' => '回复店铺导购员客诉列表', 'middleware' => 'activated', 'as' => 'distribution.salesmancustomercomplaints.reply', 'uses' => 'SalespersonController@replySalemanCustomerComplaints']);

        $api->post('/salespersonotice/notice', ['name' => '添加导购员通知', 'middleware' => 'activated', 'as' => 'salesperson.notice.add', 'uses' => 'SalespersonNotice@addNotice']);
        $api->post('/salespersonotice/sendnotice', ['name' => '发送导购员通知', 'middleware' => 'activated', 'as' => 'salesperson.notice.send', 'uses' => 'SalespersonNotice@sendNotice']);
        $api->post('/salespersonotice/withdrawnotice', ['name' => '撤回导购员通知', 'middleware' => 'activated', 'as' => 'salesperson.notice.withdraw', 'uses' => 'SalespersonNotice@withdrawNotice']);
        $api->get('/salespersonotice/list', ['name' => '导购员通知列表', 'middleware' => 'activated', 'as' => 'salesperson.notice.list', 'uses' => 'SalespersonNotice@getNoticeList']);
        $api->get('/salespersonotice/detail', ['name' => '导购员通知详情', 'middleware' => 'activated', 'as' => 'salesperson.notice.info', 'uses' => 'SalespersonNotice@getNoticeDetail']);
        $api->delete('/salespersonotice/notice', ['name' => '删除导购员通知', 'middleware' => 'activated', 'as' => 'salesperson.notice.delete', 'uses' => 'SalespersonNotice@deleteNotice']);
        $api->put('/salespersonotice/notice', ['name' => '修改导购员通知', 'middleware' => 'activated', 'as' => 'salesperson.notice.update', 'uses' => 'SalespersonNotice@updateNotice']);

        $api->get('/profit/statistics', ['name' => '获取分润统计', 'middleware' => 'activated', 'as' => 'profit.statistics.list.get', 'uses' => 'ProfitController@lists']);
        $api->get('/profit/export', ['name' => '导出分润信息', 'middleware' => 'activated', 'as' => 'profit.export', 'uses' => 'ProfitController@exportProfitData']);

        $api->get('/salesperson/task', ['name' => '获取导购任务列表', 'middleware' => 'activated', 'as' => 'salesperson.task.list', 'uses' => 'SalespersonTaskController@lists']);
        $api->get('/salesperson/task/statistics', ['name' => '获取导购任务统计列表', 'middleware' => 'activated', 'as' => 'salesperson.task.statistics', 'uses' => 'SalespersonTaskController@statistics']);
        $api->get('/salesperson/task/{taskId}', ['name' => '获取导购任务详情', 'middleware' => 'activated', 'as' => 'salesperson.task.info', 'uses' => 'SalespersonTaskController@info']);
        $api->post('/salesperson/task', ['name' => '创建导购任务', 'middleware' => 'activated', 'as' => 'salesperson.task.create', 'uses' => 'SalespersonTaskController@create']);
        $api->put('/salesperson/task/{taskId}', ['name' => '修改导购任务', 'middleware' => 'activated', 'as' => 'salesperson.task.update', 'uses' => 'SalespersonTaskController@update']);
        $api->delete('/salesperson/task/{taskId}', ['name' => '取消导购任务', 'middleware' => 'activated', 'as' => 'salesperson.task.cancel', 'uses' => 'SalespersonTaskController@cancel']);

        $api->get('/salesperson/coupon', ['name' => '获取导购优惠券列表', 'middleware' => 'activated', 'as' => 'salesperson.coupon.list', 'uses' => 'SalespersonCouponController@lists']);
        $api->post('/salesperson/coupon', ['name' => '添加导购可发放优惠券', 'middleware' => 'activated', 'as' => 'salesperson.coupon.create', 'uses' => 'SalespersonCouponController@create']);
        $api->delete('/salesperson/coupon/{id}', ['name' => '删除导购优惠券', 'middleware' => 'activated', 'as' => 'salesperson.coupon.delete', 'uses' => 'SalespersonCouponController@delete']);

    //门店人员
        $api->post('/shops/salesperson', ['name' => '添加门店人员', 'as' => 'shop.salesperson.create', 'uses'=>'Salesperson@createSalesperson']);
        $api->get('/shops/salesperson', ['name' => '获取所有门店人员列表', 'middleware' => ['datapass'], 'as' => 'shop.salesperson.lists', 'uses'=>'Salesperson@lists']);
        $api->delete('/shops/salesperson/{salespersonId}', ['name' => '删除门店人员', 'as' => 'shop.salesperson.del', 'uses'=>'Salesperson@deleteSalesperson']);
        $api->put('/shops/salesperson/{salespersonId}', ['name' => '更新门店人员', 'as' => 'shop.salesperson.update', 'uses'=>'Salesperson@updateSalesperson']);

        $api->get('/shops/saleperson/shoplist', ['name' => '获取管理员管理的门店数据', 'as' => 'shop.salesperson.update', 'uses'=>'Salesperson@getRelShopList']);
        $api->get('/shops/saleperson/getinfo', ['name' => '获取门店人员详细信息', 'middleware' => ['datapass'], 'as' => 'shop.salesperson.getinfo', 'uses'=>'Salesperson@getSalespersonInfo']);

        $api->get('/shops/saleperson/signlogs', ['name' => '获取签到记录', 'as' => 'shop.salesperson.signlogs', 'uses'=>'Salesperson@getSignlogs']);
    });
});

