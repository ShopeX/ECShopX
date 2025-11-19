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


$api->version('v1', function ($api) {
    // 跨境电商产地国相关信息
    $api->group(['namespace' => 'CrossBorderBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/crossborder/origincountry', ['name' => '获取产地国列表', 'as' => 'crossborder.origincountry.getlist', 'uses' => 'OriginCountry@getList']);
        $api->post('/crossborder/origincountry', ['name' => '产地国添加&修改', 'as' => 'crossborder.origincountry.isadd', 'uses' => 'OriginCountry@isAdd']);
        $api->put('/crossborder/origincountry/{origincountry_id}', ['name' => '产地国修改', 'as' => 'crossborder.origincountry.isupdate', 'uses' => 'OriginCountry@isUpdate']);
        $api->delete('/crossborder/origincountry/{origincountry_id}', ['name' => '产地国删除', 'as' => 'crossborder.origincountry.isdel', 'uses' => 'OriginCountry@isDel']);
    });

    // 跨境电商设置
    $api->group(['namespace' => 'CrossBorderBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/crossborder/set', ['name' => '获取设置信息', 'as' => 'crossborder.set.info', 'uses' => 'CrossBorderSet@getInfo']);
        $api->post('/crossborder/set', ['name' => '保存设置信息', 'as' => 'crossborder.set.save', 'uses' => 'CrossBorderSet@Save']);
    });

    // 跨境电商阶梯税率策略设置
    $api->group(['namespace' => 'CrossBorderBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/crossborder/taxstrategy', ['name' => '税费策略列表', 'as' => 'crossborder.taxstrategy.getlist', 'uses' => 'Taxstrategy@getList']);
        $api->get('/crossborder/taxstrategy/{taxstrategy_id}', ['name' => '税费策略详情', 'as' => 'crossborder.taxstrategy.getinfo', 'uses' => 'Taxstrategy@getInfo']);
        $api->post('/crossborder/taxstrategy', ['name' => '税费策略添加', 'as' => 'crossborder.taxstrategy.isadd', 'uses' => 'Taxstrategy@isAdd']);
        $api->put('/crossborder/taxstrategy/{taxstrategy_id}', ['name' => '税费策略修改', 'as' => 'crossborder.taxstrategy.isupdate', 'uses' => 'Taxstrategy@isUpdate']);
        $api->delete('/crossborder/taxstrategy/{taxstrategy_id}', ['name' => '税费策略删除', 'as' => 'crossborder.taxstrategy.isdel', 'uses' => 'Taxstrategy@isDel']);
    });

});
