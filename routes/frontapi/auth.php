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
    $api->group(['prefix' => 'h5app', 'namespace' => 'EspierBundle\Http\FrontApi\V1\Action'], function ($api) {
        // 上传相关
        $api->get('/wxapp/espier/image_upload_token', ['middleware' => ['dingoguard:h5app', 'api.auth'], 'as' => 'espier.h5app.image.uptoken.get',  'uses'=>'UploadFile@getPicUploadToken']);
        $api->post('/wxapp/espier/image_upload', ['middleware' =>['dingoguard:h5app', 'api.auth'], 'as' => 'espier.h5app.image.upload',  'uses'=>'UploadFile@uploadOssImage']);

        $api->get('/wxapp/espier/address', ['middleware' => 'frontnoauth:h5app', 'as' => 'espier.h5app.address.get',  'uses'=>'AddressController@get']);
        $api->post('/wxapp/espier/upload', ['middleware' => ['dingoguard:h5app', 'api.auth'], 'as' => 'espier.wxapp.upload',  'uses'=>'UploadFile@uploadImage']);
        $api->post('/wxapp/espier/uploadlocal', ['middleware' => 'frontnoauth:h5app', 'as' => 'espier.wxapp.uploadlocal',  'uses'=>'UploadFile@uploadeLocalImage']);
        $api->get('/wxapp/espier/config/request_field_setting', ['middleware' => 'frontnoauth:h5app', 'as' => 'espier.wxapp.request_field_setting', 'uses'=>'ConfigRequestFieldsController@getConfig']);
    });
    $api->group(['prefix'=>'h5app', 'namespace' => 'WechatBundle\Http\FrontApi\V1\Action'], function ($api) {
        $api->post('/wxapp/oauthlogin', [ 'as' => 'front.wxapp.oauth.login',  'uses'=>'Wxapp@checkOauthLogin']);
        $api->post('/wxapp/getopenid', [ 'as' => 'front.wxapp.oauth.openid',  'uses'=>'Wxapp@getUserOpentIdAndUnionid']);
    });
    $api->group(['prefix'=>'h5app'], function ($api) {
        // 登录-已支持h5
        $api->post('/wxapp/new_login', ['middleware' => 'frontnoauth:h5app', 'as' => 'front.wxapp.login', 'uses'=>'EspierBundle\Http\FrontApi\V1\Action\LoginController@login']);
        $api->post('/wxapp/login', function () use ($api) {
            // $credentials = app('request')->only('username', 'password', 'company_id', 'auth_type', 'check_type', 'vcode');
            $credentials = app('request')->input();
            $credentials['origin'] = app('request')->header('origin');
            $token = app('auth')->guard('h5api')->attempt($credentials);
            return response()->json(['data'=>['token'=>$token]]);
        });
        //刷新token
        $api->get('/wxapp/token/refresh', ['middleware'=>'jwt.refresh', function () use ($api) {
            return response()->json(['data'=>['result'=>true]]);
        }]);

        $api->post('/wxapp/merchant/login', function () use ($api) {
            $credentials = app('request')->only('mobile', 'vcode', 'company_id');
            $credentials['origin'] = app('request')->header('origin');
            $token = app('auth')->guard('merchantapi')->attempt($credentials);
            return response()->json(['data'=>['token'=>$token]]);
        });
    });
    $api->group(['prefix' => 'h5app', 'namespace' => 'WechatBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {
        // h获取微信登录跳转信息
        $api->get('/wxapp/oauth/getredirecturl', [ 'as' => 'front.wxapp.redirecturl',  'uses'=>'Wxapp@oauthRedirectUrl']);
        $api->get('/wxapp/oauth/getopenid', [ 'as' => 'front.wxapp.openid',  'uses'=>'Wxapp@getOpenId']);
        $api->post('/wxapp/oauth/login/authorize', [ 'as' => 'front.wxapp.oauthlogin.authorize',  'uses'=>'Wxapp@authorizeOauthLogin']);
        $api->get('/wxapp/oauth/login/valid', [ 'as' => 'front.wxapp.oauthlogin.valid',  'uses'=>'Wxapp@validOauthLogin']);
    });

    // 企业微信
    $api->group(['prefix' => 'h5app', 'namespace' => 'CompanysBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function($api) {
        // 小程序登录
        $api->post('/wxapp/workwechatlogin', [ 'as' => 'h5app.wxapp.workwechatlogin',  'uses'=>'Wxapp@workwechatlogin']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app端、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
