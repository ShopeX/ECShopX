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
    // 企业相关信息
    $api->group(['prefix' => '/admin/wxapp', 'namespace' => 'ReservationBundle\Http\AdminApi\V1\Action', 'middleware' => ['api.auth', 'distributorlog'], 'providers' => 'adminwxapp'], function($api) {
        $api->get('/reservation/list', ['name' => '获取每天每时每刻的预约记录', 'as' => 'admin.wxapp.reservation.list',  'uses'=>'Reservation@getReservationList']);
        $api->get('/reservation/getdate', ['name' => '获取可预约的日期列表及资源位列表', 'as' => 'admin.wxapp.reservation.getdate',  'uses'=>'Reservation@getDateList']);
        $api->get('/resourcelevel/list', ['name' => '获取指定门店的所有资源位', 'as' => 'admin.wxapp.resource.level.list',  'uses'=>'Reservation@getResourceLevelList']);
        //提交预约信息
        $api->post('/reservation', ['name' => '提交预约信息', 'as' => 'admin.wxapp.reservation.create',  'uses'=>'Reservation@createReservation']);
        //获取可被预约的项目
        $api->get('/getRightsList', ['name' => '获取可被预约的项目', 'as' => 'admin.wxapp.get.rights.list',  'uses'=>'Reservation@getUserRightsListData']);
        //获取可被预约的时段
        $api->get('/getTimeList', ['name' => '获取可被预约的时段', 'as' => 'admin.wxapp.get.rights.list',  'uses'=>'Reservation@getTimeList']);
        //修改预约状态
        $api->post('/reservation/updateStatus', ['name' => '修改预约状态', 'as' => 'admin.wxapp.update.status',  'uses'=>'Reservation@updateRecordStatus']);
    });
});


