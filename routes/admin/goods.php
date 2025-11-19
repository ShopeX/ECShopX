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
    $api->group(['prefix' => '/admin/wxapp', 'namespace' => 'GoodsBundle\Http\AdminApi\V1\Action', 'middleware' => ['api.auth', 'distributorlog'], 'providers' => 'adminwxapp'], function($api) {
        // 商品相关接口
        $api->get('/items/list', ['name' => '获取商品列表', 'as' => 'admin.wxapp.goods.getList',  'uses'=>'Items@getItemsList']);
        $api->get('/goods/promotion/items', ['name' => '获取商品列表', 'as' => 'admin.wxapp.goods.promotion.getList',  'uses'=>'Items@getPromotionItemList']);
        $api->get('/goods/itemsinfo', ['name' => '获取商品详情', 'as' => 'admin.wxapp.goods.detail',  'uses'=>'Items@getItemsDetail']);
        // 商品分类相关接口
        $api->get('/goods/category', ['name' => '获取分类列表', 'as' => 'goods.category.lists',  'uses' => 'Category@getCategoryList']);       
        $api->get('/custom/goods/category', ['name' => '获取自定义分类列表', 'as' => 'goods.custom.category.lists',  'uses' => 'Category@getCustomCategoryList']);
        $api->get('/goods/category/{cat_id}', ['name' => '获取分类子分类', 'as' => 'goods.category.subcat', 'uses' => 'Category@getChildrenCategorys']);
        $api->get('/goods/categorylevel', ['name' => '获取指定等级分类列表', 'as' => 'goods.category.level', 'uses' => 'Category@getLevelCategoryList']);

    });
});
