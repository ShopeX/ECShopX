<?php

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', function ($api) {
    $api->group(['middleware' => ['jwt.auth']], function ($api) {
        // 虚拟试衣
        $api->group(['prefix' => 'outfit'], function ($api) {
            $api->post('generate', 'ShopexAIBundle\Http\Api\V1\Action\OutfitAnyoneController@generate');
            $api->get('status/{task_id}', 'ShopexAIBundle\Http\Api\V1\Action\OutfitAnyoneController@checkStatus');
        });

        // 会员模特管理
        $api->group(['prefix' => 'member/outfit'], function ($api) {
            // 模特管理
            $api->post('model', 'ShopexAIBundle\Http\Api\V1\Action\MemberOutfitController@createModel');
            $api->put('model/{id}', 'ShopexAIBundle\Http\Api\V1\Action\MemberOutfitController@updateModel');
            $api->delete('model/{id}', 'ShopexAIBundle\Http\Api\V1\Action\MemberOutfitController@deleteModel');
            $api->get('models', 'ShopexAIBundle\Http\Api\V1\Action\MemberOutfitController@getModels');
            
            // 试衣记录
            $api->get('logs', 'ShopexAIBundle\Http\Api\V1\Action\MemberOutfitController@getLogs');
        });
    });
}); 