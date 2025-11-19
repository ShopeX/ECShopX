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
    // 企业相关信息,旧的pms后台登录
    $api->group(['namespace' => 'EspierBundle\Http\SuperApi\V1\Action', 'prefix'=>'super/admin'], function($api) {
        $api->post('login', [ 'as' => 'super.admin.login',  'uses'=>'SuperAdmin@login']);
    });

    // 平台账号管理
    $api->group(['namespace' => 'SuperAdminBundle\Http\SuperApi\V1\Action', 'prefix' => 'superadmin', 'middleware' => ['superguard', 'api.auth'], 'providers' => 'jwt'], function($api) {
        // 添加平台管理员账号
        $api->post('/account/add', ['as' => 'super.account.add', 'uses' => 'Accounts@addAccount']);
        // 修改管理员密码
        $api->put('/account/updatePassword', ['as' => 'super.account.updatePassword', 'uses' => 'Accounts@updatePassword']);
    });
    // 平台后台登录相关
    $api->group(['prefix'=>'superadmin'], function($api) {
        // 登录
        $api->post('/account/login', function () use ($api) {
            $credentials = app('request')->only('login_name', 'password');
            $token = app('auth')->guard('superapi')->attempt($credentials);
            return response()->json(['data'=>['token'=>$token]]);
        });
        // 刷新token
        $api->get('/account/token/refresh', ['middleware'=>'jwt.refresh', function () use ($api) {
            return response()->json(['data'=>['result'=>true]]);
        }]);
    });
});

