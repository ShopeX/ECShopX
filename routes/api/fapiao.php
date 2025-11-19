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
    $api->group(['namespace' => 'OrdersBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {

        $api->get('/fapiao/getFapiaoset', ['name' => '获取发票配置', 'as' => 'fapiao.getFapiaoset', 'uses' => 'Fapiao@getFapiaoset']);
        $api->post('/fapiao/saveFapiaoset', ['name' => '保存发票配置', 'as' => 'fapiao.saveFapiaoset', 'uses' => 'Fapiao@saveFapiaoset']);
    });

    $api->group(['namespace' => 'OrdersBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {

        $api->get('/order/invoice/list', ['name' => '获取订单发票列表', 'as' => 'get.invoice.list', 'uses' => 'Invoice@getInvoiceList']);
        $api->get('/order/invoice/info/{id}', ['name' => '获取订单发票详情', 'as' => 'get.invoice.detail', 'uses' => 'Invoice@getInvoiceDetail']);
        $api->post('/order/invoice/update/{id}', ['name' => '订单发票更新', 'as' => 'get.invoice.update', 'uses' => 'Invoice@updateInvoice']);
        $api->post('/order/invoice/updateremark/{id}', ['name' => '订单发票备注更新', 'as' => 'get.invoice.update.remark', 'uses' => 'Invoice@updateInvoiceRemark']);
        $api->get('/order/invoice/log/list', ['name' => '获取订单发票日志列表', 'as' => 'get.invoice.log.list', 'uses' => 'Invoice@getInvoiceLogList']);
        $api->post('/order/invoice/resend', ['name' => '重新发送邮件', 'as' => 'get.invoice.resend', 'uses' => 'Invoice@resendInvoice']);
        $api->post('/order/invoice/retryFailedInvoice', ['name' => '重新开票', 'as' => 'get.invoice.retryFailedInvoice', 'uses' => 'Invoice@retryFailedInvoice']);
        $api->get('/order/invoice/setting',  ['name' => '获取开票配置', 'as' => 'get.invoice.setting', 'uses' => 'Invoice@getInvoiceSetting']);
        $api->post('/order/invoice/setting', ['name' => '设置开票配置', 'as' => 'get.invoice.setting', 'uses' => 'Invoice@setInvoiceSetting']);
        $api->post('/order/invoice/baiwangInvoiceSetting', ['name' => '设置百旺发票配置', 'as' => 'get.invoice.baiwangInvoiceSetting', 'uses' => 'Invoice@setBaiwangInvoiceSetting']);
        $api->get('/order/invoice/baiwangInvoiceSetting', ['name' => '获取百旺发票配置', 'as' => 'get.invoice.baiwangInvoiceSetting', 'uses' => 'Invoice@getBaiwangInvoiceSetting']);
        //setInvoiceProtocol
        $api->post('/order/invoice/protocol', ['name' => '设置发票协议', 'as' => 'get.invoice.protocol', 'uses' => 'Invoice@setInvoiceProtocol']);
        $api->get('/order/invoice/protocol', ['name' => '获取发票协议', 'as' => 'get.invoice.protocol', 'uses' => 'Invoice@getInvoiceProtocol']);
        // 发票销售方相关接口
        $api->get('/order/invoice-seller/list', [
            'name' => '获取发票销售方列表',
            'as' => 'get.invoice.seller.list',
            'uses' => 'InvoiceSeller@getSellerList',
        ]);
        $api->get('/order/invoice-seller/info/{id}', [
            'name' => '获取发票销售方详情',
            'as' => 'get.invoice.seller.detail',
            'uses' => 'InvoiceSeller@getSellerDetail',
        ]);
        $api->post('/order/invoice-seller/create', [
            'name' => '新增发票销售方',
            'as' => 'create.invoice.seller',
            'uses' => 'InvoiceSeller@createSeller',
        ]);
        $api->post('/order/invoice-seller/update/{id}', [
            'name' => '修改发票销售方',
            'as' => 'update.invoice.seller',
            'uses' => 'InvoiceSeller@updateSeller',
        ]);

        // 分类税率相关接口
        $api->get('/order/category-taxrate/list', [
            'name' => '获取分类税率列表',
            'as' => 'get.category.taxrate.list',
            'uses' => 'CategoryTaxRate@getTaxRateList',
        ]);
        $api->get('/order/category-taxrate/info/{id}', [
            'name' => '获取分类税率详情',
            'as' => 'get.category.taxrate.detail',
            'uses' => 'CategoryTaxRate@getTaxRateDetail',
        ]);
        $api->post('/order/category-taxrate/create', [
            'name' => '新增分类税率',
            'as' => 'create.category.taxrate',
            'uses' => 'CategoryTaxRate@createTaxRate',
        ]);
        $api->post('/order/category-taxrate/update/{id}', [
            'name' => '修改分类税率',
            'as' => 'update.category.taxrate',
            'uses' => 'CategoryTaxRate@updateTaxRate',
        ]);
        $api->post('/order/category-taxrate/delete/{id}', [
            'name' => '删除分类税率',
            'as' => 'delete.category.taxrate',
            'uses' => 'CategoryTaxRate@deleteTaxRate',
        ]);
    });
});
