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

    // 分账相关api
    $api->group(['namespace' => 'ChinaumsPayBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/division/list', ['name' => '获取分账单列表', 'as' => 'division.list', 'uses' => 'Division@getList']);
        $api->get('/division/detail/list', ['name' => '获取分账单详情列表', 'as' => 'division.detail.list', 'uses' => 'Division@getDetailList']);
        $api->get('/division/errorlog/list', ['name' => '获取分账失败列表', 'as' => 'division.errorlog.list', 'uses' => 'Division@errorlogList']);
        $api->put('/division/errorlog/resubmit/{id}', ['name' => '分账失败重试', 'as' => 'division.errorlog.resubmit', 'uses' => 'Division@errrorlogResubmit']);
        $api->get('/division/exportdata', ['name' => '分账单导出', 'as' => 'division.exportdata', 'uses' => 'Division@exportDivisionData']);
        $api->get('/division/detail/exportdata', ['name' => '分账单明细导出', 'as' => 'division.detail.exportdata', 'uses' => 'Division@exportDivisionDetailData']);
    });
});
