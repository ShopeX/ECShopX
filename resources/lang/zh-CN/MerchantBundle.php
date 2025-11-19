<?php

// MerchantBundle 统一语言映射文件 - 中文
return [
    // 分页验证
    'current_page_positive_integer' => '当前页数为大于0的整数',
    'page_size_1_to_50_integer' => '每页数量为1-50的整数',
    'page_size_1_to_1000_integer' => '每页数量为1-1000的整数',
    
    // 商户操作员相关
    'operator_id_required' => 'operator_id必填',
    'password_6_to_16_digits' => '密码必须6-16位',
    'password_format_incorrect' => '密码格式不正确',
    'account_not_exist' => '该账号不存在',
    
    // 商户申请相关验证
    'business_scope_required' => '经营范围必填',
    'correct_business_scope_required' => '请选择正确的经营范围',
    'settlement_type_required' => '入驻类型必填',
    'correct_settlement_type_required' => '请选择正确的入驻类型',
    'merchant_name_required' => '商户名称必填',
    'social_credit_code_18_digits' => '统一社会信用代码必须是18位',
    'region_required' => '区域必填',
    'detailed_address_required' => '详细地址必填',
    'legal_name_required' => '姓名必填',
    'id_card_18_digits' => '身份证号码必须是18位',
    'mobile_number_required' => '手机号码必填是11位',
    'mobile_number_required_simple' => '手机号码必填',
    'mobile_number_11_digits' => '手机号码必须是11位',
    'bank_account_type_required' => '银行账户类型必填',
    'settlement_bank_card_required' => '结算银行卡号必填',
    'settlement_bank_required' => '结算银行必填',
    'bound_mobile_11_digits' => '绑定手机号必须是11位',
    'bound_mobile_11_digits_if' => '绑定手机号必须是11位',
    
    // 资质材料上传
    'business_license_required' => '营业执照必填',
    'id_card_front_required' => '手持身份证正面必填',
    'id_card_back_required' => '手持身份证背面必填',
    'id_card_back_required_alt' => '手持身份证反面必填',
    'bank_card_front_required' => '银行卡正面必填',
    
    // 商户申请流程
    'merchant_apply_not_exist' => '商户申请不存在',
    'current_step_cannot_edit' => '当前步骤不可编辑',
    'required_fields_not_complete' => '必填字段未完成',
    'submit_failed_try_again' => '提交失败，请重试',
    'operation_failed' => '操作失败',
    'settlement_info_step_error' => '入驻信息填写步骤错误',
    
    // 商品审核
    'audit_goods_required' => '审核商品必填',
    
    // 审核状态
    'audit_pass' => '审核通过',
    'audit_reject' => '审核拒绝',
    'pending_audit' => '待审核',
    'draft' => '草稿',
    
    // 商户类型
    'enterprise' => '企业',
    'soletrader' => '个体户',
    
    // 操作结果
    'operation_success' => '操作成功',
    'save_success' => '保存成功',
    'submit_success' => '提交成功',
    'reset_password_success' => '重置密码成功',
    
    // 商品审核
    'audit_goods_required' => '审核商品必填',
    
    // 商户设置相关验证
    'merchant_status_required' => '是否允许加盟商入驻必填',
    'pc_display_required' => '是否在pc端展示入口',
    'settlement_type_option_required' => '允许加盟商入驻类型必填',
    'agreement_content_required' => '入驻协议内容必填',
    
    // 分类管理
    'name_required_max_18_chars' => '名称必填且不能超过18个字符',
    'name_max_18_chars' => '名称不能超过18个字符',
    'sort_0_to_999999_integer' => '排序为0-999999的整数',
    'parent_id_min_0' => '父级ID必须大于等于0',
    'delete_category_error' => '删除分类出错.',
    
    // 审核相关
    'id_required' => 'ID必填',
    'audit_result_required' => '审核结果必填',
    'platform_audit_goods_required' => '是否需要平台审核商品必填',
    'audit_opinion_max_300_chars' => '审批意见为300个以内的字符',
    
    // 商户状态管理
    'disabled_status_required' => '禁用状态必填',
    'goods_audit_status_required' => '商品审核状态必填',
    'merchant_id_cannot_empty' => '商户id不能为空',
    
    // 账号生成和通知
    'account_mobile_11_digits' => '生成账号的手机号必须是11位',
    'sms_send_time_required' => '短信发送时间必填',
    
    // 证照材料
    'id_card_back_holding_required' => '手持身份证反面必填',
    
    // Repository通用错误消息
    'no_update_data_found' => '未查询到更新数据',
    
    // 商户设置服务错误消息
    'name_already_exists' => '名称已存在',
    'parent_data_error_check_and_resubmit' => '父级数据错误，请检查后重新提交',
    'can_only_add_to_second_level' => '只能添加到二级',
    'data_not_exist' => '数据不存在',
    'category_has_merchants_check_and_retry' => '该分类下有商家或有流程中的商家，请核实后再试',
    'delete_failed' => '删除失败',
    'business_scope_data_query_failed' => '经营范围数据查询失败',
    'merchant_type_data_query_failed' => '商户类型数据查询失败',
    'business_scope_error_confirm_and_resubmit' => '经营范围错误，请确认后重新提交',
    
    // 商户申请服务错误消息
    'get_login_info_error' => '获取登录信息出错!',
    'verification_code_error' => '验证码错误',
    'platform_not_support_merchant_settlement' => '该平台现不支持商户入驻，请核实后再试',
    'company_id_cannot_empty' => '企业ID不能为空',
    'mobile_cannot_empty' => '手机号不能为空',
    'please_enter_correct_mobile' => '请填写正确的手机号码',
    'sms_code_cannot_empty' => '短信验证码不能为空',
    'get_account_info_failed' => '获取账号信息失败',
    'merchant_info_get_failed' => '商户信息获取失败',
    'no_related_data_found' => '未查询到相关数据',
    'settlement_approved_cannot_modify' => '入驻申请已经审核通过，不能再修改',
    'settlement_type_error_confirm_and_resubmit' => '入驻类型错误，请确认后重新提交',
    'unified_credit_code_exists_check_and_resubmit' => '统一社会信用代码已存在，请检查后再重新提交',
    'settlement_apply_query_failed' => '入驻申请查询失败',
    'settlement_apply_no_need_audit' => '入驻申请当前无需审核',
    'settlement_apply_audit_failed_check_and_retry' => '入驻申请审核失败,请核实后再试',
    
    // 商户服务错误消息
    'merchant_data_query_failed' => '商户数据查询失败',
    'mobile_format_incorrect_confirm_and_retry' => '手机号格式不正确，请确认后再重试',
    'bank_mobile_format_incorrect_confirm_and_retry' => '银行预留手机号格式不正确，请确认后再重试',
    'account_mobile_format_incorrect_confirm_and_retry' => '生成账号的手机号格式不正确，请确认后再重试',
    'account_mobile_exists_confirm_and_retry' => '账号的手机号已经存在，请确认后再重试',
]; 