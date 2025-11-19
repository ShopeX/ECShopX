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
    // 企业相关信息
    $api->group(['prefix' => 'h5app', 'namespace' => 'WechatBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {
        $api->get('/wxa/promotion/articles',      ['as' => 'front.wxapp.promotion.articles',      'uses' => 'Wxapp@getPromotionArticles']);
        $api->get('/wxa/promotion/articles/info', ['as' => 'front.wxapp.promotion.articles.info', 'uses' => 'Wxapp@getPromotionArticlesInfo']);
        $api->get('/wxapp/pageparams/setting', ['as' => 'front.wxapp.pageparams.setting', 'uses' => 'Wxapp@getParamByTempName']);
        $api->get('/wxapp/share/setting', ['as' => 'front.wxapp.share.setting', 'uses' => 'Wxapp@getShareSetting']);
        // 获取订阅消息模板id
        $api->get('/wxapp/newtemplate', ['as' => 'front.wxapp.newtemplate',  'uses'=>'Wxapp@getWxaNewTmpl']);
        // 获取会员中心参数配置
        $api->get('/wxapp/membercenter/setting', ['as' => 'front.wxapp.membercenter.setting', 'uses' => 'Wxapp@getMemberCenterParamByTempName']);
        // 首页配置信息
        $api->get('/wxapp/common/setting', ['as' => 'front.wxapp.common.setting', 'uses' => 'Wxapp@getCommonSetting']);
        // 购物车提醒配置
        $api->get('/wxapp/cartremind/setting', ['as' => 'front.wxapp.cartremind.setting', 'uses' => 'Wxapp@getCartremindSetting']);
        // share_id获取参数
        $api->get('/wxapp/getbyshareid', ['as' => 'front.wxapp.getbyshareid',  'uses'=>'Wxapp@getByShareId'] );
        // 小程序模板基础设置 包含小程序配置、小程序导航配置、风格配色
        $api->get('/wxapp/pagestemplate/baseinfo', ['as' => 'front.wxapp.pagestemplate.baseinfo', 'uses' => 'Wxapp@getPagestemplateBaseinfo']);
        // 会员中心设置  包含会员中心BANNER、菜单隐藏显示设置、页面跳转设置
        $api->get('/wxapp/pagestemplate/membercenter', ['as' => 'front.wxapp.pagestemplate.membercenter', 'uses' => 'Wxapp@getPagestemplateMembercenter']);

    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app端、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
