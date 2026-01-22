<?php

return [
    // 表单模板相关
    'please_select_form_template' => '请选择表单模板',
    'only_get_statistics_within_10_days' => '只能获取10天以内的统计数据',
    
    // 报名活动相关
    'activity_not_exist' => '活动不存在',
    'parameter_error' => '参数错误：:status',
    'only_members_can_register' => '只有会员才可以参与报名',
    'please_specify_registration_activity' => '请指定报名活动',
    'activity_too_popular_try_later' => '活动太火爆了，请稍后再试',
    'activity_too_popular_try_later_short' => '活动太火爆，请稍后再试',
    'registration_data_cannot_empty' => '报名数据不能为空',
    'field_required' => ':field_title必填',
    'field_required_with_card' => ':card_title下的:field_title必填',
    'please_enter_correct_mobile' => '请填写正确的手机号',
    'activity_registration_not_allow_modify' => '活动报名不允许修改',
    'please_fill_registration_form' => '请填写报名表单',
    
    // 报名记录相关
    'rejection_reason_required' => '拒绝原因必填',
    'operation_too_frequent_try_later' => '操作太频繁，请稍后再试',
    'current_status_no_need_review' => '当前状态不需要审核',
    'registration_record_not_exist' => '报名记录不存在',
    'registration_record_cannot_verify' => '报名记录不可核销',
    'verification_code_error' => '核销码错误',
    
    // 取消相关
    'please_login_before_cancel' => '请登录后再取消',
    'please_specify_registration_record_id' => '请指定报名记录ID',
    'registration_status_cannot_cancel' => '报名状态不能取消',
    'activity_not_allow_cancel' => '活动不允许取消',
    
    // 通用错误
    'information_error' => '信息有误',
    
    // 表单验证相关
    'field_title_required' => 'field_title必填',
    'field_name_required' => 'field_name必填',
    'form_element_required' => 'form_element必填',
    'tag_id_required' => 'tagId不能为空',
    'tag_name_required' => '标签名称不能为空',
    'tem_name_required' => 'tem_name必填',
    'content_required' => 'content必填',
    'tem_type_required' => 'tem_type必填',
    'activity_id_required' => '活动id必选',
    'activity_name_required' => '活动名称必填',
    'start_time_required' => '开始时间必填',
    'end_time_required' => '结束时间必填',
    'template_required' => '模板必选',
    
    // 活动状态
    'status_waiting' => '待开始',
    'status_ended' => '已结束',
    'status_ongoing' => '进行中',
    
    // 报名记录相关验证
    'registration_record_id_required' => '报名记录id',
    'registration_record_id_cannot_empty' => '报名记录id不能为空',
    'approval_result_required' => '审批结果必填',
    'verification_code_required' => '核销码必填',
    
    // 导出相关错误
    'please_select_activity_to_export' => '请选择一个活动导出',
    'export_error_no_data' => '导出有误,暂无数据导出',
    'export_error_max_15000_records' => '导出有误，最高导出15000条数据',
    
    // Repository通用错误消息
    'no_update_data_found' => '未查询到更新数据',
    'data_not_exist' => '数据不存在',
    'delete_data_not_exist' => '删除的数据不存在',
    
    // 报名审核服务
    'activity_registration_audit_only_excel' => '活动报名审批上传只支持Excel文件格式',
    'please_enter_correct_mobile_number' => '请填写正确的手机号',
    'please_enter_correct_registration_number' => '请填写正确的报名编号',
    'save_data_error' => '保存数据错误',
    
    // 报名记录导出Excel字段名
    'export_registration_number' => '报名申请编号',
    'export_member_mobile' => '会员手机号码',
    'export_audit_result' => '审核结果',
    'export_rejection_reason' => '拒绝原因',
    
    // Excel字段说明
    'mobile_excel_remarks' => '手机号如果大于11位时，请关闭excel单元格的科学记数法，常用禁用方法："单元格格式"-"自定义"-"类型"改为"0"',
    'audit_result_excel_remarks' => '1：报名通过，0或者不填：报名被拒绝',
    
    // 其他服务错误消息
    'data_error' => '数据有误',
    'field_required_simple' => ':field_title必填',
    'field_must_be_number' => ':field_title必须是数字',
    'activity_group_code_error' => '活动分组编码错误：:group_no',
    
    // checkActivityValid方法错误消息
    'activity_not_exist_err' => '活动不存在',
    'activity_not_started_or_ended' => '活动未开始或已结束',
    'only_specific_members_allowed' => '仅限特定会员参加',
    'only_specific_stores_allowed' => '仅限特定店铺参加',
    'activity_quota_full' => '活动名额已满，下次请早哦',
    'can_only_modify_own_registration' => '只能修改自己的报名信息',
    'current_registration_status_cannot_modify' => '当前报名状态不能修改',
    'cannot_register_duplicate' => '不能重复报名',
    
    // 报名记录状态名称
    'status_pending' => '待审核',
    'status_passed' => '已通过',
    'status_rejected' => '已拒绝',
    'status_verified' => '已核销',
    'status_canceled' => '已取消',
]; 