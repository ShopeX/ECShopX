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
    $api->group(['namespace' => 'SuperAdminBundle\Http\SuperApi\V1\Action', 'prefix'=>'superadmin', 'middleware' => ['superguard', 'api.auth'], 'providers' => 'jwt'], function($api) {
        // 品牌商城和超级管理员账号开通
        $api->post('/operator/open', ['as' => 'super.operators.open', 'uses'=>'Operators@open']);
        // 更新管理员账号信息
        $api->put('/operator', ['as' => 'super.operators.update', 'uses'=>'Operators@updateOperator']);
    });
});
