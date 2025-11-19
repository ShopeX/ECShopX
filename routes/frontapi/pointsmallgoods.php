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

/* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ taro小程序、h5、app端、pc端 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
$api->version('v1', function ($api) {
    $api->group(['namespace' => 'PointsmallBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app', 'prefix' => 'h5app'], function ($api) {
        // 商品列表-已支持h5
        $api->get('/wxapp/pointsmall/goods/items',               ['name' => '商品列表', 'as' => 'pointsmall.goods.items.lists',    'uses' => 'Items@getItemsList']);
        // 猜你喜欢商品列表，销量前10
        $api->get('/wxapp/pointsmall/lovely/goods/items',               ['name' => '猜你喜欢商品列表', 'as' => 'pointsmall.lovely.goods.items.lists',    'uses' => 'Items@getLovelyItemsList']);
        // 商品详情-已支持h5
        $api->get('/wxapp/pointsmall/goods/items/{item_id}',     ['name' => '商品详情', 'as' => 'pointsmall.goods.items.detail',   'uses' => 'Items@getItemsDetail']);
        // 获取设置 模板设置、基础设置（入口配置）
        $api->get('/wxapp/pointsmall/setting',            ['name' => '获取模板设置', 'as' => 'pointsmall.setting.get', 'uses' => 'Setting@getSetting']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app端、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
