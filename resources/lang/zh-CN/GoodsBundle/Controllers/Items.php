<?php


$main = [
    'api_1' => 'Incorrect write-off type parameter',
];

// Service
$itemsService = [
    'items_service_1' => 'Please select the correct product status',
    
    // ItemsService异常
    'medical_industry_not_enabled' => '医药行业功能未开启',
    'missing_prescription_config' => '缺少处方业务集成配置',
    'invalid_spec_data' => '请填写正确的规格数据',
    'non_gift_price_must_be_positive' => '非赠品商品销售价必须大于0',
    'invalid_item_update' => '更新的商品无效',
    'barcode_exists' => '条形码已经存在',
    'please_select_correct_item_status' => '请选择正确的商品状态',
    'item_bn_exists' => '该货号商品已存在: ',
    'selected_category_not_exist' => '选中的分类不存在 或 错误',
    'selected_main_category_not_exist' => '您选中的主类目不存在',
    'selected_brand_not_exist' => '您选中的品牌不存在',
    'selected_param_not_exist' => '您选中的参数不存在',
    'selected_param_value_not_exist' => '您选中的参数值不存在',
    'selected_spec_not_exist' => '您选中的规格不存在',
    'selected_spec_value_not_exist' => '您选中的规格值不存在',
    'selected_shipping_template_not_exist' => '您选中的运费模板不存在',
    'item_id_cannot_be_empty' => '商品id不能为空',
    'delete_item_info_error' => '删除商品信息有误',
    'shop_item_info_error' => '店铺商品信息有误，不可删除',
    'item_not_exists' => '商品不存在',
    'please_confirm_item_info' => '请确认您的商品信息后再提交.',
    'supplier_item_not_saleable' => '供应商商品不可售',
    'prescription_drug_requires_medical_config' => '处方药商品需要开启医药行业配置才能上架',
    'prescription_drug_requires_audit' => '处方药商品【{0}】审核通过后才能上架',
    'zero_price_cannot_be_non_gift' => '存在价格设置为0元的商品无法设置为非赠品，请检查后再次提交',
    
    // ItemsCategoryService异常
    'category_name_exists' => '分类名称已存在',
    
    // ItemsAttributesService异常
    'param_spec_value_limit' => '参数或规格值不能超过60个',
    'please_add_param_or_spec_value' => '请添加参数或者规格值',
    'brand_name_duplicate' => '品牌名称不能重复',
    'param_value_cannot_be_empty' => '参数值不能为空',
    'has_associated_items' => '有关联商品，请先处理关联的商品',
    'has_associated_pointsmall_items' => '有关联积分商城商品，请先处理关联的积分商城商品',
    'value_has_associated_items' => '数值有关联商品，请先处理关联的商品',
    'update_data_not_exists' => '更新的数据不存在',
    
    // KeywordsService异常
    'record_not_exists' => '记录不存在',
    
    // ItemsTagsService异常
    'tag_name_duplicate' => '标签名称不能重复',
    'tag_not_exists' => '标签不存在',
    'no_permission_to_edit_tag' => '没有权限编辑该标签',
    
    // ItemsMedicineService异常
    'item_not_medicine' => '商品【{0}】非药品',
    'item_medicine_data_missing' => '商品【{0}】药品数据缺失',
    'item_medicine_audit_passed' => '商品【{0}】药品审核已通过',
    'prescription_drug_requires_audit_to_shelve' => '处方药需要审核通过后才能上架',
    'please_fill_medicine_spec' => '请填写药品规格',
    'prescription_drug_usage_tips_required' => '处方药用药提示不能为空',
    'prescription_drug_symptoms_required' => '处方药用药症状不能为空',
    
    // ServiceLabelsService异常
    'delete_member_value_attr_info_error' => '删除会员数值属性信息有误.',
    'member_value_attr_id_cannot_be_empty' => '会员数值属性id不能为空.',
    'please_confirm_member_value_attr' => '请确认您的会员数值属性信息后再提交.',
    
    // ItemsProfitService异常
    'item_get_failed' => '商品获取失败',
    'guide_profit_config_error' => '导购分润配置错误',
    'profit_type_error' => '分润类型错误',
    'save_item_guide_profit_config_failed' => '保存商品导购分润配置失败',
    
    // ItemsCommissionService异常
    'spu_settlement_commission_must_be_non_negative' => 'SPU结算佣金为大于等于0的数字',
    'sku_settlement_commission_must_be_non_negative' => 'SKU结算佣金为大于等于0的数字',
    
    // ItemsCategoryProfitService异常
    'item_category_get_failed' => '商品分类获取失败',
    'main_category_id_not_exists' => '主类目id不存在',
    'save_item_category_guide_profit_config_failed' => '保存商品分类导购分润配置失败',
    
    // Items\Services异常
    'please_select_item_content' => '请选择商品内容',
    
    // Items\Normal异常
    'sku_code_duplicate' => 'SKU编码重复: ',
    'sku_code_cannot_duplicate' => 'SKU编码不能重复: ',
    'supplier_goods_bn_duplicate' => '{0}供应商货号重复，请添加正确的商品编码',
    'supplier_goods_bn_duplicate_general' => '供应商货号重复，请添加正确的商品编码',
    
    // Upload相关异常
    'tb_items_upload_excel_only' => '淘宝商品上传只支持Excel文件格式',
    'purchase_goods_upload_excel_only' => '活动商品信息上传只支持Excel文件格式(xlsx)',
    'max_items_upload_limit' => '每次最多上传{0}个商品...请减少后再提交',
    'item_bn_cannot_be_empty' => '货号不能为空...请检查数据',
    'column_must_import' => '{0}必须导入',
    'normal_goods_upload_excel_only' => '实体商品信息上传只支持Excel文件格式(xlsx)',
    'item_code_exists_in_other_shop' => '商品编码已存在其他店铺中，不能更新',
    'profit_support_param_error' => '是否支持分润参数错误',
    'new_user_profit_amount_required' => '拉新分润金额不能为空',
    'promotion_profit_amount_required' => '推广分润金额不能为空',
    'item_status_error' => '商品状态错误',
    'please_fill_shipping_template' => '请填写商品运费模版',
    'shipping_template_not_exists' => '填写的运费模版不存在',
    'please_upload_management_category' => '请上传管理分类',
    'management_category_must_be_three_levels' => '上传管理分类必须是三层级,{0}',
    'management_category_not_exists' => '上传管理分类不存在,{0}',
    'unrecognized_management_category' => '无法识别的管理分类,{0}',
    'please_upload_item_category' => '请上传商品分类',
    'upload_management_category_param_error' => '上传管理分类参数有误',
    'upload_item_category_param_error' => '上传商品分类参数有误',
    'brand_name_not_exists' => '{0} 品牌名称不存在',
    'item_param_not_exists' => '商品参数不存在',
    'item_spec_parse_error' => '商品规格解析错误',
    'item_spec_value_parse_error' => '商品规格值解析错误',
    'item_spec_invalid_values' => '商品规格[{0}]存在无效值',
    'item_spec_value_invalid' => '商品规格值[{0}]无效',
    'same_spec_value_item_exists' => '相同规格值的商品已存在',
    'normal_goods_tag_upload_excel_only' => '实体商品批量打标签信息上传只支持Excel文件格式(xlsx)',
    'item_not_found_query' => '未查询到对应商品',
    'tag_not_found_query' => '未查询到对应标签',
    'item_tag_conflict_with_activity' => '商品标签导致活动冲突',
    'update_item_tag_failed' => '更新商品标签数据失败，请重新上传或联系客服处理',
    'normal_goods_store_upload_excel_only' => '实体商品库存信息上传只支持Excel文件格式(xlsx)',
    'can_only_import_own_shop_item_store' => '只能导入所属店铺的商品库存',
    'can_only_import_dealer_related_shop_item_store' => '只能导入所属经销商关联店铺的商品库存',
    'shop_item_not_exists' => '店铺商品不存在',
    'store_is_headquarter_store' => '门店库存为总部库存',
    'normal_goods_profit_upload_excel_only' => '实体商品分润信息上传只支持Excel文件格式(xlsx)',
    'marketing_goods_upload_excel_only' => '活动商品信息上传只支持Excel文件格式(xlsx)',
    'invalid_item_spec' => '存在无效的商品规格',
    'invalid_item_spec_value' => '存在无效的商品规格值',
    'item_code_or_barcode_required' => '商品编号 条形码 必填一项',
    'is_epidemic_item_required' => '是否设为疫情商品 必填',
];

$exceptions = [
    'get_items_detail_error' => '获取商品详情出错.',
    'get_items_info_error' => '获取商品信息有误，请确认商品ID.',
    'get_items_list_error' => '获取商品列表出错.',
    'get_member_price_detail_error' => '获取会员价详情出错.',
    'param_error' => '参数错误.',
    'item_not_exist_or_offline' => '商品不存在或者已下架',
    'item_not_found' => '商品找不到.',
    'special_char_not_supported' => '系统当前不支特殊字符搜索',
    'get_items_intro_error' => '获取商品图文详情出错.',
    
    // API异常信息
    'delete_item_error' => '删除商品出错.',
    'commission_ratio_invalid' => '佣金比例必须大于0小于100',
    'item_not_exist' => '商品信息不存在，请确认商品ID.',
    'min_stock_warning' => '预警库存最少为1',
    'please_select_shipping_template' => '请选择运费模板',
    'please_fill_sort_number' => '请填写排序编号',
    'item_price_format_error' => '商品价格格式有误',
    'params_error' => '参数有误',
    'please_specify_item' => '请指定商品再操作',
    'item_not_specified' => '未指定商品',
    'please_select_item_and_category' => '请选择商品和分类的数据',
    'get_miniapp_code_params_error' => '获取小程序码参数出错，请检查.',
    'miniapp_not_opened' => '没有开通此小程序，不能下载.',
    'params_required' => '参数必填.',
    'supplier_not_support_operation' => '供应商不支持该操作',
    'attribute_not_exist' => '属性不存在attribute_id:',
    'get_items_increment_data_error' => '获取商品增量数据出错.',
    
    // 其他控制器异常信息
    'delete_category_error' => '删除分类出错.',
    'shop_not_bind_wdt_erp' => '店铺没有绑定旺店通ERP门店',
    'shop_not_bind_jushuitan_erp' => '店铺没有绑定聚水潮ERP门店',
    'add_member_value_attr_error' => '添加会员数值属性出错.',
    'update_member_value_attr_error' => '更新会员数值属性出错.',
    'delete_member_value_attr_error' => '删除会员数值属性出错.',
    'get_member_value_attr_error' => '获取会员数值属性出错.',
    'get_member_value_attr_info_error' => '获取会员数值属性信息有误，请确认您的ID.',
    'get_member_value_attr_list_error' => '获取会员数值属性列表出错.',
];

return array_merge($main, $itemsService, $exceptions);
