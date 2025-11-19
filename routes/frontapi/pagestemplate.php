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
    $api->group(['prefix' => 'h5app', 'namespace' => 'ThemeBundle\Http\FrontApi\V1\Action', 'middleware' => ['frontnoauth:h5app']], function ($api) {
        $api->get('/wxapp/pagestemplate/detail', ['middleware'=>'apicache','name' => '模板详情', 'as' => 'front.pagestemplate.detail', 'uses' => 'PagesTemplate@detail']);
        $api->get('/wxapp/pagestemplate/shopDetail', ['name' => '店铺模板详情', 'as' => 'front.pagestemplate.shopDetail', 'uses' => 'PagesTemplate@shopDetail']);
        $api->get('/wxapp/pagestemplate/setInfo', ['name' => '模板设置信息', 'as' => 'front.pagestemplate.setInfo', 'uses' => 'PagesTemplate@setInfo']);
        $api->get('/wxapp/pagestemplate/gettdk', ['name' => '获取tdk配置信息', 'as' => 'front.pagestemplate.gettdk', 'uses' => 'PagesTemplate@getTdk']);
        $api->get('/wxapp/openscreenad', ['name' => '开屏广告', 'as' => 'front.pagestemplate.openscreenad', 'uses' => 'OpenScreenAd@getInfo']);
        //pc模板
        $api->get('/wxapp/pctemplate/getHeaderOrFooter', ['name' => '获取pc模板头尾部', 'as' => 'front.pctemplate.getHeaderOrFooter', 'uses' => 'PcTemplate@getHeaderOrFooter']);
        $api->get('/wxapp/pctemplate/getTemplateContent', ['name' => '获取pc模板页面内容', 'as' => 'front.pctemplate.getTemplateContent', 'uses' => 'PcTemplate@getTemplateContent']);
        $api->get('/wxapp/pctemplate/loginPage/setting', ['name' => '获取pc登录页设置', 'as' => 'front.pctemplate.getLoginPageSetting', 'uses' => 'PcTemplate@getLoginPageSetting']);

        //会员中心分享设置
        $api->get('/wxapp/memberCenterShare/getInfo', ['name' => '获取会员中心分享信息', 'as' => 'front.memberCenterShare.getInfo', 'uses' => 'MemberCenterShare@getInfo']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app端、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
