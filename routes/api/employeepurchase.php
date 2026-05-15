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
    $api->group(['namespace' => 'EmployeePurchaseBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
    	// 添加企业白名单
        $api->post('/enterprise', ['name'=> '添加企业','middleware'=>'activated', 'as' => 'employeepurchase.enterprise.add', 'uses' =>'Enterprise@create']);
        // 更新企业白名单
        $api->put('/enterprise/{enterpriseId}', ['name'=> '更新企业','middleware'=>'activated', 'as' => 'employeepurchase.enterprise.update', 'uses' =>'Enterprise@update']);
        // 删企业白名单
        $api->delete('/enterprise/{enterpriseId}', ['name'=> '删企业','middleware'=>'activated', 'as' => 'employeepurchase.enterprise.delete', 'uses' =>'Enterprise@delete']);
        // 获取企业白名单列表
        $api->get('/enterprise', ['name'=> '获取企业列表','middleware'=>'activated', 'as' => 'employeepurchase.enterprise.list', 'uses' =>'Enterprise@getEnterprisesList']);
        // 获取企业白名单详情
        $api->get('/enterprise/{enterpriseId}', ['name'=> '获取企业详情','middleware'=>'activated', 'as' => 'employeepurchase.enterprise.get', 'uses' =>'Enterprise@getEnterpriseInfo']);
        // 获取企业小程序码
        $api->get('/enterprise/qrcode/{enterpriseId}', ['name'=> '获取企业小程序码','middleware'=>'activated', 'as' => 'employeepurchase.enterprise.qrcode.get', 'uses' =>'Enterprise@getEnterpriseQrcode']);
        //更新状态
        $api->post('/enterprise/status', ['name'=> '更新企业状态','middleware'=>'activated', 'as' => 'employeepurchase.enterprise.status.update', 'uses' =>'Enterprise@updateStatus']);

        $api->post('/enterprise/sort', ['name' => '更新排序', 'as' => 'employeepurchase.enterprise.sort.set', 'uses' => 'Enterprise@setSort']);

        $api->post('/enterprise/sendtestemail', ['name'=> '发送测试邮件','middleware'=>'activated', 'as' => 'employeepurchase.enterprise.sendtestemail', 'uses' =>'Enterprise@sendTestemail']);

        // 获取企业员工列表
        $api->get('/employees', ['name'=> '获取企业员工列表','middleware'=>'activated', 'as' => 'employeepurchase.employee.list', 'uses' =>'Employee@getList']);
        // 获取企业员工信息
        $api->get('/employee/{employeeId}', ['name'=> '获取企业员工信息','middleware'=>'activated', 'as' => 'employeepurchase.employee.info', 'uses' =>'Employee@getInfo']);
        // 添加企业员工
        $api->post('/employee', ['name'=> '添加企业员工','middleware'=>'activated', 'as' => 'employeepurchase.employee.create', 'uses' =>'Employee@create']);
        // 更新企业员工信息
        $api->put('/employee/{employeeId}', ['name'=> '更新企业员工信息','middleware'=>'activated', 'as' => 'employeepurchase.employee.update', 'uses' =>'Employee@update']);
        // 删除企业员工信息
        $api->delete('/employee/{employeeId}', ['name'=> '删除企业员工信息','middleware'=>'activated', 'as' => 'employeepurchase.employee.delete', 'uses' =>'Employee@delete']);
        $api->get('/employees/export', ['name'=> '导出企业员工信息','middleware'=>'activated', 'as' => 'employeepurchase.employees.export', 'uses' =>'Employee@exportData']);


        // 获取活动商品列表
        $api->get('/employeepurchase/activity/items', ['name'=> '获取活动商品列表','middleware'=>'activated', 'as' => 'employeepurchase.activity.items.list', 'uses' =>'Activity@getActivityItemList']);
        // 添加活动商品
        $api->post('/employeepurchase/activity/items', ['name'=> '添加活动商品','middleware'=>'activated', 'as' => 'employeepurchase.activity.items.add', 'uses' =>'Activity@addActivityItems']);
        // 选择活动商品规格
        $api->post('/employeepurchase/activity/specitems', ['name'=> '选择活动商品规格','middleware'=>'activated', 'as' => 'employeepurchase.activity.spec_items.update', 'uses' =>'Activity@selectActivitySpecItems']);
        // 更新活动商品价格库存等
        $api->put('/employeepurchase/activity/items', ['name'=> '更新活动商品价格库存等','middleware'=>'activated', 'as' => 'employeepurchase.activity.items.update', 'uses' =>'Activity@updateActivityItems']);
        // 删除活动商品
        $api->delete('/employeepurchase/activity/{activityId}/item/{itemId}', ['name'=> '删除活动商品','middleware'=>'activated', 'as' => 'employeepurchase.activity.items.delete', 'uses' =>'Activity@deleteActivityItems']);

        // 获取员工内购活动列表
        $api->get('/employeepurchase/activities', ['name'=> '获取员工内购活动列表','middleware'=>'activated', 'as' => 'employeepurchase.activity.list', 'uses' =>'Activity@getActivityList']);
        // 内购活动亲友数据
        $api->get('/employeepurchase/activity/users', ['name'=> '获取员工内购活动亲友列表','middleware'=>'activated', 'as' => 'employeepurchase.activity.users', 'uses' =>'Activity@getActivityUsers']);
        // 活动各企业行为流水实时聚合（须写在 /activity/{activityId} 之前，避免路径被误匹配）
        $api->get('/employeepurchase/activity/{activityId}/enterprise-behavior-stats', ['name'=> '活动企业行为统计','middleware'=>'activated', 'as' => 'employeepurchase.activity.enterprise_behavior_stats', 'uses' =>'Activity@getActivityEnterpriseBehaviorStats']);
        // 下载活动企业行为统计（Excel）
        $api->get('/employeepurchase/activity/{activityId}/enterprise-behavior-stats/download', ['name'=> '下载活动企业行为统计','middleware'=>'activated', 'as' => 'employeepurchase.activity.enterprise_behavior_stats.download', 'uses' =>'Activity@downloadActivityEnterpriseBehaviorStats']);
        // 下载活动参与企业扫码小程序码（Excel）
        $api->get('/employeepurchase/activity/{activityId}/download-qrcode', ['name'=> '下载活动企业小程序码','middleware'=>'activated', 'as' => 'employeepurchase.activity.download_qrcode', 'uses' =>'Activity@downloadActivityQrcode']);
        // 获取员工内购活动详情
        $api->get('/employeepurchase/activity/{activityId}', ['name'=> '获取员工内购活动详情','middleware'=>'activated', 'as' => 'employeepurchase.activity.info', 'uses' =>'Activity@getActivityInfo']);
        // 批量生成口令编码：activity_id 可选（新建活动可不传）；传则按活动内去重，不传则按公司下去重
        $api->post('/employeepurchase/passphrase-codes/generate', ['name'=> '批量生成口令码','middleware'=>'activated', 'as' => 'employeepurchase.passphrase_codes.generate', 'uses' =>'Activity@generatePassphraseCodes']);
        // 兼容：活动已存在时仍可用路径传 activity_id
        $api->post('/employeepurchase/activity/{activityId}/passphrase-codes/generate', ['name'=> '批量生成活动口令码(路径)','middleware'=>'activated', 'as' => 'employeepurchase.activity.passphrase_codes.generate', 'uses' =>'Activity@generatePassphraseCodesByActivity']);
        // 创建员工内购活动
        $api->post('/employeepurchase/activity', ['name'=> '创建员工内购活动','middleware'=>'activated', 'as' => 'employeepurchase.activity.create', 'uses' =>'Activity@createActivity']);
        // 更新员工内购活动
        $api->put('/employeepurchase/activity/{activityId}', ['name'=> '更新员工内购活动','middleware'=>'activated', 'as' => 'employeepurchase.activity.update', 'uses' =>'Activity@updateActivity']);
        // 设置活动是否共享库存
        $api->post('/employeepurchase/activity/if_share_store', ['name'=> '设置活动是否共享库存','middleware'=>'activated', 'as' => 'employeepurchase.activity.if_share_store.set', 'uses' =>'Activity@seIfShareStore']);
        // 取消内购活动
        $api->post('/employeepurchase/activity/cancel/{activityId}', ['name'=> '取消内购活动','middleware'=>'activated', 'as' => 'employeepurchase.activity.cancel', 'uses' =>'Activity@cancelActivity']);
        // 暂停内购活动
        $api->post('/employeepurchase/activity/suspend/{activityId}', ['name'=> '暂停内购活动','middleware'=>'activated', 'as' => 'employeepurchase.activity.suspend', 'uses' =>'Activity@suspendActivity']);
        // 重新开始暂停的内购活动
        $api->post('/employeepurchase/activity/active/{activityId}', ['name'=> '重新开始暂停的内购活动','middleware'=>'activated', 'as' => 'employeepurchase.activity.active', 'uses' =>'Activity@activeActivity']);
        // 结束内购活动
        $api->post('/employeepurchase/activity/end/{activityId}', ['name'=> '结束内购活动','middleware'=>'activated', 'as' => 'employeepurchase.activity.end', 'uses' =>'Activity@endActivity']);
        // 提前开始内购活动
        $api->post('/employeepurchase/activity/ahead/{activityId}', ['name'=> '提前开始内购活动','middleware'=>'activated', 'as' => 'employeepurchase.activity.ahead', 'uses' =>'Activity@aheadActivity']);

        // 企业购门店首页（按 distributor_id；经销商仅本店）
        $api->get('/employeepurchase/store-home-page', ['name' => '内购模版列表', 'middleware' => 'activated', 'as' => 'employeepurchase.store_home_page.list', 'uses' => 'StoreHomePage@getList']);
        $api->post('/employeepurchase/store-home-page', ['name' => '创建内购模版', 'middleware' => 'activated', 'as' => 'employeepurchase.store_home_page.create', 'uses' => 'StoreHomePage@create']);
        $api->get('/employeepurchase/store-home-page/{id}', ['name' => '内购模版详情', 'middleware' => 'activated', 'as' => 'employeepurchase.store_home_page.info', 'uses' => 'StoreHomePage@getInfo']);
        $api->put('/employeepurchase/store-home-page/{id}', ['name' => '更新内购模版', 'middleware' => 'activated', 'as' => 'employeepurchase.store_home_page.update', 'uses' => 'StoreHomePage@update']);
        $api->delete('/employeepurchase/store-home-page/{id}', ['name' => '删除内购模版', 'middleware' => 'activated', 'as' => 'employeepurchase.store_home_page.delete', 'uses' => 'StoreHomePage@delete']);
    });
});