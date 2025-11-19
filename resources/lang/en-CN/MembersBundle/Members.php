<?php

return [
    // Members.php exceptions
    'share_link_expired' => 'Share link has expired',
    'mobile_required' => 'Mobile number is required',
    'invalid_mobile' => 'Please enter a valid mobile number',
    'user_type_required' => 'User type is required',
    'password_required' => 'Please enter your password!',
    'verification_code_required' => 'Please enter verification code!',
    'account_already_bound_mobile' => 'This account is already bound to a mobile number',
    'confirm_info_correct' => 'Please confirm your information is correct',
    'sms_code_error' => 'SMS verification code error',
    'data_required' => 'Please fill in the data!',
    'enter_valid_mobile' => 'Please enter a valid mobile number',
    'mobile_can_change_once_in_30_days' => 'Mobile number can only be changed once every 30 days',
    'mobile_not_registered' => 'This mobile number is not registered',
    'image_captcha_type_error' => 'Image captcha type error',
    'mobile_error' => 'Mobile number error',
    'mobile_verification_type_error' => 'Mobile verification code type error',
    'mobile_already_registered' => 'This mobile number is already registered',
    'mobile_not_registered_yet' => 'This mobile number is not registered',
    'image_captcha_error' => 'Image captcha error',
    'query_address_detail_error' => 'Error querying address details.',
    'default_address_enable_error' => 'Error enabling default address',
    'delete_address_error' => 'Error deleting address.',
    'delete_favorite_item_error' => 'Error deleting favorite product.',
    'param_error' => 'Parameter error',
    'delete_favorite_wishlist_error' => 'Error deleting favorite wishlist.',
    'delete_favorite_shop_error' => 'Error deleting favorite shop.',
    'query_invoice_detail_error' => 'Error querying invoice details.',
    'delete_invoice_info_error' => 'Error deleting invoice information.',
    'not_logged_in' => 'Not logged in',

    // MedicationPersonnel.php exceptions
    'missing_param' => 'Missing parameter',

    // Api/V1/Action/Members.php exceptions
    'user_id_or_mobile_required' => 'User ID or mobile number is required',
    'user_not_specified' => 'User not specified',
    'please_select_user' => 'Please select a user',
    'mobile_already_exists' => 'Mobile number already exists',
    'missing_default_level' => 'Missing default level',
    'member_add_failed' => 'Member addition failed',

    // Api/V1/Action/ExportData.php exceptions
    'operator_account_error' => 'Operator account error',
    'no_content_to_operate' => 'No content to operate on',
    'coupon_issued_out' => 'Coupons have been fully issued',
    'please_fill_correct_delay_days' => 'Please fill in the correct delay days',
    'invalid_paid_member_level' => 'Invalid paid member level',

    // Api/V1/Action/MembersWhitelist.php exceptions
    'id_required' => 'ID is required',

    // Services/MemberService.php exceptions
    'user_not_exists' => 'The user to update does not exist!',
    'mobile_already_exists_service' => 'User mobile number already exists',
    'guide_not_exists' => 'Guide does not exist',
    'sms_code_error_service' => 'SMS verification code error!',
    'user_wechat_info_error' => 'User WeChat information is incorrect!',
    'account_or_password_error' => 'Account or password error',
    'unknown_error' => 'Unknown error!',
    'bind_failed_wechat_bound_to_other' => 'Binding failed! This WeChat information is already bound to another user!',
    'logout_failed' => 'Logout failed!',

    // Services/MedicationPersonnelService.php exceptions
    'cannot_add_duplicate_medication_personnel' => 'Cannot add the same medication personnel repeatedly',
    'relationship_type_error' => 'Relationship type error',
    'self_relationship_exists' => 'A medication personnel with relationship "self" already exists, please modify the relationship or medication personnel information',
    'under_6_not_supported' => 'Adding medication personnel under 6 years old is not supported',
    'medication_personnel_not_exists' => 'This medication personnel does not exist',
    'medication_personnel_info_not_exists' => 'Medication personnel information does not exist',

    // Services/UserGroupService.php exceptions
    'group_exists' => 'Group already exists',
    'group_not_exists' => 'Group does not exist',
    'condition_missing' => 'Condition missing',
    'group_name_cannot_be_empty' => 'Group name cannot be empty',
    'invalid_guide' => 'Invalid guide',

    // Services/TagsCategoryService.php exceptions
    'delete_failed_category_has_tags' => 'Delete failed, this category already has tags',
    'delete_failed' => 'Delete failed',

    // Services/MembersWhitelistService.php exceptions
    'mobile_already_used' => 'This mobile number is already used',

    // Services/MemberBrowseHistoryService.php exceptions
    'get_user_info_failed' => 'Failed to get user information',

    // Services/WechatUserService.php exceptions
    'company_id_cannot_be_empty' => 'company_id cannot be empty!',
    'miniapp_user_auth_exists' => 'Mini program already has user authorization information, cannot change binding',

    // Services/WechatFansService.php exceptions
    'unionid_cannot_be_empty' => 'unionid cannot be empty!',
    'tag_name_cannot_duplicate' => 'Tag name cannot be duplicated!',
    'tag_name_already_exists' => 'Tag name already exists, please enter again',

    // Services/MemberWhitelistUploadService.php exceptions
    'whitelist_upload_excel_only' => 'Whitelist upload only supports Excel file format',
    'mobile_whitelist_already_exists' => 'Whitelist for this mobile number already exists',

    // Services/MemberUploadUpdateService.php exceptions
    'member_info_upload_excel_only' => 'Member information upload only supports Excel file format',
    'tag_not_exists' => 'Tag does not exist',
    'tag_name_not_exists' => 'Tag {0} does not exist',
    'member_data_not_exists' => 'Member data does not exist',
    'member_level_not_exists' => 'Member level: {0} does not exist',
    'member_update_error' => 'Member update error: {0}',

    // Services/MemberUploadService.php exceptions
    'mobile_or_card_required' => 'Mobile number or original physical card number is required',
    'birthday_cannot_be_greater_than_now' => 'Birthday cannot be greater than the current import time',
    'join_date_cannot_be_greater_than_now' => 'Join date cannot be greater than the current import time',
    'card_already_member' => 'This original physical card number is already a member',
    'mobile_already_member' => 'This mobile number is already a member',
    'save_data_error' => 'Error saving data',

    // Services/MemberUploadConsumService.php exceptions
    'mobile_not_exists' => 'Mobile number does not exist',

    // Services/MemberService.php other exceptions
    'get_user_info_error' => 'Error getting user information',
    'sms_code_error_exception' => 'SMS verification code error',
    'mobile_not_registered_login' => 'Mobile number not registered, please register before logging in',
    'username_or_password_error' => 'Username or password error',

    // Services/MemberItemsFavService.php exceptions
    'max_favorite_items' => 'You can favorite up to 100 products',

    // Services/MemberDistributionFavService.php exceptions
    'shop_info_error' => 'Shop information error',
    'param_error_distribution' => 'Parameter error',
    'get_user_info_failed_distribution' => 'Failed to get user information',

    // Services/MemberAddressService.php exceptions
    'max_address_limit' => 'You can add up to 20 addresses',

    // Services/MemberRegSettingService.php exceptions
    'image_captcha_token_required' => 'Please enter image captcha token',
    'image_captcha_required' => 'Please enter image captcha',
    'captcha_sent_too_many' => 'Captcha sent too many times',
    'mobile_required_reg' => 'Please enter mobile number',
    'captcha_required' => 'Please enter captcha',

    // Services/MemberArticleFavService.php exceptions
    'param_error_article' => 'Parameter error',
    'get_user_info_failed_article' => 'Failed to get user information',

    // Common validation error messages
    'validation_error' => 'Validation error: {0}',
    'please_upload_valid_consumption' => 'Please upload valid consumption amount',
    'please_enter_valid_name' => 'Please enter valid name',
    'please_enter_valid_gender' => 'Please enter valid gender',
    'please_enter_valid_member_level' => 'Please enter valid member level',
    'please_enter_valid_join_date' => 'Please enter valid join date in MM/DD/YYYY format',
    'please_enter_valid_birthday' => 'Please enter valid birthday in MM/DD/YYYY format',
    'please_enter_valid_address' => 'Please enter valid address',
    'please_enter_valid_email' => 'Please enter valid email',
    'please_enter_valid_points' => 'Please enter valid points',
    'please_enter_valid_disabled' => 'Please enter valid disabled status',


    'point_ass'=>'Point',
];
