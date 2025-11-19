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


// 菜单管理
$api->version('v1', function($api) {
    $api->group(['namespace' => 'ShopmenuBorderBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        // 获取平台自己的后台菜单权限
//        $api->get('/permission', ['as' => 'super.admin.roles.permission', 'uses'=>'Accounts@getPermission']);
        // 获取菜单列表
        $api->get('/shopmenu',  [ 'as' => 'super.admin.shopmenu.get',   'uses'=>'ShopMenu@getShopMenu']);
        // 添加菜单
        $api->post('/shopmenu', [ 'as' => 'super.admin.shopmenu.add',   'uses'=>'ShopMenu@addShopMenu']);
        // 修改菜单
        $api->put('/shopmenu',  [ 'as' => 'super.admin.shopmenu.update','uses'=>'ShopMenu@updateShopMenu']);
        // 删除菜单
        $api->delete('/shopmenu/{shopmenuId}',  [ 'as' => 'super.admin.shopmenu.del','uses'=>'ShopMenu@deleteShopMenu']);
        // 下载菜单
        $api->get('/shopmenu/down',  [ 'as' => 'super.admin.shopmenu.down','uses'=>'ShopMenu@downShopMenu']);
        // 上传菜单
        $api->post('/shopmenu/upload',  [ 'as' => 'super.admin.shopmenu.upload','uses'=>'ShopMenu@uploadMenu']);
    });
});
