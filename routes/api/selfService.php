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
    $api->group(['namespace' => 'SelfserviceBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/selfhelp/formdata', ['name'=>'新增表单元素配置项',  'middleware'=>'activated',  'as' => 'selfhelp.form.add',  'uses'=>'FormSettingController@createData']);
        $api->put('/selfhelp/formdata', ['name'=>'更新表单元素配置项', 'middleware'=>'activated',  'as' => 'selfhelp.form.edit',  'uses'=>'FormSettingController@updateData']);
        $api->get('/selfhelp/formdata', ['name'=>'获取表单元素配置项列表', 'middleware'=>'activated',  'as' => 'selfhelp.form.list',  'uses'=>'FormSettingController@getDatalist']);
        $api->get('/selfhelp/formdata/{id}', [ 'name'=>'获取表单元素配置项详情', 'middleware'=>'activated',  'as' => 'selfhelp.form.info',  'uses'=>'FormSettingController@getDataInfo']);
        $api->post('/selfhelp/formdata/discard/{id}', ['name'=>'废弃表单元素配置项', 'middleware'=>'activated',  'as' => 'selfhelp.form.delete',  'uses'=>'FormSettingController@deleteData']);
        $api->post('/selfhelp/formdata/restore/{id}', ['name'=>'恢复表单元素配置项', 'middleware'=>'activated',  'as' => 'selfhelp.form.delete',  'uses'=>'FormSettingController@restoreData']);

        $api->post('/selfhelp/formtem', ['name'=>'新增表单模板',  'middleware'=>'activated',  'as' => 'selfhelp.template.add',  'uses'=>'FormTemplateController@createData']);
        $api->put('/selfhelp/formtem', [ 'name'=>'更新表单模板', 'middleware'=>'activated',  'as' => 'selfhelp.template.edit',  'uses'=>'FormTemplateController@updateData']);
        $api->get('/selfhelp/formtem', ['name'=>'获取表单模板列表', 'middleware'=>'activated',  'as' => 'selfhelp.template.list',  'uses'=>'FormTemplateController@getDatalist']);
        $api->get('/selfhelp/formtem/{id}', [ 'name'=>'获取表单模板详情','middleware'=>'activated',  'as' => 'selfhelp.template.info',  'uses'=>'FormTemplateController@getDataInfo']);
        $api->post('/selfhelp/formtem/discard/{id}', ['name'=>'废弃表单模板',  'middleware'=>'activated',  'as' => 'selfhelp.template.delete',  'uses'=>'FormTemplateController@deleteData']);
        $api->post('/selfhelp/formtem/restore/{id}', ['name'=>'恢复表单模板',  'middleware'=>'activated',  'as' => 'selfhelp.template.delete',  'uses'=>'FormTemplateController@deleteData']);


        $api->post('/selfhelp/setting/physical', ['name'=>'配置体测表单',  'middleware'=>'activated',  'as' => 'selfhelp.setting.physical.set',  'uses'=>'UserDailyRecordController@settingPhysical']);
        $api->get('/selfhelp/setting/physical', ['name'=>'获取体测表单配置',  'middleware'=>'activated',  'as' => 'selfhelp.setting.physical.get',  'uses'=>'UserDailyRecordController@getSettingPhysical']);
        $api->get('/selfhelp/physical/alluserlist', ['name'=>'获取体测数据所有会员（最近一次的记录）',  'middleware'=>'activated',  'as' => 'selfhelp.physical.alluserlist',  'uses'=>'UserDailyRecordController@getAllUserList']);
        $api->get('/selfhelp/physical/userdata', ['name'=>'获取指定会员最近5次的体测记录',  'middleware'=>'activated',  'as' => 'selfhelp.physical.userlist',  'uses'=>'UserDailyRecordController@getUserPersonalRecord']);

        $api->get('/selfhelp/physical/datelist', ['name'=>'获取所有记录的日期列表',  'middleware'=>'activated',  'as' => 'selfhelp.physical.datelist',  'uses'=>'UserDailyRecordController@getRecordDateList']);

        $api->post('/selfhelp/registrationActivity/create', ['name'=>'新增报名活动',  'middleware'=>'activated',  'as' => 'selfhelp.registrationActivity.add',  'uses'=>'RegistrationActivityController@createData']);
        $api->put('/selfhelp/registrationActivity/update', [ 'name'=>'更新报名活动', 'middleware'=>'activated',  'as' => 'selfhelp.registrationActivity.edit',  'uses'=>'RegistrationActivityController@updateData']);
        $api->get('/selfhelp/registrationActivity/list', ['name'=>'获取报名活动列表', 'middleware'=>'activated',  'as' => 'selfhelp.registrationActivity.list',  'uses'=>'RegistrationActivityController@getDatalist']);
        $api->get('/selfhelp/registrationActivity/get', [ 'name'=>'获取报名活动详情','middleware'=>'activated',  'as' => 'selfhelp.registrationActivity.info',  'uses'=>'RegistrationActivityController@getDataInfo']);
        $api->post('/selfhelp/registrationActivity/del', ['name'=>'删除报名活动',  'middleware'=>'activated',  'as' => 'selfhelp.registrationActivity.delete',  'uses'=>'RegistrationActivityController@deleteData']);
        $api->post('/selfhelp/registrationActivity/invalid', ['name'=>'过期报名活动',  'middleware'=>'activated',  'as' => 'selfhelp.registrationActivity.invalid',  'uses'=>'RegistrationActivityController@restoreData']);
        $api->get('/selfhelp/registrationActivity/easylist', ['name'=>'获取报名活动列表', 'middleware'=>'activated',  'as' => 'selfhelp.registrationActivity.easylist',  'uses'=>'RegistrationActivityController@getEasyDatalist']);

        $api->get('/selfhelp/registrationRecord/list', ['name'=>'获取表单模板列表', 'middleware'=>['activated', 'datapass'],  'as' => 'selfhelp.registrationRecord.list',  'uses'=>'RegistrationRecordController@getDatalist']);
        $api->get('/selfhelp/registrationRecord/get', [ 'name'=>'获取表单模板详情','middleware'=>['activated', 'datapass'],  'as' => 'selfhelp.registrationRecord.info',  'uses'=>'RegistrationRecordController@getDataInfo']);
        $api->post('/selfhelp/registrationRecord/update', [ 'name'=>'更新报名记录','middleware'=>['activated', 'datapass'],  'as' => 'selfhelp.registrationRecord.update',  'uses'=>'RegistrationRecordController@updateDataInfo']);
        $api->put('/selfhelp/registrationReview',     [ 'name'=>'报名审核','middleware'=>'activated',  'as' => 'selfhelp.registrationRecord.review',  'uses'=>'RegistrationRecordController@registrationReview']);
        $api->post('/selfhelp/registrationVerify',     [ 'name'=>'报名记录核销','middleware'=>'activated',  'as' => 'selfhelp.registrationRecord.verify',  'uses'=>'RegistrationRecordController@registrationVerify']);
        $api->get('/selfhelp/registrationVerifyLog',     [ 'name'=>'报名记录核销记录列表','middleware'=>'activated',  'as' => 'selfhelp.registrationRecord.verify_log',  'uses'=>'RegistrationRecordController@registrationVerifyLog']);
        $api->get('/selfhelp/registrationRecord/export', [ 'name'=>'导出报名记录','middleware'=>['activated','datapass'],  'as' => 'selfhelp.registrationRecord.export',  'uses'=>'RegistrationRecordController@exportRegistrationRecord']);
    });
});
