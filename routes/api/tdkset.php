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


$api->version('v1', function ($api) {
    // TDK全局设置
    $api->group(['namespace' => 'TdksetBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/pcdecoration/tdkglobalset', ['name' => '获取TDK全局配置信息', 'as' => 'pcdecoration.tdkglobalset.info', 'uses' => 'TdkGlobalSet@getInfo']);
        $api->post('/pcdecoration/tdkglobalset', ['name' => 'TDK全局信息添加&修改', 'as' => 'pcdecoration.tdkglobalset.save', 'uses' => 'TdkGlobalSet@Save']);
    });

    // 特定页面设置
    $api->group(['namespace' => 'TdksetBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/pcdecoration/tdkgivenset/{type}', ['name' => '获取TDK特定页面配置信息', 'as' => 'pcdecoration.tdkgivenset.info', 'uses' => 'TdkGivenSet@getInfo']);
        $api->post('/pcdecoration/tdkgivenset/{type}', ['name' => 'TDK特定页面信息添加&修改', 'as' => 'pcdecoration.tdkgivenset.save', 'uses' => 'TdkGivenSet@Save']);
    });

});
