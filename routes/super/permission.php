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
// old
$api->version('v1', function($api) {
    // 企业相关信息
    $api->group(['namespace' => 'SuperAdminBundle\Http\SuperApi\V1\Action', 'prefix'=>'super/admin', 'middleware' => 'api.auth', 'providers' => 'superadmin'], function($api) {
        $api->get('shopmenu',  [ 'as' => 'super.admin.shopmenu.get',   'uses'=>'ShopMenu@getShopMenu']);
        $api->post('shopmenu', [ 'as' => 'super.admin.shopmenu.add',   'uses'=>'ShopMenu@addShopMenu']);
        $api->put('shopmenu',  [ 'as' => 'super.admin.shopmenu.update','uses'=>'ShopMenu@updateShopMenu']);
        $api->delete('shopmenu/{shopmenuId}',  [ 'as' => 'super.admin.shopmenu.del','uses'=>'ShopMenu@deleteShopMenu']);
        $api->get('shopmenu/down',  [ 'as' => 'super.admin.shopmenu.down','uses'=>'ShopMenu@downShopMenu']);
        $api->post('shopmenu/upload',  [ 'as' => 'super.admin.shopmenu.upload','uses'=>'ShopMenu@uploadMenu']);
    });

    $api->group(['namespace' => 'SuperAdminBundle\Http\SuperApi\V1\Action', 'prefix'=>'super/admin', 'middleware' => 'api.auth', 'providers' => 'superadmin'], function($api) {
        $api->get('wxapp',  [ 'as' => 'super.admin.wxapp.temp.get',   'uses'=>'wxappTemplate@getTemplateList']);
        $api->post('wxapp', [ 'as' => 'super.admin.wxapp.temp.add',   'uses'=>'wxappTemplate@addWxappTemplate']);
        $api->put('wxapp',  [ 'as' => 'super.admin.wxapp.temp.update','uses'=>'wxappTemplate@updateWxappTemplate']);
        $api->put('upgradeTemp',  [ 'as' => 'super.admin.wxapp.temp.upgrade','uses'=>'wxappTemplate@upgradeTemp']);
        $api->delete('wxapp/{id}',  [ 'as' => 'super.admin.wxapp.temp.del','uses'=>'wxappTemplate@deleteWxappTemplate']);
        $api->post('speedupaudit',  [ 'as' => 'super.admin.wxapp.speedupaudit','uses'=>'wxappTemplate@speedupaudit']);
        $api->post('domain',  [ 'as' => 'super.admin.wxapp.domain','uses'=>'wxappTemplate@setDomain']);
        $api->post('puturl',  [ 'as' => 'super.admin.wxapp.puturl','uses'=>'wxappTemplate@modifyDomain']);
    });
});



// new-----------------------------------------
// 菜单管理
$api->version('v1', function($api) {
    $api->group(['namespace' => 'SuperAdminBundle\Http\SuperApi\V1\Action', 'prefix'=>'superadmin', 'middleware' => ['superguard', 'api.auth'], 'providers' => 'jwt'], function($api) {
        // 获取平台自己的后台菜单权限
        $api->get('/permission', ['as' => 'super.admin.roles.permission', 'uses'=>'Accounts@getPermission']);
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


$api->version('v1', function($api) {
    $api->group(['namespace' => 'SuperAdminBundle\Http\SuperApi\V1\Action', 'prefix'=>'superadmin', 'middleware' => ['superguard', 'api.auth'], 'providers' => 'jwt'], function($api) {
        $api->get('wxapp',  [ 'as' => 'super.admin.wxapp.temp.get',   'uses'=>'wxappTemplate@getTemplateList']);
        $api->post('wxapp', [ 'as' => 'super.admin.wxapp.temp.add',   'uses'=>'wxappTemplate@addWxappTemplate']);
        $api->put('wxapp',  [ 'as' => 'super.admin.wxapp.temp.update','uses'=>'wxappTemplate@updateWxappTemplate']);
        $api->put('upgradeTemp',  [ 'as' => 'super.admin.wxapp.temp.upgrade','uses'=>'wxappTemplate@upgradeTemp']);
        $api->delete('wxapp/{id}',  [ 'as' => 'super.admin.wxapp.temp.del','uses'=>'wxappTemplate@deleteWxappTemplate']);
        // 小程序加急审核
        $api->post('speedupaudit',  [ 'as' => 'super.admin.wxapp.speedupaudit','uses'=>'wxappTemplate@speedupaudit']);
        $api->post('domain',  [ 'as' => 'super.admin.wxapp.domain','uses'=>'wxappTemplate@setDomain']);
    });

});

//微信开放平台-代码模板库
$api->version('v1', function($api) {
    $api->group(['namespace' => 'SuperAdminBundle\Http\SuperApi\V1\Action', 'prefix'=>'superadmin', 'middleware' => ['superguard', 'api.auth'], 'providers' => 'jwt'], function($api) {
        //获取代码草稿列表
        $api->get('wxappOplatform/gettemplatedraftlist',  [ 'as' => 'super.admin.wxapp.oplatform.template.',   'uses'=>'OplatformTemplate@gettemplatedraftlist']);
        //将草稿添加到代码模板库
        $api->post('wxappOplatform/addtotemplate', [ 'as' => 'super.admin.wxapp.oplatform.template.',   'uses'=>'OplatformTemplate@addtotemplate']);
        //获取代码模板列表
        $api->get('wxappOplatform/gettemplatelist', [ 'as' => 'super.admin.wxapp.oplatform.template.',   'uses'=>'OplatformTemplate@gettemplatelist']);
        //删除代码模版
        $api->post('wxappOplatform/deletetemplate', [ 'as' => 'super.admin.wxapp.oplatform.template.',   'uses'=>'OplatformTemplate@deletetemplate']);
    });
});
