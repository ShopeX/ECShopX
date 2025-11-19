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
        // 获取物流公司列表
        $api->get('/logistics/list', ['as' => 'super.logistics.list', 'uses'=>'Logistics@getLogisticsList']);
        // 更新物流公司信息
        $api->put('/logistics', ['as' => 'super.logistics.update', 'uses'=>'Logistics@updateLogistics']);
        $api->delete('/logistics/{id}', ['as' => 'super.logistics.delete', 'uses'=>'Logistics@deleteLogistics']);
        $api->put('/logistics/del', ['as' => 'super.logistics.del', 'uses'=>'Logistics@batchdeleteLogistics']);
        $api->post('/logistics', ['as' => 'super.logistics.create', 'uses'=>'Logistics@createLogistics']);
        $api->get('/logistics/init', ['as' => 'super.logistics.init', 'uses'=>'Logistics@initLogistics']);
    });
});
