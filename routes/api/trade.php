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
    $api->get('/paypal/success', ['name'=>'Paypal支付回调','as' => 'paypal.pay.notify', 'uses'=>'PaymentBundle\Http\Controllers\PaypalNotify@handle']);

    // 支付宝支付回调
    $api->post('/alipay/notify', ['name'=>'支付宝支付回调','as' => 'alipay.pay.notify', 'uses'=>'PaymentBundle\Http\Controllers\PaymentNotify@handle']);
    // 银联支付回调
    $api->post('/chinaums/notify', ['name'=>'银联支付回调','as' => 'chinaums.pay.notify', 'uses'=>'PaymentBundle\Http\Controllers\ChinaumsNotify@handle']);
    // 微信相关信息
    $api->group(['namespace' => 'OrdersBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/trade', [ 'name'=>'获取交易列表', 'middleware'=>['activated', 'datapass'],  'as' => 'order.trade.list',  'uses'=>'Trade@getTradelist']);

        $api->get('/orders', ['name'=>'获取订单列表','middleware'=>['activated', 'datapass'], 'as' => 'order.list.get', 'uses'=>'Order@getOrderList']);
        $api->get('/order/{order_id}', ['name'=>'获取订单详情','middleware'=>['activated', 'datapass'], 'as' => 'order.info.get', 'uses'=>'Order@getOrderDetail']);

        $api->get('/order/process/{orderId}', ['name'=>'获取订单操作详情','middleware'=>['activated', 'datapass'], 'as' => 'order.process.log.get', 'uses'=>'Order@getOrderProcessLog']);

        //发票后台功能
        $api->get('/fapiaolist', ['name'=>'获取发票列表','middleware'=>'activated', 'as' => 'invoice.list.get', 'uses'=>'Order@getInvoiceList']);
         $api->get('/fapiaoset', ['name'=>'发票操作','middleware'=>'activated', 'as' => 'invoice.set', 'uses'=>'Order@setInvoice']);

        // 获取订单的取消订单申请信息
        $api->get('/order/{order_id}/cancelinfo', [ 'name'=>'获取订单取消信息','middleware'=>'activated',  'as' => 'order.cancel.info',  'uses'=>'Order@getOrderCancelInfo']);
        // 确认订单取消审核
        $api->post('/order/{order_id}/confirmcancel', [ 'name'=>'确认订单取消审核', 'middleware'=>'activated',  'as' => 'order.cancel.info',  'uses'=>'Order@confirmOrderCancel']);
        $api->put('/order/{order_id}/processdrug', [ 'middleware'=>'activated',  'as' => 'order.process.drug',  'uses'=>'Order@processDrugOrders']);
        //订单配置
        $api->post('/orders/setting/set', ['name'=>'订单配置设置','middleware'=>'activated', 'as' => 'ordersetting.set', 'uses'=>'Order@setOrderSetting']);
        $api->get('/orders/setting/get', ['name'=>'获取订单配置','middleware'=>'activated', 'as' => 'orders.setting.get', 'uses'=>'Order@getOrderSetting']);
        //后端取消订单
        $api->post('/order/{order_id}/cancel', ['name' => '取消订单', 'middleware' => 'activated', 'as' => 'order.cancel', 'uses' => 'Order@cancelOrder']);

        // 发货
        $api->post('/delivery', ['name'=>'订单发货','middleware'=>'activated', 'as' => 'order.delivery', 'uses'=>'Order@delivery']);
        $api->put('/delivery/{orders_delivery_id}', ['name'=>'订单发货信息修改','middleware'=>'activated', 'as' => 'order.delivery.update', 'uses'=>'Order@updateDelivery']);
        $api->put('/old_delivery/{orderId}', ['name'=>'订单发货信息修改（旧)','middleware'=>'activated', 'as' => 'order.delivery.update', 'uses'=>'Order@updateDeliveryOld']);
        $api->put('/remarks/{orderId}', ['name'=>'订单备注信息修改','middleware'=>'activated', 'as' => 'order.remarks.update', 'uses'=>'Order@updateRemarks']);
        $api->get('/delivery/details', ['name'=>'物流详情','middleware'=>'activated', 'as' => 'order.delivery.details', 'uses'=>'Order@trackerpull']);
        $api->get('/delivery/lists', ['name'=>'发货单列表','middleware'=>'activated', 'as' => 'order.delivery.lists', 'uses'=>'Delivery@lists']);

        // 订单确认送达
        $api->post('/confirmReceipt', ['name'=>'订单确认送达','middleware'=>'activated', 'as' => 'order.confirmReceipt', 'uses'=>'Order@confirmReceipt']);

        $api->get('/orders/exportdata', ['name'=>'导出订单列表','middleware'=>['activated', 'datapass'], 'as' => 'order.list.export', 'uses'=>'ExportData@exportOrderData']);
        $api->get('/invoice/exportdata', ['name'=>'导出发票列表','middleware'=>'activated', 'as' => 'invoice.list.export', 'uses'=>'ExportData@exportInvoiceData']);
        $api->get('/rights/exportdata', ['name'=>'导出权益列表','middleware'=>['activated', 'datapass'], 'as' => 'rights.list.export', 'uses'=>'ExportData@exportRightData']);
        $api->get('/orders/exportnormaldata', ['name'=>'导出实体订单列表','middleware'=>'activated', 'as' => 'normal.list.export', 'uses'=>'ExportData@exportOrderNormalData']);
        $api->get('/trades/exportdata', ['name'=>'导出交易单列表','middleware'=>['activated','datapass'], 'as' => 'trades.list.export', 'uses'=>'ExportData@exportTradeData']);
        $api->get('/rights/logExport',  ['name'=>'导出权益核销列表','middleware'=>['activated','datapass'], 'as' => 'rights.log.list.export', 'uses'=>'ExportData@exportRightConsumeData']);
        //快递配置
        $api->post('/trade/kuaidi/setting', [ 'name'=>'快递配置信息保存','middleware'=>'activated', 'as' => 'trade.kuaidi.setting.set', 'uses'=>'Kuaidi@setKuaidiSetting']);
        $api->get('/trade/kuaidi/setting', [ 'name'=>'获取快递配置信息','middleware'=>'activated', 'as' => 'trade.kuaidi.setting.get', 'uses'=>'Kuaidi@getKuaidiSetting']);

        //顺丰物流BSP
        $api->post('/trade/sfbsp/setting', [ 'name'=>'顺丰物流跟踪设置保存','middleware'=>'activated', 'as' => 'trade.sfbsp.setting.set', 'uses'=>'Sfbsp@setSfbspSetting']);
        $api->get('/trade/sfbsp/setting', [ 'name'=>'获取顺丰物流跟踪设置','middleware'=>'activated', 'as' => 'trade.sfbsp.setting.get', 'uses'=>'Sfbsp@getSfbspSetting']);

        //退款失败日志
        $api->get('/trade/refunderrorlogs/list', [ 'name'=>'获取退款错误列表','middleware'=>'activated', 'as' => 'trade.refunderrorlogs.list', 'uses'=>'RefundErrorLogs@getList']);
        $api->put('/trade/refunderrorlogs/resubmit/{id}', [ 'name'=>'重新提交退款','middleware'=>'activated', 'as' => 'trade.refunderrorlogs.resubmit', 'uses'=>'RefundErrorLogs@resubmitRefund']);

        //评价
        $api->get('/trade/rate', ['name'=>'获取评价列表','middleware'=>'activated', 'as' => 'traderate.list.get', 'uses'=>'TradeRate@getTradeRateList']);
        $api->put('/trade/rate', ['name'=>'回复评价','middleware'=>'activated', 'as' => 'traderate.reply.put', 'uses'=>'TradeRate@replyTradeRate']);
        $api->get('/trade/{rate_id}/rate', ['name'=>'获取评价详情', 'middleware'=>'activated',  'as' => 'traderate.details.get',  'uses'=>'TradeRate@getTradeRateInfo']);
        $api->delete('/trade/rate/{rate_id}', ['name'=>'删除评价', 'middleware'=>'activated',  'as' => 'traderate.rate.delete',  'uses'=>'TradeRate@tradeRateDelete']);

        $api->post('/invoice/number', ['name'=>'设置订单发票号', 'middleware'=>'activated',  'as' => 'order.invoicenumber.set',  'uses'=>'Order@updateInvoiceNumber']);
        $api->post('/invoice/invoiced', ['name'=>'设置订单开票状态', 'middleware'=>'activated',  'as' => 'order.invoiced.invoiced',  'uses'=>'Order@setInvoiced']);

        $api->get('/financial/salesreport',  ['name'=>'导出财务销售报表','middleware'=>'activated', 'as' => 'financial.salesreport.export', 'uses'=>'ExportData@exportSalesreportData']);

        $api->get('/writeoff/{order_id}', ['name'=>'获取自提订单核销信息','middleware'=>'activated', 'as' => 'order.writeoff.info.get', 'uses'=>'Order@getOrderWriteoffInfo']);
        $api->post('/writeoff/{order_id}', ['name'=>'自提订单核销','middleware'=>'activated', 'as' => 'order.writeoff.set', 'uses'=>'Order@orderWriteoff']);

        $api->post('/qr_writeoff', ['name'=>'自提订单扫码核销','middleware'=>'activated', 'as' => 'order.writeoff.qr.set', 'uses'=>'Order@orderWriteoffQR']);

        //包装配置
        $api->post('/trade/setting', [ 'name'=>'交易配置信息保存','middleware'=>'activated', 'as' => 'trade.setting.set', 'uses'=>'TradeSetting@setSetting']);
        $api->get('/trade/setting', [ 'name'=>'获取交易配置信息','middleware'=>'activated', 'as' => 'trade.setting.get', 'uses'=>'TradeSetting@getSetting']);

        //取消订单配置
        $api->post('/trade/cancel/setting', [ 'name'=>'取消订单配置信息保存','middleware'=>'activated', 'as' => 'trade.cancel.setting.set', 'uses'=>'TradeSetting@setCancelSetting']);
        $api->get('/trade/cancel/setting', [ 'name'=>'获取取消订单配置信息','middleware'=>'activated', 'as' => 'trade.cancel.setting.get', 'uses'=>'TradeSetting@getCancelSetting']);

        // 达达同城配，商家接单
        $api->post('/businessreceipt/{orderId}', ['name'=>'达达同城配商家接单','middleware'=>'activated', 'as' => 'order.businessreceipt', 'uses'=>'Order@businessReceipt']);
        // 达达同城配，商家确认退回
        $api->post('/confirm/goods/{orderId}', ['name'=>'达达同城配商家确认退回','middleware'=>'activated', 'as' => 'order.confirm.goods', 'uses'=>'Order@confirmGoods']);

        // 订单改价
        $api->post('/order/markdown', ['name'=>'订单改价', 'middleware'=>['activated', 'datapass'], 'as' => 'order.markdown', 'uses'=>'Order@markDown']);
        $api->post('/order/markdown/confirm', ['name'=>'订单改价确认', 'middleware'=>['activated', 'datapass'], 'as' => 'order.markdown.confirm', 'uses'=>'Order@confirmMarkDown']);

        $api->post('/order/deliverypackag/confirm', ['name'=>'订单打包确认', 'middleware'=>['activated'], 'as' => 'order.deliverypackag.confirm', 'uses'=>'Order@confirmDeliveryPackag']);
        $api->post('/order/deliverystaff/confirm', ['name'=>'订单自配送员确认', 'middleware'=>['activated'], 'as' => 'order.deliverystaff.confirm', 'uses'=>'Order@confirmDeliveryStaff']);
        $api->post('/order/cancel/deliverystaff', ['name'=>'订单自配送取消', 'middleware'=>['activated'], 'as' => 'order.deliverystaff.cancel', 'uses'=>'Order@cancelDeliveryStaff']);
    });

    // 订单支状态相关信息
    $api->group(['namespace' => 'OrdersBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/order/payorderinfo/{trade_id}', ['name'=>'获取支付订单状态信息','middleware'=>'activated', 'as' => 'order.info.get', 'uses'=>'OrderStateInfo@getPayOrderInfo']);
        $api->get('/order/refundorderinfo/{refund_bn}', ['name'=>'获取退款订单状态信息','middleware'=>'activated', 'as' => 'order.info.get', 'uses'=>'OrderStateInfo@getRefundOrderInfo']);
    });

    // 支付方式相关
    $api->group(['namespace' => 'PaymentBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/trade/payment/setting', [ 'name'=>'支付配置信息保存','middleware'=>'activated', 'as' => 'trade.payment.setting.set', 'uses'=>'Payment@setPaymentSetting']);
        $api->get('/trade/payment/setting', [ 'name'=>'获取支付配置信息','middleware'=>'activated', 'as' => 'trade.payment.setting.get', 'uses'=>'Payment@getPaymentSetting']);
        $api->get('/trade/payment/open-status', [ 'name'=>'获取支付配置开关状态','middleware'=>'activated', 'as' => 'trade.payment.open.status.get', 'uses'=>'Payment@getPaymentOpenStatus']);
        $api->get('/trade/payment/list', [ 'name'=>'获取支付配置信息列表','middleware'=>'activated', 'as' => 'trade.payment.setting.list', 'uses'=>'Payment@getPaymentSettingList']);
        $api->get('/trade/payment/hfpayversionstatus', [ 'name'=>'获取汇付天下版本状态','middleware'=>'activated', 'as' => 'trade.payment.hfpay.status', 'uses'=>'Payment@getHfpayVersionStatus']);
        $api->get('/trade/payment/rsakey', [ 'name'=>'生成RSA密钥','middleware'=>'activated', 'as' => 'trade.payment.rsa.gen', 'uses'=>'Payment@genRsaKey']);
    });

    // 达达财务
    $api->group(['namespace' => 'OrdersBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/dada/finance/info', ['name'=>'获取账户余额','middleware'=>'activated', 'as' => 'dada.finance.info', 'uses'=>'DadaFinance@queryBalance']);
        $api->post('/dada/finance/create', ['name'=>'获取充值链接','middleware'=>'activated', 'as' => 'dada.finance.create', 'uses'=>'DadaFinance@recharge']);
    });


});
