<?php

// 汇付斗拱支付
$api->version('v1', function($api) {
    $api->group(['prefix' => '/bspay', 'namespace' => 'BsPayBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/user/audit_state', ['name' => '用户对象审核状态', 'as' => 'bspay.user.audit_state', 'uses' => 'User@getAuditState']);
        $api->get('/user_indv/get', ['name' => '获取个人用户对象', 'as' => 'bspay.user_indv.info', 'uses' => 'UserIndv@get']);
        $api->post('/user_indv/create', ['name' => '创建个人用户对象', 'as' => 'bspay.user_indv.create', 'uses' => 'UserIndv@create']);
        $api->post('/user_indv/modify', ['name' => '修改个人用户对象(未开户)', 'as' => 'bspay.user_indv.modify', 'uses' => 'UserIndv@modify']);
        $api->post('/user_indv/update', ['name' => '更新个人用户对象', 'as' => 'bspay.user_indv.update', 'uses' => 'UserIndv@update']);
        $api->get('/user_ent/get', ['name' => '获取企业用户对象', 'as' => 'bspay.user_ent.info', 'uses' => 'UserEnt@get']);
        $api->post('/user_ent/create', ['name' => '创建企业用户对象', 'as' => 'bspay.user_ent.create', 'uses' => 'UserEnt@create']);
        $api->post('/user_ent/modify', ['name' => '修改企业用户对象(未开户)', 'as' => 'bspay.user_ent.modify', 'uses' => 'UserEnt@modify']);
        $api->post('/user_ent/update', ['name' => '更新企业用户对象', 'as' => 'bspay.user_ent.update', 'uses' => 'UserEnt@update']);

        $api->get('/sub_approve/list', ['name' => '斗拱子商户审批列表', 'as' => 'bspay.sub_approve.list', 'uses' => 'SubUser@subApproveLists']);
        $api->get('/sub_approve/info/{id}', ['name' => '子商户审批详情', 'middleware' => ['datapass'], 'as' => 'bspay.sub_approve.info', 'uses' => 'SubUser@subApproveInfo']);
        $api->post('/sub_approve/save_audit', ['name' => '斗拱子商户审批保存', 'as' => 'bspay.sub_approve.save_audit', 'uses' => 'SubUser@saveAudit']);
        $api->post('/sub_approve/draw_limit', ['name' => '保存子商户提现限额', 'as' => 'bspay.sub_approve.draw_limit_set', 'uses' => 'SubUser@setDrawLimit']);
        $api->get('/sub_approve/draw_limit', ['name' => '获取子商户提现限额', 'as' => 'bspay.sub_approve.draw_limit_get', 'uses' => 'SubUser@getDrawLimit']);
        $api->post('/sub_approve/draw_cash_config', ['name' => '保存子商户提现限额', 'as' => 'bspay.sub_approve.draw_limit_set', 'uses' => 'SubUser@setDrawCashConfig']);
        $api->get('/sub_approve/draw_cash_config', ['name' => '获取子商户提现限额', 'as' => 'bspay.sub_approve.draw_limit_get', 'uses' => 'SubUser@getDrawCashConfig']);

        $api->get('/regions', ['name' => '获取二级所有地区', 'as' => 'bspay.regions', 'uses' => 'User@getRegions']);
        $api->get('/regions/third', ['name' => '获取三级所有地区', 'as' => 'bspay.regions.third', 'uses' => 'User@getRegionsThird']);

        $api->get('/trade/list', ['name' => '交易单列表', 'middleware'=>'activated', 'as' => 'bspay.trade.getList', 'uses' => 'Trade@getTradelist']);
        $api->get('/trade/info/{trade_id}', ['name' => '交易单详情', 'middleware'=>'activated', 'as' => 'bspay.tradeInfo.get', 'uses' => 'Trade@getTradeInfo']);
        $api->get('/trade/exportdata', ['name'=>'导出交易单列表','middleware'=>'activated', 'as' => 'bspay.trades.list.export', 'uses'=>'ExportData@exportTradeData']);
        
        // 提现相关接口
        $api->get('/withdraw/balance', ['name' => '获取提现余额', 'middleware'=>'activated', 'as' => 'bspay.withdraw.balance', 'uses' => 'Withdraw@getBalance']);
        $api->post('/withdraw/apply', ['name' => '申请提现', 'middleware'=>'activated', 'as' => 'bspay.withdraw.apply', 'uses' => 'Withdraw@apply']);
        $api->get('/withdraw/lists', ['name' => '获取提现记录列表', 'middleware'=>'activated', 'as' => 'bspay.withdraw.lists', 'uses' => 'Withdraw@lists']);
        $api->post('/withdraw/audit', ['name' => '审核提现申请', 'middleware'=>'activated', 'as' => 'bspay.withdraw.audit', 'uses' => 'Withdraw@audit']);
        $api->post('/withdraw/huifu', ['name' => '汇付取现接口', 'middleware'=>'activated', 'as' => 'bspay.withdraw.huifu', 'uses' => 'Withdraw@huifuWithdraw']);
        $api->get('/withdraw/exportdata', ['name'=>'导出提现记录','middleware'=>['activated', 'datapass'], 'as' => 'bspay.withdraw.export', 'uses'=>'ExportData@exportWithdrawData']);

    });

    $api->group(['namespace' => 'BsPayBundle\Http\Api\V1\Action', 'prefix'=>'bspay'], function($api) {
        $api->post('/callback/{eventType}', ['as' => 'bspay.callback',  'uses'=>'CallBack@handle']);
    });
});
