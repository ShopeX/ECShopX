<?php
/**
 *  代码已废弃
 */
$api->version('v1', function ($api) {

    $api->group(['prefix' => 'openapi','namespace' => 'OpenapiBundle\Http\Api\V1\Action\Member', "middleware" => ["OpenapiCheck", "OpenapiResponse"]], function ($api) {
//        $api->post('/member', ['name' => '创建会员（单个）', 'as' => 'openapi.member.create', 'uses' => 'MemberController@create']);
//        $api->post('/members', ['name' => '创建会员（批量）', 'as' => 'openapi.members.create', 'uses' => 'MemberController@batchCreate']);
//        $api->get('/members', ['name' => '获取会员（多个）', 'as' => 'openapi.members.get', 'uses' => 'MemberController@list']);
//        $api->get('/member', ['name' => '获取会员（详情）', 'as' => 'openapi.member.get', 'uses' => 'MemberController@detail']);
//        $api->patch('/member_detail', ['name' => '更新会员信息', 'as' => 'openapi.member.get', 'uses' => 'MemberController@updateDetail']);
//        $api->patch('/member_mobile', ['name' => '更新会员手机号', 'as' => 'openapi.member.get', 'uses' => 'MemberController@updateMobile']);
//        $api->get('/member_orders', ['name' => '获取会员订单（多个）', 'as' => 'openapi.member_orders.get', 'uses' => 'MemberOrderController@list']);
//        $api->get('/member_operate_logs', ['name' => '获取会员操作日志（多个）', 'as' => 'openapi.member_operate_logs.get', 'uses' => 'MemberOperateLogController@list']);

        // 会员积分相关
//        $api->get('/member_point_logs',['name' => '查询会员积分历史记录', 'as' => 'openapi.member_point.get', 'uses' => 'MemberPointController@list']);
//        $api->patch('/member_point',['name' => '增/减会员积分', 'as' => 'openapi.member_point.patch', 'uses' => 'MemberPointController@update']);
//        $api->get('/member_point_orders',['name' => '查询会员订单积分', 'as' => 'openapi.member_point_orders.get', 'uses' => 'MemberOrderController@pointList']);
//        $api->get('/member_point',['name' => '查询会员订单积分', 'as' => 'openapi.member_point.get', 'uses' => 'MemberPointController@detail']);

        // 会员卡基础信息相关
//        $api->get('/member_card',['name' => '查询会员卡基础设置', 'as' => 'openapi.member_card.get', 'uses' => 'MemberCardController@detail']);
//        $api->patch('/member_card',['name' => '修改会员卡基础设置', 'as' => 'openapi.member_card.update', 'uses' => 'MemberCardController@update']);

        // 会员卡等级相关
//        $api->get('/member_card_grades',['name' => '查询会员卡等级列表', 'as' => 'openapi.member_card_grades.get', 'uses' => 'MemberCardGradeController@list']);
//        $api->post('/member_card_grade',['name' => '新增会员卡等级设置', 'as' => 'openapi.member_card_grade.create', 'uses' => 'MemberCardGradeController@create']);
//        $api->patch('/member_card_grade',['name' => '修改会员卡等级设置', 'as' => 'openapi.member_card_grade.update', 'uses' => 'MemberCardGradeController@update']);
//        $api->delete('/member_card_grade',['name' => '删除会员卡等级设置', 'as' => 'openapi.member_card_grade.delete', 'uses' => 'MemberCardGradeController@delete']);

        // 会员卡付费等级相关
//        $api->get('/member_card_vip_grades',['name' => '查询会员卡付费等级设置列表', 'as' => 'openapi.member_card_vip_grades.get', 'uses' => 'MemberCardVipGradeController@list']);
//        $api->post('/member_card_vip_grade',['name' => '新增会员卡付费等级设置', 'as' => 'openapi.member_card_vip_grade.create', 'uses' => 'MemberCardVipGradeController@create']);
//        $api->patch('/member_card_vip_grade',['name' => '更新会员卡付费等级设置', 'as' => 'openapi.member_card_vip_grade.update', 'uses' => 'MemberCardVipGradeController@update']);
//        $api->delete('/member_card_vip_grade',['name' => '删除会员卡付费等级设置', 'as' => 'openapi.member_card_vip_grade.delete', 'uses' => 'MemberCardVipGradeController@delete']);
    });
});