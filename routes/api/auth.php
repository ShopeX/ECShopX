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
    //登录
//    $api->post('/operator/login',['middleware'=>'shoplogin', function () use ($api) {
//        $credentials = app('request')->only('username', 'password', 'logintype', 'product_model', 'agreement_id');
//        $token = app('auth')->guard('api')->attempt($credentials);
//        return response()->json(['data'=>['token'=>$token]]);
//    }]);
    $api->group(['namespace' => 'EspierBundle\Http\Api\V1\Action'], function ($api) {
        $api->post('/operator/login', ['name' => '登录', 'middleware' => 'shoplogin', 'as' => 'operator.login', 'uses' => 'AuthController@login']);
        $api->post('/operator/getLevel', ['name' => '获取登录验证等级', 'middleware' => 'shoplogin', 'as' => 'operator.getLevel', 'uses' => 'AuthController@getLevel']);
    });

    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action'], function($api) {
        //获取oauth登录链接
        $api->get('/operator/authorizeurl', ['name' => '登陆获取oauth链接','as' => 'operator.authorizeurl', 'uses' => 'Operators@getOuthorizeurl']);
        // 获取oauth登出链接
        $api->get('/operator/oauth/logout', ['name' => '获取oauth登出链接','as' => 'operator.authorizeurl', 'uses' => 'Operators@getOauthLogouturl']);
    });

    $api->post('/operator/oauth/login',['middleware'=>'shoplogin', function () use ($api) {
        $credentials = app('request')->only('code','logintype', 'product_model');
        $token = app('auth')->guard('oauthapi')->attempt($credentials);
        return response()->json(['data'=>['token'=>$token]]);
    }]);

    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action'], function($api) {
        $api->post('/operator/workwechat/oauth/login', ['name' => '企业微信oauth登录', 'middleware' => 'shoplogin', 'as' => 'workwechat.oauth.login', 'uses' => 'WorkWechat@login']);
        $api->post('/operator/workwechat/bind_mobile', ['name' => '绑定企业微信手机号', 'middleware' => 'shoplogin', 'as' => 'workwechat.bind', 'uses' => 'WorkWechat@bindMobile']);
        $api->get('/operator/workwechat/authorizeurl', ['name' => '获取企业微信oauth登录链接', 'middleware' => 'shoplogin', 'as' => 'workwechat.authorizeurl', 'uses' => 'WorkWechat@getWorkwechatOuthorizeurl']);
    });
    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action'], function($api) {
        $api->post('/operator/wechat/oauth/login', ['name' => '微信oauth登录', 'middleware' => 'shoplogin', 'as' => 'wechat.oauth.login', 'uses' => 'Wxapp@login']);
        $api->post('/operator/wechat/lite/login', ['name' => '小程序登录', 'middleware' => 'shoplogin', 'as' => 'wechat.lite.login', 'uses' => 'Wxapp@wxLiteLogin']);
        $api->post('/operator/wechat/bind_mobile', ['name' => '绑定微信手机号', 'middleware' => 'shoplogin', 'as' => 'wechat.bind.mobile', 'uses' => 'Wxapp@bindMobile']);
        $api->post('/operator/wechat/bind_account', ['name' => '绑定账号', 'middleware' => 'shoplogin', 'as' => 'wechat.bind.account', 'uses' => 'Wxapp@bindAccountLogin']);
        $api->get('/operator/wechat/authorizeurl', ['name' => '获取微信oauth登录链接', 'middleware' => 'shoplogin', 'as' => 'wechat.authorizeurl', 'uses' => 'Wxapp@getWechatOuthorizeurl']);
        $api->post('/operator/wechat/sms/code', ['name' => '发送手机短信验证码', 'middleware' => 'shoplogin', 'as' => 'wechat.sms.code', 'uses' => 'Wxapp@getSmsCode']);
        $api->post('/operator/wechat/distributor/js/config', ['name' => '前台获取JsSDK', 'middleware' => 'shoplogin', 'as' => 'wechat.distributor.js.config', 'uses' => 'Wxapp@getDistributorJsConfig']);
    });

    //刷新token
    $api->get('/token/refresh', ['middleware'=>'jwt.refresh', function () use ($api) {
        return response()->json(['data'=>['result'=>true]]);
    }]);

    $api->get('/token/invalidate', [function () use ($api) {
        app('auth')->guard('api')->logout();
        return response()->json(['data'=>['result'=>true]]);
    }]);

    $api->post('/operator/shuyun/login',['middleware'=>'shoplogin', function () use ($api) {
        $credentials = app('request')->only('code');
        $credentials['logintype'] = 'shuyunadmin';
        $token = app('auth')->guard('shuyunapi')->attempt($credentials);
        return response()->json(['data'=>['token'=>$token]]);
    }]);
});

