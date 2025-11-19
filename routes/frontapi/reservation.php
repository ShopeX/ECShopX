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

/* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ taro小程序、h5、app端、pc端 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
$api->version('v1', function ($api) {
    $api->group(['namespace' => 'ReservationBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:wechat', 'api.auth'], 'providers' => 'wxapp'], function ($api) {
        $api->post('/wxapp/reservation',      ['name' => '用户提交预约数据', 'as' => 'reservation.add',  'uses' => 'Reservation@createReservation']);
        $api->get('/wxapp/reservation/dateDay',      ['name' => '获取可预约的具体日期', 'as' => 'reservation.date.get',  'uses' => 'Reservation@getReservationDate']);
        $api->get('/wxapp/reservation/recordlist',      ['name' => '获取预约记录列表', 'as' => 'reservation.get.recordlist', 'uses' => 'Reservation@getRecordList']);
        $api->get('/wxapp/reservation/timelist',      ['name' => '获取可预约的时段', 'as' => 'reservation.get.timedata', 'uses' => 'Reservation@getTimelist']);
        $api->get('/wxapp/reservation/getCount',      ['name' => '获取指定项目的预约量', 'as' => 'reservation.get.count', 'uses' => 'Reservation@getRecordCount']);
        $api->get('/wxapp/can/reservation/rights',      ['name' => '获取可用预约项目', 'as' => 'reservation.get.can.rights', 'uses' => 'Reservation@getCanReservationRights']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app端、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
