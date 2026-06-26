<?php

/*
|--------------------------------------------------------------------------
| 数云开放网关（company_shuyun_open_platform_config）B 端配置接口。
| 约定与 routes/api 主流一致：jwt + activated + shoplog；路径与 openapi 回调 shuyun/open-platform/* 语义对齐。
|--------------------------------------------------------------------------
*/

$api->version('v1', function ($api) {
    $api->group([
        'prefix' => '/shuyun/open-platform',
        'namespace' => 'ShuyunOpenPlatformBundle\Http\Api\V1\Action',
        'middleware' => ['api.auth', 'activated', 'shoplog'],
        'providers' => 'jwt',
    ], function ($api) {
        $api->get('/config', ['name' => '数云开放网关配置-获取', 'as' => 'shuyun.open_platform.config.get', 'uses' => 'OpenPlatformConfigController@getConfig']);
        $api->put('/config', ['name' => '数云开放网关配置-保存', 'as' => 'shuyun.open_platform.config.put', 'uses' => 'OpenPlatformConfigController@putConfig']);
        $api->post('/loyalty/grade/sync', ['name' => '数云开放网关-会员等级档案手动同步', 'as' => 'shuyun.open_platform.loyalty.grade.sync', 'uses' => 'LoyaltyGradeSyncController@postManualSync']);
    });

    /** 数云回调：验签在 Controller/Service 内完成，不可走 JWT（与 thirdparty/openapi 中 openapi/shuyun/... 等价，便于仅绑定 default 域名的环境）。 */
    $api->group([
        'prefix' => '/shuyun/open-platform',
        'namespace' => 'ShuyunOpenPlatformBundle\Http\Controllers',
    ], function ($api) {
        $api->post('/callback/token', ['as' => 'shuyun.open_platform.callback.token.default_api', 'uses' => 'ShuyunOpenPlatformTokenCallbackController@token']);
        $api->post('/callback/loyalty-grade', ['as' => 'shuyun.open_platform.callback.loyalty_grade.default_api', 'uses' => 'ShuyunOpenPlatformLoyaltyGradeCallbackController@callback']);
        $api->post('/callback/offline-benefit/create', ['as' => 'shuyun.open_platform.callback.offline_benefit.create.default_api', 'uses' => 'ShuyunOfflineBenefitCallbackController@create']);
        $api->post('/callback/offline-benefit/single-send', ['as' => 'shuyun.open_platform.callback.offline_benefit.single_send.default_api', 'uses' => 'ShuyunOfflineBenefitCallbackController@singleSend']);
        $api->post('/callback/offline-benefit/batch-send', ['as' => 'shuyun.open_platform.callback.offline_benefit.batch_send.default_api', 'uses' => 'ShuyunOfflineBenefitCallbackController@batchSend']);
    });
});
