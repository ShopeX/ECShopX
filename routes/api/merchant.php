<?php
$api->version('v1', function($api) {
    $api->group(['prefix' => '/merchant', 'namespace' => 'MerchantBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/basesetting', ['name' => '获取基础设置', 'middleware'=>'activated', 'as' => 'merchant.basesetting.get', 'uses' => 'MerchantSetting@getBase']);
        $api->post('/basesetting', ['name' => '保存基础设置', 'middleware'=>'activated', 'as' => 'merchant.basesetting.save', 'uses' => 'MerchantSetting@saveBase']);

        $api->get('/type/list', ['name' => '获取商户类型列表', 'middleware'=>'activated', 'as' => 'merchant.type.list', 'uses' => 'MerchantSetting@getTypeList']);
        $api->post('/type/create', ['name' => '新增商户类型', 'middleware'=>'activated', 'as' => 'merchant.type.create', 'uses' => 'MerchantSetting@createType']);
        $api->put('/type/{id}', ['name' => '更新单条商户类型信息', 'as' => 'merchant.type.update', 'uses' => 'MerchantSetting@updateType']);
        $api->delete('/type/{id}', ['name' => '删除商户类型', 'as' => 'merchant.type.delete', 'uses' => 'MerchantSetting@deleteType']);
        $api->get('/operator', ['name' => '获取商户账号列表', 'middleware'=>['activated', 'datapass'], 'as' => 'merchant.operator.list', 'uses' => 'MerchantOperator@getOperatorList']);
        $api->post('/operator', ['name' => '商户修改密码', 'middleware'=>'activated', 'as' => 'merchant.operator.save', 'uses' => 'MerchantOperator@updateOperatorAccount']);
        $api->put('/operator/{id}', ['name' => '平台重置商户密码', 'middleware'=>'activated', 'as' => 'merchant.operator.update', 'uses' => 'MerchantOperator@resetOperatorAccount']);


        $api->get('/settlement/apply/list', ['name' => '获取商户入驻申请列表', 'middleware'=>'activated', 'as' => 'merchant.settlement.apply.list', 'uses' => 'MerchantSettlementApply@getList']);
        $api->get('/settlement/apply/{id}', ['name' => '获取商户入驻申请详情', 'middleware'=>['activated', 'datapass'], 'as' => 'merchant.settlement.apply.detail', 'uses' => 'MerchantSettlementApply@getDetail']);
        $api->post('/settlement/apply/audit', ['name' => '审核商户入驻申请', 'middleware'=>['activated'], 'as' => 'merchant.settlement.apply.audit', 'uses' => 'MerchantSettlementApply@auditData']);

        $api->get('/list', ['name' => '获取商户列表', 'middleware'=>['activated', 'datapass'], 'as' => 'merchant.list', 'uses' => 'Merchant@getList']);
        $api->get('/detail/{id}', ['name' => '获取商户详情', 'middleware'=>['activated', 'datapass'], 'as' => 'merchant.detail.get', 'uses' => 'Merchant@getDetail']);
        $api->post('/{id}', ['name' => '更新商户', 'middleware'=>['activated'], 'as' => 'merchant.update', 'uses' => 'Merchant@updateMerchant']);
        $api->post('/', ['name' => '新增商户', 'middleware'=>['activated'], 'as' => 'merchant.create', 'uses' => 'Merchant@createMerchant']);
        $api->get('/visibletype/list', ['name' => '获取可见商户类型列表', 'middleware'=>'activated', 'as' => 'merchant.visibletype.list', 'uses' => 'MerchantSetting@getVisibleTypeList']);
        $api->post('/disabled/update/{id}', ['name' => '修改单个商户的禁用状态', 'middleware'=>['activated'], 'as' => 'merchant.disabled.update', 'uses' => 'Merchant@updateMerchantDisabled']);
        $api->post('/auditgoods/update/{id}', ['name' => '修改单个商户的审核商品状态', 'middleware'=>['activated'], 'as' => 'merchant.audit.goods.update', 'uses' => 'Merchant@updateMerchantAuditGoods']);
        $api->get('/info', ['name' => '获取商户详情', 'middleware'=>['activated'], 'as' => 'merchant.info.get', 'uses' => 'Merchant@getInfo']);


    });

    $api->group(['prefix' => '/supplier', 'namespace' => 'SupplierBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated','shoplog'], 'providers' => 'jwt'], function($api) {
        
        //供应商
        $api->post('/register', ['name' => '供应商入驻', 'as' => 'supplier.register', 'uses' => 'Supplier@register']);
        $api->get('/get_supplier_info', ['name' => '查询供应商信息', 'as' => 'supplier.get_supplier_info', 'uses' => 'Supplier@getSupplierInfo']);
        $api->get('/get_supplier_list', ['name' => '查询供应商列表', 'as' => 'supplier.get_supplier_list', 'uses' => 'Supplier@getSupplierList']);
        $api->post('/check_supplier', ['name' => '审核供应商', 'as' => 'supplier.check_supplier', 'uses' => 'Supplier@checkSupplier']);
        $api->post('/batch_review_items', ['name' => '批量审核供应商商品', 'as' => 'supplier.batch_review_items', 'uses' => 'SupplierItem@batchReviewItems']);
    });
});
