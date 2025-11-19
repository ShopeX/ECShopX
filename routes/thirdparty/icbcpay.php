<?php

$api->version('v1', function($api) {
    $api->group(['namespace' => 'IcbcPayBundle\Http\ThirdApi\V1\Action'], function($api) {
        //工商银行推送通知
        $api->post('/third/icbcpay/notify', ['as' => 'third.icbcpay.notify', 'uses'=>'IcbcPay@notify']);
    });
});
