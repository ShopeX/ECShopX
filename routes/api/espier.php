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

$api->version('v1',
    function($api) {
    // 微信相关信息
    $api->group(['namespace' => 'EspierBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/espier/exportCsvData', ['name'=>'导出csv文件','middleware'=>'activated',  'as' => 'espier.export.file.down',  'uses'=>'ExportData@exportCsvData']);
        
        $api->post('/espier/upload_file', ['name'=>'上传文件','middleware'=>'activated',  'as' => 'espier.upload',  'uses'=>'UploadFile@handleUploadFile']);
        $api->get('/espier/upload_files', ['name'=>'获取上传文件列表','middleware'=>'activated',  'as' => 'espier.upload.list',    'uses'=>'UploadFile@getUploadLists']);
        $api->get('/espier/upload_error_file_export/{id}', ['name'=>'上传文件执行后错误信息','middleware'=>'activated',  'as' => 'espier.upload.error.export',    'uses'=>'UploadFile@exportUploadErrorFile']);
        $api->get('/espier/upload_template', ['name'=>'获取上传文件模版','middleware'=>'activated',  'as' => 'espier.upload.template.export',    'uses'=>'UploadFile@exportUploadTemplate']);

        //图片分类相关
        $api->post('/espier/image_upload_token', ['name'=>'获取上传图片token','middleware'=>'activated',  'as' => 'espier.image.uploadToken.get',  'uses'=>'UploadImages@getPicUploadToken']);
        $api->post('/espier/oss_upload_token', ['name'=>'获取云存储上传token','middleware'=>'activated',  'as' => 'espier.oss.uploadToken.get',  'uses'=>'UploadImages@getUploadToken']);

        $api->post('/espier/video_upload_token', ['name'=>'获取上传视频token','middleware'=>'activated',  'as' => 'espier.image.uploadToken.get',  'uses'=>'UploadImages@getVideoUploadToken']);
        $api->post('/espier/image/cat', ['name'=>'添加图片分类','middleware'=>'activated',  'as' => 'espier.image.cat.edit',  'uses'=>'UploadImages@editImageCat']);
        $api->get('/espier/image/cat/children', ['name'=>'获取分类的子类','middleware'=>'activated',  'as' => 'espier.image.cat.children',  'uses'=>'UploadImages@getCatChildren']);
        $api->get('/espier/image/cat/{image_cat_id}', ['name'=>'获取分类详情','middleware'=>'activated',  'as' => 'espier.image.cat.info',  'uses'=>'UploadImages@getCatInfo']);
        $api->delete('/espier/image/cat/{image_cat_id}', ['name'=>'删除图片分类','middleware' => 'activated', 'as' => 'espier.image.cat.del',  'uses'=>'UploadImages@delImgCat']);

        $api->post('/espier/oss_upload', ['name' => 'OSS直传文件(图片&视频上传)', 'middleware' => 'activated', 'as' => 'espier.oss.upload', 'uses' => 'UploadImages@ossUpload']);

        // 图片相关
        $api->post('/espier/image', ['name'=>'保存图片','middleware'=>'activated',  'as' => 'espier.image.save',  'uses'=>'UploadImages@saveImage']);
        $api->get('/espier/images', ['name'=>'获取图片列表','middleware'=>'activated',  'as' => 'espier.image.list',  'uses'=>'UploadImages@getImageList']);
        $api->delete('/espier/images', ['name'=>'删除图片','middleware'=>'activated',  'as' => 'espier.image.del',  'uses'=>'UploadImages@deleteImage']);

        $api->post('/espier/image/movecat', ['name'=>'移动图片到指定分类','middleware'=>'activated',  'as' => 'espier.image.move.cat',  'uses'=>'UploadImages@moveImageCat']);

        $api->post('/espier/upload_localimage', ['name'=>'上传图片','middleware'=>'activated',  'as' => 'espier.localimage.upload',  'uses'=>'UploadImages@uploadeImage']);

        $api->get('/espier/exportlog/list', ['name'=>'获取文件导出列表','middleware'=>'activated',  'as' => 'espier.export.loglist',  'uses'=>'ExportLogController@getExportLogList']);
        $api->get('/espier/exportlog/file/down', ['name'=>'文件导出下载','middleware'=>'activated',  'as' => 'espier.export.file.down',  'uses'=>'ExportLogController@fileDown']);
        // 地区模板
        $api->get('/espier/address', ['name'=>'获取地址','middleware' => 'activated', 'as' => 'espier.address.info', 'uses' => 'AddressController@get']);

        $api->get('/espier/printer', ['name'=>'获取易联云配置','middleware' => 'activated', 'as' => 'espier.printer.info', 'uses' => 'PrinterController@info']);
        $api->post('/espier/printer', ['name'=>'保存易联云配置','middleware' => 'activated', 'as' => 'espier.printer.save', 'uses' => 'PrinterController@update']);
        $api->get('/espier/printer/shop', ['name'=>'获取商家易联云列表','middleware' => 'activated', 'as' => 'espier.printer.shop.list', 'uses' => 'PrinterController@getPrinterList']);
        $api->post('/espier/printer/shop', ['name'=>'添加商家易联云打印机','middleware' => 'activated', 'as' => 'espier.printer.shop.created', 'uses' => 'PrinterController@createPrinter']);
        $api->put('/espier/printer/shop/{id}', ['name'=>'更新商家易联云打印机','middleware' => 'activated', 'as' => 'espier.printer.shop.updated', 'uses' => 'PrinterController@updatePrinter']);
        $api->delete('/espier/printer/shop/{id}', ['name'=>'删除商家易联云打印机','middleware' => 'activated', 'as' => 'espier.printer.shop.deleted', 'uses' => 'PrinterController@deletePrinter']);

        // 配置
        // 配置请求字段和验证模式
        $api->get('/espier/config/request_fields', ['name' => '获取配置的请求字段列表', 'middleware' => 'activated', 'as' => 'espier.config.request_fields.get', 'uses' => 'ConfigRequestFieldsController@list']);
        $api->post('/espier/config/request_fields', ['name' => '创建配置的请求字段', 'middleware' => 'activated', 'as' => 'espier.config.request_fields.create', 'uses' => 'ConfigRequestFieldsController@create']);
        $api->put('/espier/config/request_fields/switch', ['name' => '更新配置的请求字段的开关', 'middleware' => 'activated', 'as' => 'espier.config.request_fields.switch', 'uses' => 'ConfigRequestFieldsController@updateSwitch']);
        $api->put('/espier/config/request_fields/info', ['name' => '更新配置的请求字段的内容', 'middleware' => 'activated', 'as' => 'espier.config.request_fields.info', 'uses' => 'ConfigRequestFieldsController@updateInfo']);
        $api->delete('/espier/config/request_fields', ['name' => '创建配置的请求字段', 'middleware' => 'activated', 'as' => 'espier.config.request_fields.delete', 'uses' => 'ConfigRequestFieldsController@delete']);
        // 配置请求字段和验证模式 >> 配置项
        $api->post('/espier/config/request_field_setting', ['name' => '设置配置请求字段的配置项', 'middleware' => 'activated', 'as' => 'espier.config.request_field_setting.post', 'uses' => 'ConfigRequestFieldsController@updateConfig']);
        $api->get('/espier/config/request_field_setting', ['name' => '设置配置请求字段的配置项', 'middleware' => 'activated', 'as' => 'espier.config.request_field_setting.get', 'uses' => 'ConfigRequestFieldsController@getConfig']);

        // 街道社区
        $api->get('/espier/subdistrict', ['name' => '获取街道社区列表', 'middleware' => 'activated', 'as' => 'espier.subdistrict.list.get', 'uses' => 'SubdistrictController@get']);
        $api->get('/espier/subdistrict/{id}', ['name' => '获取街道社区', 'middleware' => 'activated', 'as' => 'espier.subdistrict.get', 'uses' => 'SubdistrictController@getInfo']);
        $api->delete('/espier/subdistrict/{id}', ['name' => '删除街道社区', 'middleware' => 'activated', 'as' => 'espier.subdistrict.delete', 'uses' => 'SubdistrictController@delete']);
        $api->put('/espier/subdistrict', ['name' => '保存街道社区', 'middleware' => 'activated', 'as' => 'espier.subdistrict.save', 'uses' => 'SubdistrictController@save']);

        // 线下转账-收款账户
        $api->get('/espier/offline/backaccount/lists', ['name' => '获取线下收款账户列表', 'middleware' => 'activated', 'as' => 'espier.offline.backaccount.lists', 'uses' => 'OfflineBankAccountController@getLists']);
        $api->get('/espier/offline/backaccount/{id}', ['name' => '获取线下收款账户信息', 'middleware' => 'activated', 'as' => 'espier.offline.backaccount.info', 'uses' => 'OfflineBankAccountController@getInfo']);
        $api->delete('/espier/offline/backaccount/{id}', ['name' => '删除线下收款账户', 'middleware' => 'activated', 'as' => 'espier.offline.backaccount.delete', 'uses' => 'OfflineBankAccountController@delete']);
        $api->post('/espier/offline/backaccount/create', ['name' => '创建线下收款账户', 'middleware' => 'activated', 'as' => 'espier.offline.backaccount.create', 'uses' => 'OfflineBankAccountController@create']);
        $api->post('/espier/offline/backaccount/update', ['name' => '更新线下收款账户', 'middleware' => 'activated', 'as' => 'espier.offline.backaccount.update', 'uses' => 'OfflineBankAccountController@update']);
    });

    $api->group(['namespace' => 'EspierBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->post('/espier/system/detect_version', ['name' => '升级', 'uses' => 'UpgradeController@detectVersion']);
        $api->post('/espier/system/upgrade', ['name' => '升级', 'uses' => 'UpgradeController@upgrade']);
        $api->post('/espier/system/rollback', ['name' => '回滚', 'uses' => 'UpgradeController@rollback']);
        $api->post('/espier/system/changelog', ['name' => '更新日志', 'uses' => 'UpgradeController@changelog']);
    });
    $api->group(['namespace' => 'EspierBundle\Http\Api\V1\Action', 'middleware' => 'api.throttle', 'limit' => 30, 'expires' => 1], function ($api) {
        $api->post('/espier/system/agreement', ['name' => '获取安装协议', 'uses' => 'UpgradeController@getAgreement']);
    });

});
