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
];
