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
    $api->group(['namespace' => 'GoodsBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app', 'prefix' => 'h5app'], function ($api) {
        // 商品列表-已支持h5
        $api->get('/wxapp/goods/items',               ['as' => 'goods.items.lists',    'uses' => 'Items@getItemsList']);
        // 获取商品筛选条件
        $api->get('/wxapp/goods/items/filter', ['as' => 'goods.items.filter',    'uses' => 'Items@getItemsFilter']);
        //获取小店商品列表
        $api->get('/wxapp/goods/shopitems',               ['as' => 'goods.shop.items',    'uses' => 'Items@getShopItemsList']);
        // 商品分类-已支持h5
        $api->get('/wxapp/goods/category',            ['as' => 'goods.category.lists', 'uses' => 'Category@getCategoryList']);
        // 商品文描-已支持h5
        $api->get('/wxapp/goods/itemintro/{item_id}', ['as' => 'goods.items.intro',    'uses' => 'Items@getItemsIntro']);
        // 获取分类子分类
        $api->get('/wxapp/goods/category/{cat_id}',     ['as' => 'goods.category.subcat', 'uses' => 'Category@getChildrenCategorys']);
        //获取指定等级分类列表
        $api->get('/wxapp/goods/categorylevel',         ['as' => 'goods.category.level', 'uses' => 'Category@getLevelCategoryList']);
        //获取小店上架分类
        $api->get('/wxapp/goods/shopcategorylevel',         ['as' => 'goods.shop.category', 'uses' => 'Category@getShopShelvesCategoryList']);
        //获取商品会员价
        $api->get('/wxapp/goods/memberprice/{item_id}',     ['as' => 'goods.items.memberprice', 'uses' => 'Items@getMemberPriceList']);
        //获取店铺热门关键词
        $api->get('/wxapp/goods/keywords', ['as' => 'goods.item.getKeywords', 'uses' => 'Items@getKeywords']);
        // 获取商品分类
        $api->get('wxapp/goods/categoryinfo',            ['as' => 'goods.category.info', 'uses' => 'Category@getCategoryInfo']);

        // 商品分类-店铺推广
        $api->get('/wxapp/goods/promoter/category',            ['as' => 'goods.category.lists', 'uses' => 'Category@getPromoterCategoryList']);

    });

    $api->group(['namespace' => 'GoodsBundle\Http\FrontApi\V2\Action', 'middleware' => 'frontnoauth:h5app', 'prefix' => 'h5app'], function ($api) {
        // 商品详情-已支持h5,下面两种获取商品详情的接口都保留，兼容其他客户
        $api->get('/wxapp/goods/items/{item_id}',     ['as' => 'goods.items.detail',   'uses' => 'Items@getItemsDetail']);
        $api->get('/wxapp/goods/newitems',     ['as' => 'goods.items.detail.new',   'uses' => 'Items@getItemsDetailNew']);
        $api->get('/wxapp/goods/items_price_store/{item_id}',     ['as' => 'goods.items.price_and_store',   'uses' => 'Items@getItemsPriceAndStore']);
    });

    $api->group(['prefix' => 'h5app', 'namespace' => 'GoodsBundle\Http\FrontApi\V2\Action', 'middleware' => 'frontnoauth:h5app', 'providers' => 'jwt'], function($api) {
        //扫描二维码查找商品并加入购物车
        $api->post('/wxapp/goods/scancodeAddcart',     ['as' => 'goods.items.scancode.addcart',   'uses' => 'Items@scanCodeSales']);
    });
});

$api->version('v1', function ($api) {
    // 企业相关信息
    $api->group(['prefix' => 'h5app', 'namespace' => 'GoodsBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        $api->get('/wxapp/goods/items/{item_id}/fav', ['as' => 'goods.items.fav',    'uses' => 'Items@getItemsFav']);

        // 检查是否可以分享
        $api->get('/wxapp/goods/checkshare/items', ['as' => 'goods.checkshare.items',    'uses' => 'Items@checkShare']);

        // 获取分享的商品信息
        $api->get('/wxapp/goods/share/items/{item_id}', ['as' => 'goods.share.items',    'uses' => 'Items@getShareInfo']);

    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app端、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
