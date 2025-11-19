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
    $api->group(['prefix' => 'h5app', 'namespace' => 'ImBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {
        $api->get('/wxapp/im/meiqia', ['name' => '获取im配置', 'as' => 'im.meiqia.info', 'uses' => 'Im@meiqiaInfo']);
        $api->get('/wxapp/im/meiqia/distributor/{distributor_id}', ['name' => '获取店铺美洽客服配置', 'as' => 'im.meiqia.distributor.get.front', 'uses' => 'Im@getDistributorMeiQiaSetting']);

        //获取一洽配置
        $api->get('/wxapp/im/echat', ['name' => '获取一洽配置', 'as' => 'im.echat.info', 'uses' => 'EChat@getInfo']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app端、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
