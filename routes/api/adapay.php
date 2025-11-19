<?php
$api->version('v1', function($api) {
    $api->group(['prefix' => '/adapay', 'namespace' => 'AdaPayBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/is_open', ['name' => '是否开通', 'as' => 'adapay.is_open', 'uses' => 'OpenAccount@isOpen']);
        $api->get('/withdrawset', ['name' => '获取提现设置', 'middleware'=>'activated', 'as' => 'adapay.withdraw.get', 'uses' => 'AdapayWithdrawSet@index']);
        $api->post('/withdrawset', ['name' => '提现设置', 'middleware'=>'activated', 'as' => 'adapay.withdraw.save', 'uses' => 'AdapayWithdrawSet@save']);
        $api->get('/drawcash/getList', ['name' => '提现记录', 'middleware'=>['activated', 'datapass'], 'as' => 'adapay.drawcash.getList', 'uses' => 'AdapayDrawCash@getList']);
        $api->post('/withdraw', ['name' => '汇付提现申请', 'middleware'=>'activated', 'as' => 'adapay.withdraw', 'uses' => 'AdapayDrawCash@withdraw']);
        $api->get('/trade/list', ['name' => '交易单列表', 'middleware'=>'activated', 'as' => 'adapay.trade.getList', 'uses' => 'AdapayTrade@getTradelist']);
        $api->get('/trade/info/{trade_id}', ['name' => '交易单详情', 'middleware'=>'activated', 'as' => 'adapay.tradeInfo.get', 'uses' => 'AdapayTrade@getTradeInfo']);
        $api->get('/trade/exportdata', ['name'=>'导出交易单列表','middleware'=>'activated', 'as' => 'adapay.trades.list.export', 'uses'=>'ExportData@exportTradeData']);
        $api->get('/distributor/list', ['name' => '获取店铺列表', 'middleware'=>'activated', 'as' => 'adapay.distributor.list', 'uses' => 'AdapayTrade@getDistributorList']);   

        $api->post('/merchant_entry/create', ['name' => '创建开户进件申请', 'as' => 'adapay.merchant_entry.create', 'uses' => 'OpenAccount@merchantEntryCreate']);
        $api->get('/merchant_entry/info', ['name' => '开户进件详情', 'as' => 'adapay.merchant_entry.info', 'uses' => 'OpenAccount@merchantEntryInfo']);

        $api->get('/member/auditState', ['name' => '用户对象审核状态', 'as' => 'adapay.member.auditState', 'uses' => 'Member@getAuditState']);
        $api->get('/member/setValid', ['name' => '用户进入结算中心状态', 'as' => 'adapay.member.valid', 'uses' => 'Member@setValid']);
        $api->get('/member/get', ['name' => '获取个人用户对象', 'as' => 'adapay.member.info', 'uses' => 'Member@get']);
        $api->post('/member/create', ['name' => '创建个人用户对象', 'as' => 'adapay.member.create', 'uses' => 'Member@create']);
        $api->post('/member/modify', ['name' => '修改个人用户对象(未开户)', 'as' => 'adapay.member.modify', 'uses' => 'Member@modify']);
        $api->post('/member/update', ['name' => '更新个人用户对象', 'as' => 'adapay.member.update', 'uses' => 'Member@update']);
        $api->get('/corp_member/get', ['name' => '获取企业用户对象', 'as' => 'adapay.corp_member.info', 'uses' => 'CorpMember@get']);
        $api->post('/corp_member/create', ['name' => '创建企业用户对象', 'as' => 'adapay.corp_member.create', 'uses' => 'CorpMember@create']);
        $api->post('/corp_member/modify', ['name' => '修改企业用户对象(未开户)', 'as' => 'adapay.corp_member.modify', 'uses' => 'CorpMember@modify']);
        $api->post('/corp_member/update', ['name' => '更新企业用户对象', 'as' => 'adapay.corp_member.update', 'uses' => 'CorpMember@update']);
        $api->get('/bank/list', ['name' => '获取结算银行列表', 'as' => 'adapay.bank.list', 'uses' => 'OpenAccount@getBanksLists']);   
        $api->get('/regions/list', ['name' => '获取省市列表(四位码)', 'as' => 'adapay.regions.list', 'uses' => 'OpenAccount@getRegionsLists']);   
        $api->get('/regions_third/list', ['name' => '获取省市区列表(六位码或九位码)', 'as' => 'adapay.regions_third.list', 'uses' => 'OpenAccount@getRegionsThirdLists']);   
        $api->post('/merchant_resident/create', ['name' => '商户入驻申请', 'as' => 'adapay.merchant_resident.create', 'uses' => 'OpenAccount@merchantResidentCreate']);   
        $api->get('/merchant_resident/info', ['name' => '商户入驻详情', 'as' => 'adapay.merchant_resident.info', 'uses' => 'OpenAccount@merchantResidentInfo']);   
        $api->get('/wx_business_cat/list', ['name' => '获取微信经营类目', 'as' => 'adapay.wx_business_cat.list', 'uses' => 'OpenAccount@getWxBusinessCatList']);
        $api->get('alipay_industry_cat/list', ['name' => '获取支付宝行业类目', 'as' => 'adapay.alipay_industry_cat.list', 'uses' => 'OpenAccount@getAlipayIndustryCatList']);
        $api->post('/license/upload', ['name' => '上传商户证照', 'as' => 'adapay.license.upload', 'uses' => 'OpenAccount@uploadLicense']);
        $api->post('/license_submit/create', ['name' => '提交商户证照', 'as' => 'adapay.license_submit.create', 'uses' => 'OpenAccount@submitLicense']);   
        $api->get('/license_submit/info', ['name' => '商户证照详情', 'as' => 'adapay.license_submit.info', 'uses' => 'OpenAccount@submitLicenseInfo']);
        $api->get('/open_account/step', ['name' => '商户开户步骤', 'middleware' => ['datapass'], 'as' => 'adapay.open_account.step', 'uses' => 'OpenAccount@openAccountStep']);
        $api->get('/generate/key', ['name' => '生成RAS密钥', 'as' => 'adapay.generate.key', 'uses' => 'OpenAccount@generateKey']);
        $api->get('/other/cat', ['name' => '费率 入驻 商户分类', 'as' => 'adapay.other.cat', 'uses' => 'OpenAccount@otherCat']);
        
        $api->get('/sub_approve/list', ['name' => '子商户审批列表', 'as' => 'adapay.sub_approve.list', 'uses' => 'SubMerchant@subApproveLists']);
        $api->get('/sub_approve/info/{id}', ['name' => '子商户审批详情', 'middleware' => ['datapass'], 'as' => 'adapay.sub_approve.info', 'uses' => 'SubMerchant@subApproveInfo']);
        $api->post('/sub_approve/save_split_ledger', ['name' => '子商户审批保存分账信息', 'as' => 'adapay.sub_approve.save_split_ledger', 'uses' => 'SubMerchant@saveSplitLedger']);
        $api->post('/sub_approve/draw_limit', ['name' => '保存子商户提现限额', 'as' => 'adapay.sub_approve.draw_limit_set', 'uses' => 'SubMerchant@setDrawLimit']);
        $api->get('/sub_approve/draw_limit', ['name' => '获取子商户提现限额', 'as' => 'adapay.sub_approve.draw_limit_get', 'uses' => 'SubMerchant@getDrawLimit']);
        $api->post('/sub_approve/draw_cash_config', ['name' => '保存子商户提现限额', 'as' => 'adapay.sub_approve.draw_limit_set', 'uses' => 'SubMerchant@setDrawCashConfig']);
        $api->get('/sub_approve/draw_cash_config', ['name' => '获取子商户提现限额', 'as' => 'adapay.sub_approve.draw_limit_get', 'uses' => 'SubMerchant@getDrawCashConfig']);

        $api->get('/dealer/list', ['name' => '经销商列表', 'middleware' => ['datapass'], 'as' => 'adapay.dealer.list', 'uses' => 'Dealer@dealerList']);
        $api->get('/dealer/distributors', ['name' => '经销商关联店铺列表', 'middleware' => ['datapass'], 'as' => 'adapay.dealer.distributorList', 'uses' => 'Dealer@distributorList']);
        $api->get('/dealer/{id}', ['name' => '经销商详情', 'middleware' => ['datapass'], 'as' => 'adapay.dealer.info', 'uses' => 'Dealer@dealerInfo']);
        $api->put('/dealer/disable', ['name' => '经销商开启 禁用', 'as' => 'adapay.dealer.disable', 'uses' => 'Dealer@openOrDisable']);
        $api->put('/dealer/rel', ['name' => '经销商关联店铺', 'as' => 'adapay.dealer.rel', 'uses' => 'Dealer@dealerRelDistributor']);

        $api->get('/log/list', ['name' => '操作日志列表', 'as' => 'adapay.log.list', 'uses' => 'AdapayLog@getList']);
        $api->put('/dealer/reset/{operatorId}', ['name' => '经销商重置密码', 'as' => 'adapay.dealer.reset', 'uses' => 'Dealer@resetPassword']);
        $api->put('/dealer/update/{operatorId}', ['name' => '经销商端账号编辑', 'as' => 'adapay.dealer.update', 'uses' => 'Dealer@update']);
        $api->delete('/dealer/sub/del/{operatorId}', ['name' => '删除经销商子账号', 'as' => 'adapay.dealer.del', 'uses' => 'Dealer@delDealerSub']);
        $api->get('/member/list', ['name' => 'adapay开户列表(店铺端 经销商端)', 'middleware' => ['datapass'], 'as' => 'adapay.member.list', 'uses' => 'Member@lists']);
        $api->get('/dealer/dealer_parent/get', ['name' => '获取经销商主账号id', 'as' => 'adapay.dealer_parent.get', 'uses' => 'Dealer@getDealerParentId']);

    });

    $api->group(['namespace' => 'AdaPayBundle\Http\Api\V1\Action', 'prefix'=>'adapay'], function($api) {
        $api->post('/callback', ['as' => 'adapay.callback',  'uses'=>'CallBack@handle']);
    });
});