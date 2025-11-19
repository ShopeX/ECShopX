<?php

$api->version('v1', function($api) {
    $api->group(['namespace' => 'HfPayBundle\Http\ThirdApi\V1\Action'], function($api) {
        //汇付天下推送通知
        $api->post('/third/hfpay/notify', ['as' => 'third.hfpay.notify', 'uses'=>'HfPay@notify']);
    });
});
