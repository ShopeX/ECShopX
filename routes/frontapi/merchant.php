<?php
/* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ taro小程序、h5、app端、pc端 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
$api->version('v1', function ($api) {
    // 商户相关api
    $api->group(['prefix' => 'h5app', 'namespace' => 'MerchantBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {
        $api->get('/wxapp/merchant/basesetting', ['name' => '获取商户基础设置','as' => 'merchant.basesetting.get',   'uses' => 'Merchant@getBaseSetting']);
    });

    // 需要登录token
    $api->group(['prefix' => 'h5app', 'namespace' => 'MerchantBundle\Http\FrontApi\V1\Action', 'middleware' => ['frontmerchantauth:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        $api->get('/wxapp/merchant/settlementapply/step', ['name' => '获取商户入驻当前步骤','as' => 'merchant.merchant.settlementapply.step',   'uses' => 'Merchant@getSettlementApplyStep']);
        $api->get('/wxapp/merchant/type/list', ['name' => '获取商户类型列表','as' => 'merchant.type.list',   'uses' => 'Merchant@getVisibleTypeList']);
        $api->post('/wxapp/merchant/settlementapply/{step}', ['name' => '保存商户入驻信息','as' => 'merchant.settlementapply.save',   'uses' => 'Merchant@saveSettlementApply']);
        $api->get('/wxapp/merchant/settlementapply/detail', ['name' => '获取商户入驻信息详情','as' => 'merchant.settlementapply.detail',   'uses' => 'Merchant@getSettlementApplyDetail']);
        $api->get('/wxapp/merchant/settlementapply/auditstatus', ['name' => '获取商户入驻信息审核结果','as' => 'merchant.settlementapply.auditstatus',   'uses' => 'Merchant@getSettlementApplyAuditstatus']);
        $api->post('/wxapp/merchant/password/reset', ['name' => '重置商户登录密码','as' => 'merchant.password.reset',   'uses' => 'Merchant@resetMerchantPassword']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */