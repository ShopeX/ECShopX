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
    // 用户相关信息
    $api->group(['namespace' => 'PointBundle\Http\Api\V1\Action','middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/point/member', ['name'=>'用户积分列表','middleware'=>'activated', 'as' => 'point.member.list', 'uses' =>'PointMember@lists']);
        $api->get('/point/member/export', ['name'=>'导出用户积分列表','middleware'=>'activated', 'as' => 'point.member.list.export', 'uses' =>'PointMember@exportData']);
        $api->post('/point/adjustment', ['name'=>'积分调整','middleware'=>'activated', 'as' => 'point.adjustment', 'uses' =>'PointMember@adjustment']);

        $api->get('/member/point/rule', ['name'=>'用户积分规则详情','middleware'=>'activated', 'as' => 'point.member.rule.info', 'uses' =>'PointMemberRule@info']);
        $api->put('/member/point/rule', ['name'=>'用户积分规则设置','middleware'=>'activated', 'as' => 'point.member.rule.save', 'uses' =>'PointMemberRule@save']);

        $api->get('/member/pointcount/index',['name' => '获取积分总览页数据',  'middleware' => 'activated', 'as' => 'member.pointcount.index','uses'=>'PointMember@getPointCountIndex']);

    });
});
