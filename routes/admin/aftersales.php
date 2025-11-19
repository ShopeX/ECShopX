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
    $api->group(['prefix' => '/admin/wxapp', 'namespace' => 'AftersalesBundle\Http\AdminApi\V1\Action', 'middleware' => ['api.auth', 'distributorlog'], 'providers' => 'adminwxapp'], function($api) {
        // 导购售后相关api
        $api->get('/aftersales', ['name' => '获取售后列表', 'role' => '3', 'as' => 'admin.wxapp.aftersales.list', 'uses' => 'Aftersales@getAftersalesList']);
        $api->get('/aftersales/info', ['name' => '获取售后详情', 'role' => '3', 'as' => 'admin.wxapp.aftersales.info', 'uses' => 'Aftersales@getAftersalesDetail']);
        $api->post('/aftersales/review', ['name' => '售后审核', 'role' => '3', 'as' => 'admin.wxapp.aftersales.review', 'uses' => 'Aftersales@aftersalesReview']);
        $api->post('/aftersales/refundCheck', ['name' => '售后退款审核', 'role' => '3', 'as' => 'admin.wxapp.aftersales.review', 'uses' => 'Aftersales@refundCheck']);
        $api->post('/aftersales', ['name' => '自提订单售后申请', 'role' => '3', 'as' => 'admin.wxapp.aftersales.apply', 'uses' => 'Aftersales@apply']);
        //获取售后原因列表
        $api->get('/aftersales/reason/list', ['name' => '获取售后原因列表', 'as' => 'admin.wxapp.aftersales.reason.list', 'uses' => 'Reason@getSreasonList']);
    });
});

