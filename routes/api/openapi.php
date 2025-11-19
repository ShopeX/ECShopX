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
    $api->group(['prefix' => '/setting/openapi', 'namespace' => 'OpenapiBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function ($api) {
//        $api->get('/external', ['name' => '获取外部请求配置', 'middleware' => 'activated', 'as' => 'openapi.external.setting.get', 'uses' => 'ExternalSettingController@getConfig']);
//        $api->post('/external', ['name' => '存储外部请求配置', 'middleware' => 'activated', 'as' => 'openapi.external.setting.save', 'uses' => 'ExternalSettingController@setConfig']);

        $api->get('/developer', ['name' => '获取开发配置', 'middleware' => 'activated', 'as' => 'openapi.developer.info', 'uses' => 'DeveloperController@info']);
//        $api->post('/developer', ['name' => '修改开发配置', 'middleware' => 'activated', 'as' => 'openapi.developer.update', 'uses' => 'DeveloperController@update']);
    });
});