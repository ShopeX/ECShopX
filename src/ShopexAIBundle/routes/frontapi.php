<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', [
    'namespace' => 'ShopexAIBundle\Http\Api\V1\Action',
    'middleware' => ['dingoguard:h5app', 'api.auth'],
    'providers' => 'jwt'
], function ($api) {
    // 虚拟试衣相关接口
    $api->group(['prefix' => 'h5app'], function ($api) {
        // 会员模特相关接口
        $api->post('/wxapp/outfit/model', 'MemberOutfitController@create');
        $api->put('/wxapp/outfit/model/{id}', 'MemberOutfitController@update');
        $api->delete('/wxapp/outfit/model/{id}', 'MemberOutfitController@delete');
        $api->get('/wxapp/outfit/models', 'MemberOutfitController@list');
        $api->get('/wxapp/outfit/logs', 'MemberOutfitController@logs');

        // 生成接口（支持直接生成和异步生成）
        $api->post('/wxapp/outfit/generate', 'OutfitAnyoneController@generate');
        // 查询任务状态
        $api->get('/wxapp/outfit/check-status/{taskId}', 'OutfitAnyoneController@checkStatus');
    });
});
