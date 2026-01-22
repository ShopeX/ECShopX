<?php

return [
    // Form template related
    'please_select_form_template' => 'Please select a form template',
    'only_get_statistics_within_10_days' => 'Can only get statistics within 10 days',
    
    // Registration activity related
    'activity_not_exist' => 'Activity does not exist',
    'parameter_error' => 'Parameter error: :status',
    'only_members_can_register' => 'Only members can participate in registration',
    'please_specify_registration_activity' => 'Please specify registration activity',
    'activity_too_popular_try_later' => 'Activity is too popular, please try again later',
    'activity_too_popular_try_later_short' => 'Activity is too popular, please try again later',
    'registration_data_cannot_empty' => 'Registration data cannot be empty',
    'field_required' => ':field_title is required',
    'field_required_with_card' => ':field_title under :card_title is required',
    'please_enter_correct_mobile' => 'Please enter a valid mobile number',
    'activity_registration_not_allow_modify' => 'Activity registration cannot be modified',
    'please_fill_registration_form' => 'Please fill in the registration form',
    
    // Registration record related
    'rejection_reason_required' => 'Rejection reason is required',
    'operation_too_frequent_try_later' => 'Operation too frequent, please try again later',
    'current_status_no_need_review' => 'Current status does not need review',
    'registration_record_not_exist' => 'Registration record does not exist',
    'registration_record_cannot_verify' => 'Registration record cannot be verified',
    'verification_code_error' => 'Verification code error',
    
    // Cancellation related
    'please_login_before_cancel' => 'Please login before cancellation',
    'please_specify_registration_record_id' => 'Please specify registration record ID',
    'registration_status_cannot_cancel' => 'Registration status cannot be cancelled',
    'activity_not_allow_cancel' => 'Activity does not allow cancellation',
    
    // General errors
    'information_error' => 'Information error',
    
    // Form validation related
    'field_title_required' => 'Field title is required',
    'field_name_required' => 'Field name is required',
    'form_element_required' => 'Form element is required',
    'tag_id_required' => 'Tag ID cannot be empty',
    'tag_name_required' => 'Tag name cannot be empty',
    'tem_name_required' => 'Template name is required',
    'content_required' => 'Content is required',
    'tem_type_required' => 'Template type is required',
    'activity_id_required' => 'Activity ID is required',
    'activity_name_required' => 'Activity name is required',
    'start_time_required' => 'Start time is required',
    'end_time_required' => 'End time is required',
    'template_required' => 'Template is required',
    
    // Activity status
    'status_waiting' => 'Waiting to start',
    'status_ended' => 'Ended',
    'status_ongoing' => 'Ongoing',
    
    // Registration record related validation
    'registration_record_id_required' => 'Registration record ID is required',
    'registration_record_id_cannot_empty' => 'Registration record ID cannot be empty',
    'approval_result_required' => 'Approval result is required',
    'verification_code_required' => 'Verification code is required',
    
    // Export related errors
    'please_select_activity_to_export' => 'Please select an activity to export',
    'export_error_no_data' => 'Export error, no data to export',
    'export_error_max_15000_records' => 'Export error, maximum 15000 records can be exported',
    
    // Repository common error messages
    'no_update_data_found' => 'No update data found',
    'data_not_exist' => 'Data does not exist',
    'delete_data_not_exist' => 'Delete data does not exist',
    
    // Registration audit service
    'activity_registration_audit_only_excel' => 'Activity registration audit upload only supports Excel file format',
    'please_enter_correct_mobile_number' => 'Please enter correct mobile number',
    'please_enter_correct_registration_number' => 'Please enter correct registration number',
    'save_data_error' => 'Save data error',
    
    // Registration record export Excel field names
    'export_registration_number' => 'Registration Number',
    'export_member_mobile' => 'Member Mobile Number',
    'export_audit_result' => 'Audit Result',
    'export_rejection_reason' => 'Rejection Reason',
    
    // Excel field remarks
    'mobile_excel_remarks' => 'If mobile number is longer than 11 digits, please disable scientific notation in Excel cells. Common method: "Cell Format" - "Custom" - "Type" change to "0"',
    'audit_result_excel_remarks' => '1: Registration approved, 0 or empty: Registration rejected',
    
    // Other service error messages
    'data_error' => 'Data error',
    'field_required_simple' => ':field_title is required',
    'field_must_be_number' => ':field_title must be a number',
    'activity_group_code_error' => 'Activity group code error: :group_no',
    
    // checkActivityValid method error messages
    'activity_not_exist_err' => 'Activity does not exist',
    'activity_not_started_or_ended' => 'Activity has not started or has ended',
    'only_specific_members_allowed' => 'Only specific members are allowed to participate',
    'only_specific_stores_allowed' => 'Only specific stores are allowed to participate',
    'activity_quota_full' => 'Activity quota is full, please come early next time',
    'can_only_modify_own_registration' => 'Can only modify your own registration information',
    'current_registration_status_cannot_modify' => 'Current registration status cannot be modified',
    'cannot_register_duplicate' => 'Cannot register duplicate',
    
    // Registration record status names
    'status_pending' => 'Pending Review',
    'status_passed' => 'Approved',
    'status_rejected' => 'Rejected',
    'status_verified' => 'Verified',
    'status_canceled' => 'Canceled',
]; 