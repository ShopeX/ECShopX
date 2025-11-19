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
    // 售后相关api
    $api->group(['namespace' => 'AftersalesBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/aftersales', ['name' => '获取售后列表', 'middleware' => 'datapass', 'as' => 'aftersales.list', 'uses' => 'Aftersales@getAftersalesList']);
        $api->get('/aftersales/logExport',  ['name'=>'导出售后列表', 'as' => 'aftersales.logexport', 'uses'=>'Aftersales@logExport']);
        $api->get('/aftersales/{aftersales_bn}', ['name' => '获取售后详情', 'as' => 'aftersales.info', 'uses' => 'Aftersales@getAftersalesDetail']);
        $api->post('/aftersales/review', ['name' => '售后审核', 'as' => 'aftersales.review', 'uses' => 'Aftersales@aftersalesReview']);
        $api->post('/aftersales/refundCheck', ['name' => '售后退款审核', 'as' => 'aftersales.review', 'uses' => 'Aftersales@refundCheck']);
        $api->get('/aftersales/reason/list', ['name' => '售后原因列表获取', 'as' => 'aftersales.reason.list', 'uses' => 'Reason@getSreasonList']);
        $api->post('/aftersales/reason/save',  ['name'=>'售后原因列表保存', 'as' => 'aftersales.reason.save', 'uses'=>'Reason@Saveset']);
        $api->get('/aftersales/financial/export',  ['name'=>'导出售后报表', 'as' => 'aftersales.financial.export', 'uses'=>'Aftersales@financialExport']);
        $api->get('/aftersales/remind/detail',  ['name'=>'售后提醒内容获取', 'as' => 'aftersales.remind.get', 'uses'=>'Aftersales@getRemind']);
        $api->post('/aftersales/remind',  ['name'=>'售后提醒内容设置', 'as' => 'aftersales.remind.set', 'uses'=>'Aftersales@setRemind']);

        $api->put('/aftersales/remark', ['name' => '更新售后备注', 'as' => 'aftersales.remark.update', 'uses' => 'Aftersales@updateRemark']);
        $api->post('/aftersales/apply', ['name' => '创建售后申请', 'as' => 'aftersales.apply', 'uses' => 'Aftersales@apply']);
        $api->post('/aftersales/sendback', ['name' => '售后管理员填写寄回信息', 'as' => 'aftersales.sendback', 'uses' => 'Aftersales@sendback']);
    });

    // 退款单相关api
    $api->group(['namespace' => 'AftersalesBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/refund', ['name' => '获取退款单列表', 'as' => 'refund.list', 'uses' => 'Refund@getRefundList']);
        $api->get('/refund/detail/{refund_bn}', ['name' => '获取退款单详情', 'as' => 'refund.info', 'uses' => 'Refund@getRefundsDetail']);
        $api->get('/refund/logExport',  ['name'=>'导出退款单列表', 'as' => 'refund.logexport', 'uses'=>'Refund@logExport']);
        $api->get('/refund/offline/bank', ['name' => '获取线下退款银行信息', 'as' => 'refund.offline', 'uses' => 'Refund@getOfflineBank']);
        $api->post('/refund/offline', ['name' => '线下退款', 'as' => 'refund.offline', 'uses' => 'Refund@saveOfflineRefund']);
    });
});
