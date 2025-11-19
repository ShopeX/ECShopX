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
    $api->group(['prefix' => '/admin/wxapp', 'namespace' => 'SelfserviceBundle\Http\AdminApi\V1\Action', 'middleware' => ['api.auth', 'distributorlog'], 'providers' => 'adminwxapp'], function($api) {
        $api->get('/selfform/list', ['name' => '获取表单模板列表', 'as' => 'admin.wxapp.self.form.datalist',  'uses'=>'FormTemplateController@getDatalist']);
        $api->get('/selfform/tempinfo', ['name' => '获取指定模板', 'as' => 'admin.wxapp.self.form.temp',  'uses'=>'FormTemplateController@getTemplateInfo']);
        $api->post('/selfform/saveuserform', ['name' => '保存自助表单内容', 'as' => 'admin.wxapp.self.form.datainfo',  'uses'=>'FormTemplateController@saveSelfFormData']);
        $api->get('/selfform/statisticalAnalysis', ['name' => '获取指定时间段内的数据统计数据', 'as' => 'admin.wxapp.self.form.datainfo',  'uses'=>'FormTemplateController@statisticalAnalysis']);
        $api->get('/selfform/physical/datelist', ['name' => '获取所有记录的日期列表', 'as' => 'front.wxapp.member.selfform.datelist',      'uses' => 'FormTemplateController@getRecordDateList']);
    });
});
