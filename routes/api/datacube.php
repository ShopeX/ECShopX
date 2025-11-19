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
    // 来源相关api
    $api->group(['namespace' => 'DataCubeBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/datacube/sources',               ['name' => '添加来源', 'as' => 'source.create', 'uses' => 'Sources@createSources']);
        $api->get('/datacube/sources',                ['name' => '获取来源列表', 'as' => 'source.list',   'uses' => 'Sources@getSourcesList']);
        $api->get('/datacube/sources/{source_id}',    ['name' => '获取来源详情', 'as' => 'source.detail', 'uses' => 'Sources@getSourcesDetail']);
        $api->delete('/datacube/sources/{source_id}', ['name' => '删除来源', 'as' => 'source.delete', 'uses' => 'Sources@deleteSources']);
        $api->put('/datacube/sources/{source_id}',    ['name' => '更新来源', 'as' => 'source.update', 'uses' => 'Sources@updateSources']);
        $api->get('/datacube/companydata',    ['name' => '获取商城统计数据', 'as' => 'datacube.company.data', 'uses' => 'CompanyData@getCompanyData']);
        $api->get('/datacube/distributordata',    ['name' => '获取商城门店统计数据', 'as' => 'datacube.distributor.data', 'uses' => 'DistributorData@getDistributorData']);
        $api->get('/datacube/goodsdata',    ['name' => '获取商品统计数据', 'as' => 'datacube.goods.data', 'uses' => 'GoodsData@getGoodsData']);
        $api->post('/datacube/savetags',     ['name' => '来源绑定会员标签', 'as' => 'source.savetags', 'uses' => 'Sources@saveSourceTags']);
        $api->get('/datacube/deliverystaffdata',    ['name' => '获取配送员统计列表', 'as' => 'datacube.deliverystaff.data', 'uses' => 'DeliveryStaffData@getDeliveryStaffData']);
        $api->get('/datacube/Deliverystaffdata/export',    ['name' => '获取配送员统计导出', 'as' => 'datacube.deliverystaff.data.export', 'uses' => 'DeliveryStaffData@exportDeliverystaffdata']);

    });

    // 来源监控相关api
    $api->group(['namespace' => 'DataCubeBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/datacube/monitors',                        ['name' => '获取页面监控列表', 'as' => 'monitors.list',              'uses' => 'SourcesMonitors@getSourcesMonitors']);
        $api->get('/datacube/monitors/{monitor_id}',           ['name' => '获取监控详情', 'as' => 'monitors.detail',            'uses' => 'SourcesMonitors@getMonitorsDetail']);
        $api->delete('/datacube/monitors/{monitor_id}',        ['name' => '删除监控页面', 'as' => 'monitors.delete',            'uses' => 'SourcesMonitors@deleteMonitors']);
        $api->post('/datacube/monitors',                       ['name' => '添加监控链接', 'as' => 'monitors.add',               'uses' => 'SourcesMonitors@addMonitors']);
        $api->post('/datacube/monitorsRelSources',             ['name' => '监控页面关联来源', 'as' => 'monitors.relsources.detail', 'uses' => 'SourcesMonitors@relSources']);
        $api->get('/datacube/monitorsRelSources/{monitor_id}', ['name' => '获取监控页面关联来源信息', 'as' => 'monitors.relsources.list',   'uses' => 'SourcesMonitors@getRelSources']);
        $api->delete('/datacube/monitorsRelSources/{monitor_id}/{source_id}', ['name' => '删除监控页面的某个来源', 'as' => 'monitors.relsources.delete', 'uses' => 'SourcesMonitors@deleteRelSources']);
        $api->get('/datacube/monitorsstats',                   ['name' => '获取监控页面的来源统计', 'as' => 'monitors.stats',             'uses' => 'SourcesMonitors@getStats']);
    });

    // 来源监控相关api,不需要权限
    $api->group(['namespace' => 'DataCubeBundle\Http\Api\V1\Action'], function($api) {
        $api->get('/datacube/monitorsWxaCode64',     ['name' => '获取监控的小程序码参数', 'as' => 'monitors.wxacode64',     'uses' => 'SourcesMonitors@getMonitorWxaCode64']);
        $api->get('/datacube/monitorsWxaCodeStream', ['name' => '获取监控的小程序码流信息', 'as' => 'monitors.wxacodestream', 'uses' => 'SourcesMonitors@getMonitorWxaCodeStream']);
    });

    // 小程序页面信息api
    $api->group(['namespace' => 'DataCubeBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/datacube/miniprogram/pages', ['name' => '获取小程序的页面及参数信息', 'as' => 'miniprogram.pages', 'uses' => 'MiniProgram@getPages']);
    });

});
