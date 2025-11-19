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
    // 小程序登录
    $api->group(['prefix' => '/admin/wxapp', 'namespace' => 'CompanysBundle\Http\AdminApi\V1\Action'], function($api) {
        // 核销小程序登录
        $api->post('/login', [ 'as' => 'admin.wxapp.login',  'uses'=>'Wxapp@login']);
        // 导购小程序登录
        $api->post('/workwecahtlogin', [ 'as' => 'admin.wxapp.workwechatlogin',  'uses'=>'Wxapp@workwechatlogin']);
        // 导购小程序登录校验
        $api->post('/check', [ 'as' => 'admin.wxapp.check',  'uses'=>'Wxapp@checkSessionKey']);
    });
    // 获取微信二维码
    $api->group(['prefix' => '/admin/wxapp', 'namespace' => 'WechatBundle\Http\AdminApi\V1\Action'], function($api) {
        $api->get('/qrcode', [ 'as' => 'admin.wxapp.qrcode',  'uses'=>'Qrcode@getQrcode']);
    });
    $api->group(['prefix' => '/admin/wxapp', 'namespace' => 'EspierBundle\Http\AdminApi\V1\Action'], function($api) {
        // 获取上传图片token
        $api->post('/espier/image_upload_token', ['as' => 'admin.wxapp.upload',  'uses'=>'UploadFile@getPicUploadToken']);
        $api->post('/espier/uploadlocal', ['as' => 'admin.wxapp.uploadlocal',  'uses'=>'UploadFile@uploadeLocalImage']);
    });
});


