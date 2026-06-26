<?php

/*
|--------------------------------------------------------------------------
| 数云开放网关 · 第三方回调（与 dm 等一致走 /api/third/...，便于与 third 分支路由习惯对齐）
|--------------------------------------------------------------------------
*/

$api->version('v1', function ($api) {
    $api->group(['namespace' => 'ShuyunOpenPlatformBundle\Http\Controllers'], function ($api) {
        // 数云token回调
        $api->post('/third/shuyun/open-platform/callback/token', [
            'as' => 'third.shuyun.open_platform.callback.token',
            'uses' => 'ShuyunOpenPlatformTokenCallbackController@token',
        ]);
        // 数云会员等级变更回调
        $api->post('/third/shuyun/open-platform/callback/loyalty-grade', [
            'as' => 'third.shuyun.open_platform.callback.loyalty_grade',
            'uses' => 'ShuyunOpenPlatformLoyaltyGradeCallbackController@callback',
        ]);
        // 数云线下权益创建回调
        $api->post('/third/shuyun/open-platform/callback/offline-benefit/create', [
            'as' => 'third.shuyun.open_platform.callback.offline_benefit.create',
            'uses' => 'ShuyunOfflineBenefitCallbackController@create',
        ]);
        // 数云线下权益单笔发送回调
        $api->post('/third/shuyun/open-platform/callback/offline-benefit/single-send', [
            'as' => 'third.shuyun.open_platform.callback.offline_benefit.single_send',
            'uses' => 'ShuyunOfflineBenefitCallbackController@singleSend',
        ]);
        // 数云线下权益批量发送回调
        $api->post('/third/shuyun/open-platform/callback/offline-benefit/batch-send', [
            'as' => 'third.shuyun.open_platform.callback.offline_benefit.batch_send',
            'uses' => 'ShuyunOfflineBenefitCallbackController@batchSend',
        ]);
    });
});
