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
    $api->group(['namespace' => 'SuperAdminBundle\Http\SuperApi\V1\Action', 'prefix'=>'superadmin', 'middleware' => ['superguard', 'api.auth'], 'providers' => 'jwt'], function($api) {
        // 获取公告列表
        $api->get('/notice/list', ['as' => 'superadmin.notice.list', 'uses'=>'ShopNotice@getShopNoticeList']);

        // 新增公告
        $api->post('/notice/add', ['as' => 'superadmin.notice.add', 'uses'=>'ShopNotice@addShopNotice']);

        // 编辑公告
        $api->put('/notice/update', ['as' => 'superadmin.notice.update', 'uses'=>'ShopNotice@updateShopNotice']);

        // 删除公告
        $api->delete('/notice/delete/{notice_id}', ['as' => 'superadmin.notice.delete', 'uses'=>'ShopNotice@deleteShopNotice']);

        // 获取公告详情
        $api->get('/notice/{notice_id}', ['as' => 'superadmin.notice.detail', 'uses'=>'ShopNotice@getShopNoticeInfo']);
    });
});
