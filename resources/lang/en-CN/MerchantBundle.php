<?php

// MerchantBundle 统一语言映射文件 - 英文
return [
    // Pagination validation
    'current_page_positive_integer' => 'Current page number must be a positive integer',
    'page_size_1_to_50_integer' => 'Page size must be an integer between 1-50',
    'page_size_1_to_1000_integer' => 'Page size must be an integer between 1-1000',
    
    // Merchant operator related
    'operator_id_required' => 'Operator ID is required',
    'password_6_to_16_digits' => 'Password must be 6-16 characters',
    'password_format_incorrect' => 'Password format is incorrect',
    'account_not_exist' => 'This account does not exist',
    
    // Merchant application validation
    'business_scope_required' => 'Business scope is required',
    'correct_business_scope_required' => 'Please select the correct business scope',
    'settlement_type_required' => 'Settlement type is required',
    'correct_settlement_type_required' => 'Please select the correct settlement type',
    'merchant_name_required' => 'Merchant name is required',
    'social_credit_code_18_digits' => 'Unified social credit code must be 18 digits',
    'region_required' => 'Region is required',
    'detailed_address_required' => 'Detailed address is required',
    'legal_name_required' => 'Legal name is required',
    'id_card_18_digits' => 'ID card number must be 18 digits',
    'mobile_number_required' => 'Mobile number is required and must be 11 digits',
    'mobile_number_required_simple' => 'Mobile number is required',
    'mobile_number_11_digits' => 'Mobile number must be 11 digits',
    'bank_account_type_required' => 'Bank account type is required',
    'settlement_bank_card_required' => 'Settlement bank card number is required',
    'settlement_bank_required' => 'Settlement bank is required',
    'bound_mobile_11_digits' => 'Bound mobile number must be 11 digits',
    
    // Qualification materials upload
    'business_license_required' => 'Business license is required',
    'id_card_front_required' => 'ID card front with person holding is required',
    'id_card_back_required' => 'ID card back with person holding is required',
    'bank_card_front_required' => 'Bank card front is required',
    
    // Merchant application process
    'merchant_apply_not_exist' => 'Merchant application does not exist',
    'current_step_cannot_edit' => 'Current step cannot be edited',
    'required_fields_not_complete' => 'Required fields are not complete',
    'submit_failed_try_again' => 'Submission failed, please try again',
    'operation_failed' => 'Operation failed',
    'settlement_info_step_error' => 'Settlement information step error',
    
    // Audit status
    'audit_pass' => 'Audit passed',
    'audit_reject' => 'Audit rejected',
    'pending_audit' => 'Pending audit',
    'draft' => 'Draft',
    
    // Merchant type
    'enterprise' => 'Enterprise',
    'soletrader' => 'Sole trader',
    
    // Operation results
    'operation_success' => 'Operation successful',
    'save_success' => 'Save successful',
    'submit_success' => 'Submit successful',
    'reset_password_success' => 'Reset password successful',
    
    // Additional validations
    'mobile_number_required_simple' => 'Mobile number is required',
    'mobile_number_11_digits' => 'Mobile number must be 11 digits',
    'id_card_back_required_alt' => 'ID card back with person holding is required',
    'audit_goods_required' => 'Audit goods is required',
    
    // Merchant settings validation
    'merchant_status_required' => 'Whether to allow merchant settlement is required',
    'pc_display_required' => 'Whether to display entrance on PC',
    'settlement_type_option_required' => 'Allowed merchant settlement types are required',
    'agreement_content_required' => 'Settlement agreement content is required',
    
    // Category management
    'name_required_max_18_chars' => 'Name is required and cannot exceed 18 characters',
    'name_max_18_chars' => 'Name cannot exceed 18 characters',
    'sort_0_to_999999_integer' => 'Sort must be an integer from 0-999999',
    'parent_id_min_0' => 'Parent ID must be greater than or equal to 0',
    'delete_category_error' => 'Error deleting category.',
    
    // Audit related
    'id_required' => 'ID is required',
    'audit_result_required' => 'Audit result is required',
    'platform_audit_goods_required' => 'Whether platform audit goods is required',
    'audit_opinion_max_300_chars' => 'Audit opinion must be within 300 characters',
    
    // Merchant status management
    'disabled_status_required' => 'Disabled status is required',
    'goods_audit_status_required' => 'Goods audit status is required',
    'merchant_id_cannot_empty' => 'Merchant ID cannot be empty',
    
    // Account generation and notification
    'account_mobile_11_digits' => 'Account mobile number must be 11 digits',
    'sms_send_time_required' => 'SMS send time is required',
    
    // Certification materials
    'id_card_back_holding_required' => 'ID card back with person holding is required',
    
    // Repository common error messages
    'no_update_data_found' => 'No update data found',
    
    // Merchant setting service error messages
    'name_already_exists' => 'Name already exists',
    'parent_data_error_check_and_resubmit' => 'Parent data error, please check and resubmit',
    'can_only_add_to_second_level' => 'Can only add to second level',
    'data_not_exist' => 'Data does not exist',
    'category_has_merchants_check_and_retry' => 'This category has merchants or merchants in process, please check and retry',
    'delete_failed' => 'Delete failed',
    'business_scope_data_query_failed' => 'Business scope data query failed',
    'merchant_type_data_query_failed' => 'Merchant type data query failed',
    'business_scope_error_confirm_and_resubmit' => 'Business scope error, please confirm and resubmit',
    
    // Merchant application service error messages
    'get_login_info_error' => 'Error getting login information!',
    'verification_code_error' => 'Verification code error',
    'platform_not_support_merchant_settlement' => 'This platform does not support merchant settlement, please check and retry',
    'company_id_cannot_empty' => 'Company ID cannot be empty',
    'mobile_cannot_empty' => 'Mobile number cannot be empty',
    'please_enter_correct_mobile' => 'Please enter correct mobile number',
    'sms_code_cannot_empty' => 'SMS verification code cannot be empty',
    'get_account_info_failed' => 'Get account information failed',
    'merchant_info_get_failed' => 'Merchant information get failed',
    'no_related_data_found' => 'No related data found',
    'settlement_approved_cannot_modify' => 'Settlement application has been approved and cannot be modified',
    'settlement_type_error_confirm_and_resubmit' => 'Settlement type error, please confirm and resubmit',
    'unified_credit_code_exists_check_and_resubmit' => 'Unified credit code already exists, please check and resubmit',
    'settlement_apply_query_failed' => 'Settlement application query failed',
    'settlement_apply_no_need_audit' => 'Settlement application currently does not need audit',
    'settlement_apply_audit_failed_check_and_retry' => 'Settlement application audit failed, please check and retry',
    
    // Merchant service error messages
    'merchant_data_query_failed' => 'Merchant data query failed',
    'mobile_format_incorrect_confirm_and_retry' => 'Mobile format is incorrect, please confirm and retry',
    'bank_mobile_format_incorrect_confirm_and_retry' => 'Bank mobile format is incorrect, please confirm and retry',
    'account_mobile_format_incorrect_confirm_and_retry' => 'Account mobile format is incorrect, please confirm and retry',
    'account_mobile_exists_confirm_and_retry' => 'Account mobile already exists, please confirm and retry',
]; 