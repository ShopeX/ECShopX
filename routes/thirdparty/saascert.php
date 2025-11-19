<?php

/*
|--------------------------------------------------------------------------
| sass 获取证书和节点
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */

$api->version('v1', function($api) {
    $api->group(['namespace' => 'ThirdPartyBundle\Http\Api\V1\Action'], function($api) {
        //反查地址
        $api->any('/third/saascert/cert/validate', ['as' => 'third.saascert.cert.validate',  'uses'=>'SaasCert@certiValidate']);
        //绑定节点callback
        $api->any('/third/saascert/matrix/callback/{id}', ['as' => 'third.saascert.matrix.callback',  'uses'=>'SaasCert@bindrelationCallback']);
    });
});

$api->version('v1', function($api) {
    $api->group(['namespace' => 'ThirdPartyBundle\Http\Api\V1\Action', 'middleware' => 'api.auth'], function($api) {
    	// 获取证书、节点
        $api->get('/third/saascert/certificate', ['as' => 'admin.third.saascert.certificate',  'uses'=>'SaasCert@getCertificate']);
        // 删除证书、节点
        $api->get('/third/saascert/delete/certificate', ['as' => 'admin.third.saascert.delete.certificate',  'uses'=>'SaasCert@deleteCertificate']);
        // 申请绑定节点
        $api->get('/third/saascert/apply/bindrelation', ['as' => 'admin.third.saascert.accept.bindrelation',  'uses'=>'SaasCert@applyBindrelation']);
        // 查看绑定节点
        $api->get('/third/saascert/accept/bindrelation', ['as' => 'admin.third.saascert.accept.bindrelation',  'uses'=>'SaasCert@acceptBindrelation']);
        // 查看是否绑定了erp
        $api->get('/third/saascert/isbind', ['as' => 'admin.third.saascert.isbind',  'uses'=>'SaasCert@getIsBind']);

        // saasErp 连通log列表
        $api->get('/third/saaserp/log/list', ['as' => 'admin.third.saaserp.log.list',  'uses'=>'SaasCert@getLogList']);
        
    });
});