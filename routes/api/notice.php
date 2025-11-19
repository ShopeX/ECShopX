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

    $api->group(['namespace' => 'SuperAdminBundle\Http\Api\V1\Action','middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function($api) {

        // 获取公告列表
        $api->get('/notice/list', ['name'=>'获取公告列表','as' => 'super.notice.list', 'uses'=>'ShopNotice@getShopNoticeList']);

        // 获取公告详情
        $api->get('/notice/{notice_id}', ['name'=>'获取公告详情','as' => 'super.notice.detail', 'uses'=>'ShopNotice@getShopNoticeInfo']);
    });
});
