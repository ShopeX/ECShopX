<?php
/*
|--------------------------------------------------------------------------
| 达摩crm
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */

$api->version('v1', function($api) {
    $api->group(['namespace' => 'ThirdPartyBundle\Http\ThirdApi\V1\Action'], function($api) {
        // 达摩crm 消息订阅事件回调接口
        $api->post('/third/dm/messageNotify/{companyId}', ['as' => 'third.dm.messageNotify', 'uses'=>'Dm@messageNotify']);
    });
    
});
