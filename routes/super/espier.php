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
    $api->group(['namespace' => 'EspierBundle\Http\SuperApi\V1\Action', 'prefix'=>'superadmin', 'middleware' => ['superguard', 'api.auth'], 'providers' => 'jwt'], function($api) {
        $api->post('/espier/image_upload_token', ['name'=>'获取上传图片token', 'as' => 'super.espier.image.uploadToken.get',  'uses'=>'UploadImages@getPicUploadToken']);
        $api->post('/espier/upload_localimage', ['name'=>'上传图片保存在本地', 'as' => 'super.espier.localimage.upload',  'uses'=>'UploadImages@uploadeLocalImage']);
    });
});
