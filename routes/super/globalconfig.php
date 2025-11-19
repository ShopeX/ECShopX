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

$api->version('v1', function ($api) {
    //商品数据统计
    $api->group(['namespace' => 'SuperAdminBundle\Http\SuperApi\V1\Action', 'prefix' => 'superadmin', 'middleware' => ['superguard', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        $api->get('/globalconfig/getinfo', ['name' => '获取全局配置', 'uses' => 'Globalconfig@getinfo']);
        $api->post('/globalconfig/saveset', ['name' => '设置全局配置', 'uses' => 'Globalconfig@saveset']);
    });
});

