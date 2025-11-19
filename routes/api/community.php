<?php

$api->version('v1', function($api) {

    $api->group(['prefix' => 'community', 'namespace' => 'CommunityBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {

        //社区团购团长申请字段
        $api->get('/chief/apply_fields', ['name' => '配置字段列表', 'as' => 'community.chief.apply_field.list', 'uses' => 'CommunityChiefApplyFields@list']);
        $api->post('/chief/apply_field', ['name' => '创建字段', 'as' => 'community.chief.apply_field.create', 'uses' => 'CommunityChiefApplyFields@create']);
        $api->post('chief/apply_field/switch/{id}', ['name' => '配置字段开关', 'as' => 'community.chief.apply_field.switch', 'uses' => 'CommunityChiefApplyFields@switch']);
        $api->post('/chief/apply_field/{id}', ['name' => '修改字段', 'as' => 'community.chief.apply_field.update', 'uses' => 'CommunityChiefApplyFields@update']);
        $api->delete('/chief/apply_field/{id}', ['name' => '删除字段', 'as' => 'community.chief.apply_field.delete', 'uses' => 'CommunityChiefApplyFields@delete']);
        //团长申请
        $api->get('/chief/apply/wxaCode', ['name' => '团长申请页小程序码', 'as' => 'community.chief.apply.wxaCode', 'uses' => 'CommunityChief@getWxaCode']);
        $api->get('/chief/apply/list', ['name' => '团长申请列表', 'as' => 'community.chief.apply.list', 'uses' => 'CommunityChief@getApplyList']);
        $api->get('/chief/apply/info/{apply_id}', ['name' => '团长申请信息', 'as' => 'community.chief.apply.info', 'uses' => 'CommunityChief@getApplyInfo']);
        $api->post('/chief/approve/{apply_id}', ['name' => '团长申请审批', 'as' => 'community.chief.approve', 'uses' => 'CommunityChief@approve']);

        $api->post('/chief/setMemberCommunity', ['name' => '设置团长', 'as' => 'community.setting.chief', 'uses' => 'CommunityChief@setMemberCommunity']);
        $api->get('/chief/list', ['name' => '团长列表', 'as' => 'community.chief.list', 'uses' => 'CommunityChief@getChiefList']);
        $api->get('/chief/{chief_id}', ['name' => '团长列表', 'as' => 'community.chief.info', 'uses' => 'CommunityChief@getChiefInfo']);

        //团长业绩&提现
        $api->get('/rebate/count', ['name' => '团长业绩列表', 'as' => 'community.chief.rebate.count', 'uses' => 'CommunityChiefCashWithdrawal@getChiefRebateCount']);
        $api->get('/cash_withdrawal', ['name' => '团长佣金提现列表', 'as' => 'community.chief.cash_withdrawal.list', 'uses' => 'CommunityChiefCashWithdrawal@getCashWithdrawalList']);
        $api->post('/cash_withdrawal/{cash_withdrawal_id}', ['name' => '处理团长佣金提现申请', 'as' => 'community.chief.cash_withdrawal.process', 'uses' => 'CommunityChiefCashWithdrawal@processCashWithdrawal']);
        $api->get('/cash_withdrawal/payinfo/{cash_withdrawal_id}', ['name' => '获取佣金提现支付信息', 'as' => 'community.chief.cash_withdrawal.payinfo', 'uses' => 'CommunityChiefCashWithdrawal@getMerchantTradeList']);

        // 社区团购订单
        $api->get('/orders', ['name'=>'获取订单列表', 'middleware'=>['datapass'], 'as' => 'community.order.list.get', 'uses'=>'CommunityOrder@getOrderList']);
        $api->get('/orders/export', ['name'=>'导出团购订单', 'middleware'=>['datapass'], 'as' => 'community.order.list.export', 'uses'=>'CommunityOrder@exportActivityOrderData']);
        $api->get('/order/{order_id}', ['name'=>'获取订单详情', 'middleware'=>['datapass'], 'as' => 'community.order.detail.get', 'uses'=>'CommunityOrder@getOrderDetail']);

        //社区团购活动管理
        $api->get('/list', ['name' => '活动列表', 'as' => 'community.list', 'uses' => 'CommunityActivity@getList']);
        // 活动确认发货
        $api->post('/activity/confirm/delivery', ['name' => '确认发货', 'as' => 'community.activity.confirm.delivery', 'uses' => 'CommunityActivity@confirmDeliveryStatus']);
        //社区团购活动管理发货
        $api->post('/chief/deliver', ['name' => '店铺发货', 'as' => 'community.activity.deliver', 'uses' => 'CommunityActivity@deliver']);

        // 社区团购商品池
        $api->get('/items', ['name' => '商品列表', 'as' => 'community.items.list.get', 'uses' => 'CommunityItems@getItemsList']);
        $api->post('/items', ['name' => '添加商品', 'as' => 'community.items.add', 'uses' => 'CommunityItems@createItems']);
        $api->post('/itemMinDeliveryNum', ['name' => '修改商品起送量', 'as' => 'community.item.minDeliveryNum.update', 'uses' => 'CommunityItems@updateItemMinDeliveryNum']);
        $api->post('/itemSort', ['name' => '修改商品排序编号', 'as' => 'community.item.sort.update', 'uses' => 'CommunityItems@updateItemSort']);
        $api->delete('/item/{goods_id}', ['name' => '删除商品', 'as' => 'community.item.delete', 'uses' => 'CommunityItems@deleteItem']);

        //社区团设置
        $api->get('/activity/setting', ['name' => '商品列表', 'as' => 'community.setting.get', 'uses' => 'CommunitySetting@get']);
        $api->post('/activity/setting', ['name' => '添加商品', 'as' => 'community.setting.save', 'uses' => 'CommunitySetting@save']);
    });
});
