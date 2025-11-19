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
    $api->group(['prefix' => 'h5app', 'namespace' => 'PointBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        $api->get('/wxapp/point/member', ['name'=>'获取会员积分记录列表', 'as' => 'front.wxapp.point.member.list', 'uses' =>'PointMember@lists']);
        $api->get('/wxapp/point/member/info', ['name'=>'获取会员积分总数', 'as' => 'front.wxapp.point.member.info', 'uses' =>'PointMember@info']);
    });

    $api->group(['prefix' => 'h5app', 'namespace' => 'PointBundle\Http\FrontApi\V1\Action', 'middleware' => ['frontnoauth:h5app']], function ($api) {
        $api->get('/wxapp/point/rule', ['name'=>'获取积分规则', 'as' => 'front.wxapp.point.rule', 'uses' =>'PointMemberRule@info']);
    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app端、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
