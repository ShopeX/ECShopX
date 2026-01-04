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
    'middleware' => ['api.auth', 'shoplog'], 
    'providers' => 'jwt'
], function ($api) {
    // 软文生成相关接口
    $api->group(['prefix' => 'article'], function ($api) {
        // 直接生成接口（无需创建会话）
        $api->post('generate-direct', 'ArticleController@generateDirect');
        $api->get('check-status', 'ArticleController@checkGenerateStatus');

        // 流式生成内容
        $api->get('generate-stream/{sessionId}', 'ArticleController@generateStream');
        // 创建生成会话
        $api->post('create-session', 'ArticleController@createSession');
        // 清理会话
        $api->delete('cleanup-session/{sessionId}', 'ArticleController@cleanupSession');
        
        // 保存结构化文章
        $api->post('save-structured', 'ArticleController@saveStructuredArticle');
    });
    
    // 虚拟试衣相关接口（管理端）
    $api->group(['prefix' => 'outfit'], function ($api) {
        // 统计相关接口
        $api->get('statistics', 'OutfitAnyoneController@statistics');
        // 配置相关接口
        $api->get('config', 'OutfitAnyoneController@getConfig');
        $api->post('config', 'OutfitAnyoneController@updateConfig');
    });
}); 