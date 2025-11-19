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
    //商品数据统计
    $api->group(['namespace' => 'SuperAdminBundle\Http\SuperApi\V1\Action', 'prefix' => 'superadmin',
                 'middleware' => ['superguard', 'api.auth'], 'providers' => 'jwt'], function($api) {
        $api->get('/datacube/goodsdata', ['name' => '获取商品统计数据', 'uses' => 'DataCube@getGoodsData']);
        $api->get('/datacube/companydata', ['name' => '获取商城统计数据', 'uses' => 'DataCube@getCompanyData']);
        $api->get('/datacube/exportloglist', ['name' => '获取文件导出列表', 'uses'=> 'ExportLog@getExportLogList']);
    });
});

