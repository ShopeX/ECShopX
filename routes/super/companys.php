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
        // 获取公司列表
        $api->get('/companys/list', ['as' => 'super.companys.list', 'uses'=>'Companys@getCompanysList']);
        // 更新公司信息
        $api->put('/companys', ['as' => 'super.companys.update', 'uses'=>'Companys@updateCompany']);
        $api->get('/companys/logs', ['as' => 'super.companys.logs', 'uses'=>'Companys@getCompanysLogs']);
        // 设置店务端用户协议
        $api->post('/distribution/protocol', ['as' => 'super.companys.distributionProtocol.update', 'uses' => 'Companys@UpdateDistributionProtocol']);
    });
});
