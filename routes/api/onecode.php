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
    // 一物一码相关信息
    $api->group(['namespace' => 'OneCodeBundle\Http\Api\V1\Action', 'middleware' => ['activated', 'api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        // 物品
        $api->post('/onecode/things',              ['name'=>'添加物品','as' => 'onecode.things.create', 'uses' => 'Things@createThings']);
        $api->get('/onecode/things',               ['name'=>'获取物品列表','as' => 'onecode.things.lists',  'uses' => 'Things@getThingsList']);
        $api->get('/onecode/things/{thing_id}',    ['name'=>'获取物品详情','as' => 'onecode.things.detail', 'uses' => 'Things@getThingsDetail']);
        $api->delete('/onecode/things/{thing_id}', ['name'=>'删除物品','as' => 'onecode.things.delete', 'uses' => 'Things@deleteThings']);
        $api->put('/onecode/things/{thing_id}',    ['name'=>'更新物品','as' => 'onecode.things.update', 'uses' => 'Things@updateThings']);
        // 批次
        $api->post('/onecode/batchs',              ['name'=>'添加物品批次','as' => 'onecode.batchs.create', 'uses' => 'Batchs@createBatchs']);
        $api->get('/onecode/batchs',               ['name'=>'获取物品批次列表','as' => 'onecode.batchs.lists',  'uses' => 'Batchs@getBatchsList']);
        $api->get('/onecode/batchs/{batch_id}',    ['name'=>'获取物品批次详情','as' => 'onecode.batchs.detail', 'uses' => 'Batchs@getBatchsDetail']);
        $api->delete('/onecode/batchs/{batch_id}', ['name'=>'删除物品批次','as' => 'onecode.batchs.delete', 'uses' => 'Batchs@deleteBatchs']);
        $api->put('/onecode/batchs/{batch_id}',    ['name'=>'更新物品批次','as' => 'onecode.batchs.update', 'uses' => 'Batchs@updateBatchs']);

        $api->get('/onecode/wxaOneCodeStream', ['name'=>'获取物品批次小程序码','as' => 'onecode.batchs.wxacode',  'uses' => 'Batchs@getWxaOneCodeStream']);
    });
});
