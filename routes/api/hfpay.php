<?php
$api->version('v1', function($api) {
    $api->group(['namespace' => 'HfPayBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/hfpay/ledgerconfig/index', ['name' => '汇付获取分账配置', 'middleware'=>'activated', 'as' => 'hfpay.ledgerconfig.index', 'uses' => 'HfpayLedgerConfig@index']);
        $api->post('/hfpay/ledgerconfig/save', ['name' => '保存分账配置', 'middleware'=>'activated','as' => 'hfpay.ledgerconfig.save', 'uses' => 'HfpayLedgerConfig@save']);
        $api->get('/hfpay/enterapply/apply', ['name' => '获取入驻信息', 'middleware'=>'activated', 'as' => 'hfpay.enterapply.apply', 'uses' => 'HfpayEnterapply@apply']);
        $api->post('/hfpay/enterapply/save', ['name' => '保存入驻信息', 'middleware'=>'activated','as' => 'hfpay.enterapply.save', 'uses' => 'HfpayEnterapply@saveEnterapply']);
        $api->get('/hfpay/enterapply/getList', ['name' => '获取店铺入驻列表信息', 'middleware'=>'activated', 'as' => 'hfpay.enterapply.getlist', 'uses' => 'HfpayEnterapply@getApplyList']);
        $api->post('/hfpay/enterapply/hfkaihu', ['name' => '企业个体户开户', 'middleware'=>'activated','as' => 'hfpay.enterapply.hfkaihu', 'uses' => 'HfpayEnterapply@hfKaiHu']);
        $api->post('/hfpay/enterapply/hffile', ['name' => '汇付文件上传', 'middleware'=>'activated','as' => 'hfpay.enterapply.hffile', 'uses' => 'HfpayEnterapply@hfFile']);
        $api->post('/hfpay/enterapply/opensplit', ['name' => '店铺分账开关', 'middleware'=>'activated','as' => 'hfpay.enterapply.opensplit', 'uses' => 'HfpayEnterapply@openSplit']);
        $api->get('/hfpay/getwithdrawset', ['name' => '汇付获取提现设置', 'middleware'=>'activated', 'as' => 'hfpay.withdraw.get', 'uses' => 'HfpayWithdrawSet@index']);
        $api->post('/hfpay/savewithdrawset', ['name' => '汇付提现设置', 'middleware'=>'activated', 'as' => 'hfpay.withdraw.save', 'uses' => 'HfpayWithdrawSet@save']);
        $api->get('/hfpay/statistics/distributor', ['name' => '店铺分账交易统计', 'middleware'=>'activated','as' => 'hfpay.statistics.distributor', 'uses' => 'HfpayStatistics@distributor']);
        $api->get('/hfpay/statistics/company', ['name' => '平台分账交易统计', 'middleware'=>'activated','as' => 'hfpay.statistics.company', 'uses' => 'HfpayStatistics@company']);
        $api->get('/hfpay/statistics/exportData', ['name' => '导出报表', 'middleware'=>'activated','as' => 'hfpay.statistics.exportData', 'uses' => 'HfpayStatistics@exportData']);

        $api->get('/hfpay/statistics/orderList', ['name' => '分账统计列表', 'middleware'=>'activated','as' => 'hfpay.statistics.orderList', 'uses' => 'HfpayStatistics@orderList']);
        $api->get('/hfpay/statistics/orderDetail/{orderId}', ['name' => '分账统计详情', 'middleware'=>'activated','as' => 'hfpay.statistics.orderDetail', 'uses' => 'HfpayStatistics@orderDetail']);
        $api->get('/hfpay/statistics/orderExportData', ['name' => '分账统计导出报表', 'middleware'=>'activated','as' => 'hfpay.statistics.orderExportData', 'uses' => 'HfpayStatistics@orderExportData']);

        $api->get('/hfpay/withdraw/getList', ['name' => '汇付提现记录', 'middleware'=>'activated', 'as' => 'hfpay.withdraw.getList', 'uses' => 'HfpayCashRecord@getList']);
        $api->post('/hfpay/withdraw', ['name' => '汇付提现', 'middleware'=>'activated', 'as' => 'hfpay.withdraw', 'uses' => 'HfpayCashRecord@withdraw']);
        $api->get('/hfpay/withdraw/exportData', ['name' => '汇付提现记录导出', 'middleware'=>'activated', 'as' => 'hfpay.withdraw.exportData', 'uses' => 'HfpayCashRecord@exportData']);
    });
});