<?php

return [
    // Members.php异常信息
    'share_link_expired' => '分享链接已失效',
    'mobile_required' => '手机号必填',
    'invalid_mobile' => '请填写正确的手机号',
    'user_type_required' => '用户类型必填',
    'password_required' => '请填写密码!',
    'verification_code_required' => '请填写验证码!',
    'account_already_bound_mobile' => '该账号已绑定手机号',
    'confirm_info_correct' => '请确认您的信息是否正确',
    'sms_code_error' => '短信验证码错误',
    'data_required' => '请填写数据!',
    'enter_valid_mobile' => '请输入合法的手机号',
    'mobile_can_change_once_in_30_days' => '手机号每30天只可修改一次',
    'mobile_not_registered' => '该手机号还没有注册',
    'image_captcha_type_error' => '图片验证码类型错误',
    'mobile_error' => '手机号码错误',
    'mobile_verification_type_error' => '手机验证码类型错误',
    'mobile_already_registered' => '该手机号已注册',
    'mobile_not_registered_yet' => '该手机号未注册',
    'image_captcha_error' => '图片验证码错误',
    'query_address_detail_error' => '查询地址详情出错.',
    'default_address_enable_error' => '默认地址开启错误',
    'delete_address_error' => '删除地址出错.',
    'delete_favorite_item_error' => '删除收藏商品出错.',
    'param_error' => '参数错误',
    'delete_favorite_wishlist_error' => '删除收藏心愿单出错.',
    'delete_favorite_shop_error' => '删除收藏店铺出错.',
    'query_invoice_detail_error' => '查询发票详情出错.',
    'delete_invoice_info_error' => '删除发票信息出错.',
    'not_logged_in' => '未登录',

    // MedicationPersonnel.php异常信息
    'missing_param' => '缺少参数',

    // Api/V1/Action/Members.php异常信息
    'user_id_or_mobile_required' => '用户id或者手机号必填',
    'user_not_specified' => '未指定用户',
    'please_select_user' => '请选择用户',
    'mobile_already_exists' => '手机号已存在',
    'missing_default_level' => '缺少默认等级',
    'member_add_failed' => '会员添加失败',

    // Api/V1/Action/ExportData.php异常信息
    'operator_account_error' => '操作员账号有误',
    'no_content_to_operate' => '没有内容可被操作',
    'coupon_issued_out' => '优惠券已发放完',
    'please_fill_correct_delay_days' => '请填写正确的延期天数',
    'invalid_paid_member_level' => '无效的付费会员等级',

    // Api/V1/Action/MembersWhitelist.php异常信息
    'id_required' => 'id必填',

    // Services/MemberService.php异常信息
    'user_not_exists' => '更新的用户不存在！',
    'mobile_already_exists_service' => '用户手机号已经存在',
    'guide_not_exists' => '导购员不存在',
    'sms_code_error_service' => '短信验证码错误！',
    'user_wechat_info_error' => '用户的微信信息有误！',
    'account_or_password_error' => '账号或密码错误',
    'unknown_error' => '未知错误！',
    'bind_failed_wechat_bound_to_other' => '绑定失败！该微信信息已与其他用户做了绑定！',
    'logout_failed' => '注销失败！',

    // Services/MedicationPersonnelService.php异常信息
    'cannot_add_duplicate_medication_personnel' => '不能重复添加同一个用药人',
    'relationship_type_error' => '与本人关系类型错误',
    'self_relationship_exists' => '已存在关系为“本人”的用药人，请修改“与本人关系”或用药人信息',
    'under_6_not_supported' => '不支持添加6岁以下用药人',
    'medication_personnel_not_exists' => '不存在该用药人',
    'medication_personnel_info_not_exists' => '用药人信息不存在',

    // Services/UserGroupService.php异常信息
    'group_exists' => '分组已存在',
    'group_not_exists' => '分组不存在',
    'condition_missing' => '条件缺失',
    'group_name_cannot_be_empty' => '分组名不能为空',
    'invalid_guide' => '无效的导购员',

    // Services/TagsCategoryService.php异常信息
    'delete_failed_category_has_tags' => '删除失败,该分类下已有标签',
    'delete_failed' => '删除失败',

    // Services/MembersWhitelistService.php异常信息
    'mobile_already_used' => '该手机号已被使用',

    // Services/MemberBrowseHistoryService.php异常信息
    'get_user_info_failed' => '获取用户信息失败',

    // Services/WechatUserService.php异常信息
    'company_id_cannot_be_empty' => 'company_id不能为空！',
    'miniapp_user_auth_exists' => '小程序已有用户授权信息，不可更换绑定',

    // Services/WechatFansService.php异常信息
    'unionid_cannot_be_empty' => 'unionid不能为空！',
    'tag_name_cannot_duplicate' => '标签名不能重复！',
    'tag_name_already_exists' => '标签名称已存在，请重新输入',

    // Services/MemberWhitelistUploadService.php异常信息
    'whitelist_upload_excel_only' => '白名单上传只支持Excel文件格式',
    'mobile_whitelist_already_exists' => '当前手机号的白名单已经存在',

    // Services/MemberUploadUpdateService.php异常信息
    'member_info_upload_excel_only' => '会员信息上传只支持Excel文件格式',
    'tag_not_exists' => '标签不存在',
    'tag_name_not_exists' => '{0}标签不存在',
    'member_data_not_exists' => '会员数据不存在',
    'member_level_not_exists' => '会员等级：{0}  不存在',
    'member_update_error' => '会员更新错误：{0}',

    // Services/MemberUploadService.php异常信息
    'mobile_or_card_required' => '手机号和原实体卡号必填一个',
    'birthday_cannot_be_greater_than_now' => '生日不可大于当前导入时间',
    'join_date_cannot_be_greater_than_now' => '入会日期不可大于当前导入时间',
    'card_already_member' => '当前原实体卡号已经是会员',
    'mobile_already_member' => '当前手机号已经是会员',
    'save_data_error' => '保存数据错误',

    // Services/MemberUploadConsumService.php异常信息
    'mobile_not_exists' => '手机号不存在',

    // Services/MemberService.php其他异常信息
    'get_user_info_error' => '获取用户信息出错',
    'sms_code_error_exception' => '短信验证码错误',
    'mobile_not_registered_login' => '手机号码未注册，请注册后登陆',
    'username_or_password_error' => '用户名或密码错误',

    // Services/MemberItemsFavService.php异常信息
    'max_favorite_items' => '最多可以收藏100个商品',

    // Services/MemberDistributionFavService.php异常信息
    'shop_info_error' => '店铺信息有误',
    'param_error_distribution' => '参数有误',
    'get_user_info_failed_distribution' => '获取用户信息失败',

    // Services/MemberAddressService.php异常信息
    'max_address_limit' => '最多添加20个地址',

    // Services/MemberRegSettingService.php异常信息
    'image_captcha_token_required' => '请输入图片验证码token',
    'image_captcha_required' => '请输入图片验证码',
    'captcha_sent_too_many' => '验证码发送过多',
    'mobile_required_reg' => '请输入手机号',
    'captcha_required' => '请输入验证码',

    // Services/MemberArticleFavService.php异常信息
    'param_error_article' => '参数有误',
    'get_user_info_failed_article' => '获取用户信息失败',

    // 通用验证错误信息
    'validation_error' => '验证错误：{0}',
    'please_upload_valid_consumption' => '请上传有效的消费金额',
    'please_enter_valid_name' => '请填写正确的姓名',
    'please_enter_valid_gender' => '请填写正确的性别',
    'please_enter_valid_member_level' => '请填写正确的会员等级',
    'please_enter_valid_join_date' => '请填写正确的入会日期 请填写 月/日/年 格式',
    'please_enter_valid_birthday' => '请填写正确的生日日期 请填写 月/日/年 格式',
    'please_enter_valid_address' => '请填写正确的地址',
    'please_enter_valid_email' => '请填写正确的邮箱',
    'please_enter_valid_points' => '请填写正确的积分',
    'please_enter_valid_disabled' => '请填写正确的禁用',

    'point_ass'=>'积分',

    // 邮箱注册/登录（member-email-auth）
    'invalid_email' => '邮箱格式不正确',
    'email_code_purpose_invalid' => '邮箱验证码用途无效',
    'email_code_resend_too_fast' => '发送过于频繁，请稍后再试',
    'email_activation_resend_too_frequent' => '请勿频繁请求，稍后再做尝试',
    'email_send_limit_exceeded' => '该邮箱今日发送次数已达上限',
    'email_ip_limit_exceeded' => '请求过于频繁，请稍后再试',
    'email_device_limit_exceeded' => '请求过于频繁，请稍后再试',
    'company_mail_not_configured' => '店铺邮件未配置，无法发送',
    'email_send_failed' => '邮件发送失败',
    'email_code_sent' => '验证码已发送',
    'email_code_error' => '邮箱验证码错误',
    'email_register_params_missing' => '请填写邮箱与密码',
    'email_register_success_check_mail' => '注册成功，请查收邮件中的激活链接完成验证后再登录',
    'email_activate_params_missing' => '请提供激活链接中的 token',
    'email_activate_company_id_missing' => '请提供激活链接中的 company_id（与邮件 URL 中一致）',
    'email_activate_company_mismatch' => '店铺信息与激活链接不一致',
    'email_activation_token_invalid' => '激活链接无效或已过期',
    'email_activation_link_sent' => '激活邮件已发送，请查收',
    'email_activation_subject' => '验证你的邮箱',
    'email_activation_headline' => '验证你的邮箱',
    'email_activation_intro' => '成为会员只差一步，点击下面的「验证邮箱」按钮：',
    'email_activation_button' => '验证邮箱',
    // 激活邮件已改为 HTML 模板；保留 email_activation_body 以免自定义语言包引用断裂
    'email_activation_body' => '【:brand】请点击链接激活邮箱（若未注册请忽略）：:url',
    'email_activation_base_url_required' => '请配置激活页基础地址（activation_base_url）',
    'email_activation_mail_config_missing' => '邮件激活配置缺失',
    'email_activation_queue_dispatch_failed' => '激活邮件排队失败，请稍后在发送邮箱邮件接口选择 purpose=activate 重发激活链接。',
    'email_activate_success' => '邮箱已激活，请使用密码登录',
    'email_already_verified' => '该邮箱已激活',
    'email_activation_send_not_allowed' => '无法发送激活邮件，请确认邮箱已注册且尚未激活',
    'email_register_password_confirm_required' => '请填写确认密码',
    'email_register_password_mismatch' => '两次输入的密码不一致',
    'login_email_already_exists' => '该邮箱已注册',
    'email_not_verified' => '邮箱未验证，请先完成验证',
    'email_not_registered' => '该邮箱未注册',
    'password_min_length_8' => '密码至少 8 位',
    'password_need_letter_and_digit' => '密码需同时包含字母与数字',
    'password_too_weak' => '密码过于简单，请更换',
    'synthetic_mobile_failed' => '系统繁忙，请稍后重试',
    'password_reset_email_sent_if_exists' => '若邮箱已注册，您将收到重置邮件',
    'password_reset_token_invalid' => '链接无效或已过期',
    'email_vcode_body' => '您的验证码是 :code，请在有效期内使用。',
    'email_login_vcode_headline_prefix' => '登录到',
    'email_login_vcode_intro' => '您的一次性验证码是：',
    'email_login_vcode_expire_line' => '此验证码将在 :minutes 分钟后过期。',
    'email_login_vcode_footer_hint' => '如果您没有请求登录，您可以放心忽略此邮件。可能是其他人误输入了您的邮箱地址。',
    'email_password_reset_subject' => '重置密码',
    // 重置密码已改为 HTML 模板；保留 email_password_reset_body 以免自定义语言包引用断裂
    'email_password_reset_body' => '请点击链接重置密码（若未申请请忽略）：:url',
    'email_password_reset_headline' => '重置密码',
    'email_password_reset_intro' => '我们收到了重置您账户密码的请求。',
    'email_password_reset_button' => '重置密码',
    'email_password_reset_expire_line' => '此链接将在 :minutes 分钟后过期。',
    'email_password_reset_footer_hint' => '如果您没有请求重置密码，可以放心忽略此邮件。可能是其他人误输入了您的邮箱地址。',
    'email_password_reset_link_invalid' => '重置链接无效，请重新申请',
];
