<?php

/*
|--------------------------------------------------------------------------
| shopex 接口
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */

$api->version('v1', function($api) {

    // 内部调用路由
    $api->group(['namespace' => 'OpenapiBundle\Services','middleware' => ['OpenapiCommonCheck', 'handleResponse']], function($api) {
        // 重置密码后退出登陆
        $api->any('openapi/exc.operator.resetpwd', ['as' => 'openapi.api',  'uses'=>'run@process']);
    });

    $api->group(['namespace' => 'OpenapiBundle\Services','middleware' => ['OpenapiCheck', 'handleResponse']], function($api) {
        // shopex api
        $api->any('openapi', ['as' => 'openapi.api',  'uses'=>'run@process']);
        $api->any('openapi/{method}', ['as' => 'openapi.api',  'uses'=>'run@process']);
    });
    $api->group(['namespace' => 'ThirdPartyBundle\Http\ThirdApi\V1\Action'], function($api) {
        $api->any('openapi/dada/callback/{company_id}', ['as' => 'dada.callback.api' ,'middleware' => ['DadaApiCheck'], 'uses'=>'DadaCallback@updateOrderStatus']);
        $api->any('openapi/shansong/callback/{company_id}', ['as' => 'shansong.callback.api',  'uses'=>'ShansongCallback@updateOrderStatus']);
    });
});

