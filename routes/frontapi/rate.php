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
    $api->group(['prefix' => 'h5app', 'namespace' => 'OrdersBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        //用户评价
        $api->post('/wxapp/order/rate/create', ['name'=>'用户评价', 'as' => 'front.wxapp.order.rate.create',  'uses'=>'WxappTradeRate@addRate']);
        //评价回复
        $api->post('/wxapp/order/replyRate', ['as' => 'front.wxapp.order.rate.reply',  'uses'=>'WxappTradeRate@replyRate']);
        // 评价点赞
        $api->get('/wxapp/order/rate/praise/{rate_id}', ['name'=>'评价点赞', 'as' => 'front.wxapp.order.rate.praise', 'uses'=>'WxappTradeRate@ratePraise']);
        // 评价点赞验证
        $api->get('/wxapp/order/rate/praise/check/{rate_id}', ['name'=>'评价点赞验证', 'as' => 'front.wxapp.order.rate.praisecheck', 'uses'=>'WxappTradeRate@ratePraiseCheck']);
        //获取点赞状态
        $api->get('/wxapp/order/ratePraise/status', ['as' => 'front.wxapp.order.rate.praise.status', 'uses' => 'WxappTradeRate@ratePraiseStatus']);
    });

    //不需要接口验证
    $api->group(['prefix' => 'h5app', 'namespace' => 'OrdersBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {
        // 文章点赞数量获取
        $api->get('/wxapp/order/rate/praise/num/{rate_id}', [ 'as' => 'front.wxapp.order.rate.praisenum', 'uses'=>'WxappTradeRate@ratePraiseNum']);
        //评价列表
        $api->get('/wxapp/order/rate/list', ['name'=>'评价列表', 'as' => 'front.wxapp.order.list',  'uses'=>'WxappTradeRate@getRateList']);
        //获取评价详情
        $api->get('/wxapp/order/rate/detail/{rate_id}', ['name'=>'获取评价详情', 'as' => 'front.wxapp.order.detail', 'uses'=>'WxappTradeRate@getRateDetail']);
        //回复列表
        $api->get('/wxapp/order/replyRate/list', ['as' => 'front.wxapp.order.replyRate.list', 'uses' => 'WxappTradeRate@getReplyRateList']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app端、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
