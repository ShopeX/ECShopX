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
    // 第三方联通接口
    $api->group(['namespace' => 'SystemLinkBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog', 'activated'], 'providers' => 'jwt'], function($api) {
        $api->post('/third/shopexerp/setting', [ 'name' => 'shopexerp配置信息保存', 'as' => 'third.shopexerp.setting.set', 'uses'=>'Third@setShopexErpSetting']);
        $api->get('/third/shopexerp/setting', [ 'name' => '获取shopexerp配置信息保存', 'as' => 'third.shopexerp.setting.get', 'uses'=>'Third@getShopexErpSetting']);

        $api->get('/omsqueuelog', [ 'name' => '获取oms通信日志列表', 'as' => 'omsqueuelog.get', 'uses'=>'OmsQueueLogController@getLogList']);

        // 旺店通旗舰版相关
        $api->post('/third/wdterp/setting', [ 'name' => '旺店通配置信息保存', 'middleware'=>'activated', 'as' => 'third.wdterp.setting.set', 'uses'=>'WdtErp@setSetting']);
        $api->get('/third/wdterp/setting', [ 'name' => '获取旺店通配置信息保存','middleware'=>'activated', 'as' => 'third.wdterp.setting.get', 'uses'=>'WdtErp@getSetting']);
        // 聚水潭相关
        $api->post('/third/jushuitan/setting', [ 'name' => '聚水潭配置信息保存', 'middleware'=>'activated', 'as' => 'third.jushuitan.setting.set', 'uses'=>'Jushuitan@setSetting']);
        $api->get('/third/jushuitan/setting', [ 'name' => '获取聚水潭配置信息保存','middleware'=>'activated', 'as' => 'third.jushuitan.setting.get', 'uses'=>'Jushuitan@getSetting']);

    });

    $api->group(['namespace' => 'ThirdPartyBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog', 'activated'], 'providers' => 'jwt'], function($api) {
        $api->post('/third/map/setting', [ 'name' => '更新第三方地图定位的类型', 'as' => 'third.map.setting.set', 'uses'=>'MapController@set']);
        $api->get('/third/map/setting', [ 'name' => '获取第三方地图定位的类型', 'as' => 'third.map.setting.get', 'uses'=>'MapController@get']);

        //Oms
        $api->post('/third/oms/setting', [ 'name' => 'OMS对接配置', 'as' => 'third.oms.setting.set', 'uses'=>'OmsController@setOmsSetting']);
        $api->get('/third/oms/setting', [ 'name' => '获取OMS对接配置', 'as' => 'third.oms.setting.get', 'uses'=>'OmsController@getOmsSetting']);

        // 达摩CRM相关
        $api->post('/third/dmcrm/setting', [ 'name' => '达摩CRM配置信息保存', 'middleware'=>'activated', 'as' => 'third.dmcrm.setting.set', 'uses'=>'DmCrm@setSetting']);
        $api->get('/third/dmcrm/setting', [ 'name' => '获取达摩CRM配置信息保存','middleware'=>'activated', 'as' => 'third.dmcrm.setting.get', 'uses'=>'DmCrm@getSetting']);
    });
});
