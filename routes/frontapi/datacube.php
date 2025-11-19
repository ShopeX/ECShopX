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
    $api->group(['prefix' => 'h5app', 'namespace' => 'DataCubeBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {
        // 统计浏览人数-已支持h5
        $api->post('/wxapp/track/viewnum',    ['as' => 'front.wxapp.track.viewnum', 'uses' => 'Track@addViewNum']);
    });
    $api->group(['prefix' => 'h5app', 'namespace' => 'DataCubeBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        $api->get('/wxapp/datacube/Deliverystaffdata',    ['as' => 'front.wxapp.datacube.Deliverystaffdata', 'uses' => 'DeliveryStaff@getDeliveryStaffData']);
        $api->get('/wxapp/datacube/DeliverystaffdataDetail',    ['as' => 'front.wxapp.datacube.Deliverystaffdata', 'uses' => 'DeliveryStaff@getDeliverystaffdataDetail']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app端、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
