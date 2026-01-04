<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
