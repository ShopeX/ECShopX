<?php


$api->version('v1', function ($api) {

    // 团长端
     $api->group(['prefix' => 'h5app', 'namespace' => 'CommunityBundle\Http\FrontApi\V1\Action\chief', 'middleware' => ['dingoguard:h5app']], function ($api) {
        //团长申请
        $api->get('/wxapp/community/chief/aggrement_and_explanation', ['as' => 'front.wxapp.community.chief.aggrement_and_explanation.get', 'uses' => 'CommunityChief@getAggrementAndExplanation']);
        $api->get('/wxapp/community/chief/apply_fields', ['as' => 'front.wxapp.community.chief.apply_fields.get', 'uses' => 'CommunityChief@getApplyFields']);
        $api->post('/wxapp/community/chief/apply', ['as' => 'front.wxapp.community.chief.apply', 'uses' => 'CommunityChief@apply']);
        $api->get('/wxapp/community/chief/apply', ['as' => 'front.wxapp.community.chief.applyinfo.get', 'uses' => 'CommunityChief@getApplyInfo']);
        //检查用户是否是团长
        $api->post('/wxapp/community/checkChief', ['as' => 'front.wxapp.community.check.chief', 'uses' => 'CommunityChief@checkChief']);

        //团长业绩&提现
        $api->post('/wxapp/community/chief/cash_withdrawal', ['as' => 'front.wxapp.community.chief.cash_withdrawal.apply', 'uses' => 'CommunityChiefCashWithdrawal@applyCashWithdrawal']);
        $api->get('/wxapp/community/chief/cash_withdrawal', ['as' => 'front.wxapp.community.chief.cash_withdrawal.list', 'uses' => 'CommunityChiefCashWithdrawal@getCashWithdrawalList']);
        $api->get('/wxapp/community/chief/cash_withdrawal/account', ['as' => 'front.wxapp.community.chief.cash_withdrawal.account.get', 'uses' => 'CommunityChiefCashWithdrawal@getCashWithdrawalAccount']);
        $api->post('/wxapp/community/chief/cash_withdrawal/account', ['as' => 'front.wxapp.community.chief.cash_withdrawal.account.update', 'uses' => 'CommunityChiefCashWithdrawal@updateCashWithdrawalAccount']);
        $api->get('/wxapp/community/chief/cash_withdrawal/count', ['as' => 'front.wxapp.community.chief.cash_withdrawal.count', 'uses' => 'CommunityChiefCashWithdrawal@cashWithdrawalCount']);

        //团购活动
        $api->get('/wxapp/community/activity/lists', ['as' => 'front.wxapp.community.activity.lists', 'uses' => 'CommunityActivity@getActivityList']);
        $api->get('/wxapp/community/chief/activity/{activity_id}', ['as' => 'front.wxapp.community.activity.chief_detail', 'uses' => 'CommunityActivity@getActivityDetail']);

        //订单
        $api->get('/wxapp/community/orders', ['name'=>'获取订单列表', 'as' => 'front.wxapp.community.order.lists', 'uses' => 'CommunityOrder@getOrderList']);
        $api->get('/wxapp/community/orders/export', ['name'=>'导出团购订单', 'as' => 'front.wxapp.community.order.export', 'uses' => 'CommunityOrder@exportActivityOrderData']);
        $api->post('/wxapp/community/orders/batch_writeoff', ['name'=>'团购订单一键核销', 'as' => 'front.wxapp.community.order.writeoff.batch', 'uses'=>'CommunityOrder@batchWriteoff']);
        $api->post('/wxapp/community/orders/qr_writeoff', ['name'=>'团购订单扫码核销', 'as' => 'front.wxapp.community.order.writeoff.qr', 'uses'=>'CommunityOrder@writeoffQR']);
        $api->post('/writeoff/{order_id}', ['name'=>'团购订单自助核销', 'as' => 'front.wxapp.community.order.writeoff', 'uses'=>'CommunityOrder@writeoff']);

        // 获取团长的店铺列表
        $api->get('/wxapp/community/chief/distributor', ['as' => 'front.wxapp.community.chief.distributor', 'uses' => 'CommunityChief@getDisitrbutorList']);
        // 获取团长的商品列表
        $api->get('/wxapp/community/chief/items', ['as' => 'front.wxapp.community.chief.items', 'uses' => 'CommunityActivity@getDisitrbutorItemList']);
        // 获取团长的自提点列表
        $api->get('/wxapp/community/chief/ziti', ['as' => 'front.wxapp.community.chief.ziti.list', 'uses' => 'CommunityChiefZiti@actionList']);

        // 添加团长自提点
        $api->post('/wxapp/community/chief/ziti', ['as' => 'front.wxapp.community.chief.ziti.create', 'uses' => 'CommunityChiefZiti@actionCreate']);

        // 修改团长自提点
        $api->post('/wxapp/community/chief/ziti/{ziti_id}', ['as' => 'front.wxapp.community.chief.ziti.update', 'uses' => 'CommunityChiefZiti@actionUpdate']);

        // 团长获取活动列表
        $api->get('/wxapp/community/chief/activity', ['as' => 'front.wxapp.community.chief.activity.list', 'uses' => 'CommunityActivity@getActivityList']);

        // 团长创建活动
        $api->post('/wxapp/community/chief/activity', ['as' => 'front.wxapp.community.chief.activity.create', 'uses' => 'CommunityActivity@createActivity']);

        // 团长修改活动
        $api->post('/wxapp/community/chief/activity/{activity_id}', ['as' => 'front.wxapp.community.chief.activity.update', 'uses' => 'CommunityActivity@updateActivity']);

        // 团长修改活动状态
        $api->post('/wxapp/community/chief/activity_status/{activity_id}', ['as' => 'front.wxapp.community.chief.activity.update.status', 'uses' => 'CommunityActivity@updateActivityStatus']);

        // 团长对活动操作确认收货
         $api->post('/wxapp/community/chief/confirm_delivery/{activity_id}', ['as' => 'front.wxapp.community.chief.activity.confirm.delivery', 'uses' => 'CommunityActivity@confirmDeliveryStatus']);
    });

    // 会员端
    $api->group(['prefix' => 'h5app', 'namespace' => 'CommunityBundle\Http\FrontApi\V1\Action\member', 'middleware' => ['dingoguard:h5app'], 'providers' => 'jwt'], function ($api) {

         $api->get('/wxapp/community/member/activity', ['as' => 'front.wxapp.community.activity.member_lists', 'uses' => 'CommunityActivity@getActivityList']);
         $api->get('/wxapp/community/member/activity/{activity_id}', ['as' => 'front.wxapp.community.activity.member_detail', 'uses' => 'CommunityActivity@getActivityDetail']);
        // 获取团长的商品列表
        $api->get('/wxapp/community/member/items', ['as' => 'front.wxapp.community.member.items', 'uses' => 'CommunityActivity@getDisitrbutorItemList']);
    });
});
