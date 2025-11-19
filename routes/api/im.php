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
    $api->group(['namespace' => 'ImBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/im/meiqia', ['name' => '获取im配置', 'as' => 'im.meiqia.info', 'uses' => 'Im@meiqiaInfo']);
        $api->post('/im/meiqia', ['name' => '保存im配置', 'as' => 'im.meiqia.save', 'uses' => 'Im@meiqiaUpdate']);
        $api->get('/im/meiqia/distributor/{distributor_id}', ['name' => '获取店铺美洽客服配置', 'as' => 'im.meiqia.distributor.get', 'uses' => 'Im@getDistributorMeiQiaSetting']);
        $api->put('/im/meiqia/distributor/{distributor_id}', ['name' => '设置店铺美洽客服配置', 'as' => 'im.meiqia.distributor.set', 'uses' => 'Im@setDistributorMeiQia']);

        //一洽客服配置
        $api->get('/im/echat', ['name' => '获取一洽配置', 'as' => 'im.echat.info', 'uses' => 'EChat@getInfo']);
        $api->post('/im/echat', ['name' => '保存一洽配置', 'as' => 'im.echat.save', 'uses' => 'EChat@saveInfo']);
    });
});