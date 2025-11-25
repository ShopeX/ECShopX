<?php
// 通用配置文件

return [
    'test_mode' => env('TEST_MODE', false), // 测试模式，用于压测，自动化等场景
    'check_superadmin_permission' => env('CHECK_SUPERADMIN_PERMISSION', false), // 超级管理员是否检查后台菜单权限
    'system_is_saas' => env('SYSTEM_IS_SAAS', false), // 是否是saas
    'system_open_online' => env('SYSTEM_OPEN_ONLINE', false), // 在线开通
    'system_companys_id' => env('SYSTEM_COMPANYS_ID', 1), // 指定默认company_id,独立部署,不可修改
    'qqmap_key' => env('QQMAP_KEY'), // 腾讯地图key
    'h5_base_url' => env('H5_BASE_URL'), // H5基础域名
    'h5_domain_suffix' => env('H5_DOMAIN_SUFFIX', '.yuanyuanke.cn'),//H5域名后缀
    'pc_domain_suffix' => env('PC_DOMAIN_SUFFIX', '.yuanyuanke.cn'),//PC域名后缀
    'del_operator_logs_date' => env('DEL_OPERATOR_LOGS_DATE'), // 日志删除时间，默认保存三个月
    # prism
    'openapi_shopex_url' => env ('OPENAPI_SHOPEX_URL', 'https://openapi.shopex.cn'), // prism配置，默认不用动
    'prism_url' => env('PRISM_URL', 'https://openapi.shopex.cn/api'), // prism配置，默认不用动
    'prism_key' => env('PRISM_KEY'), // prism配置
    'prism_secret' => env('PRISM_SECRET'), // prism配置
    'ishopex_prism_url' => env('ISHOPEX_PRISM_URL'),
    'ishopex_prism_key' => env('ISHOPEX_PRISM_KEY'),
    'ishopex_prism_secret' => env('ISHOPEX_PRISM_SECRET'),
    'system_main_companys_id' => env('SYSTEM_MAIN_COMPANYS_ID'),
    'tips_ws_key' => env('TIPS_WS_KEY'),
    'use_system_menu' => env('USE_SYSTEM_MENU'), // 是否使用系统菜单,false则是固定菜单，true则更新storage/static/目录下的菜单
    'product_model' => env('PRODUCT_MODEL', 'platform'), // 系统版本，standard|platform
    'api_token' => env('API_TOKEN'),
    'jwt_secret' => env('JWT_SECRET'), // jwt密钥
    'tips_ws_uri' => env('TIPS_WS_URI'),
    'wechat_payment_notify' => env('WECHAT_PAYMENT_NOTIFY', env('APP_URL').'/wechatAuth/wxpay/notify'), // 微信支付通知回调地址。示例：https://后台域名/wechatAuth/wxpay/notify
    'brokerage_uri_item' => env('BROKERAGE_URI_ITEM'),
    'brokerage_uri' => env('BROKERAGE_URI'),
    'system_sms_entid' => env('SYSTEM_SMS_ENTID', ''),
    'system_sms_entpwd' => env('SYSTEM_SMS_ENTPWD', ''),
    'erp_gy_token' => env('ERP_GY_TOKEN'), // 直连oms配置
    'oms_api_url' => env('OMS_API_URL'),
    'oms_token' => env('OMS_TOKEN'),
    'oms_white_ip' => env('OMS_WHITE_IP', ''),//直连oms的白名单，多个ip用英文分号隔开；留空为不限制
    'fapiao_hangxin_api_url' => env('FAPIAO_HANGXIN_API_URL'), // 航信发票接口地址
    'fapiao_hangxin_key' => env('FAPIAO_HANGXIN_KEY'),
    'verify_app_id' => env('VERIFY_APP_ID', 'ecos.ecshopx'), // 对应矩阵appid
    'certi_base_url' => env('CERTI_BASE_URL', env('APP_URL')), # 对接saas ERP的配置信息 URL
    'store_key' => env('STORE_KEY'), # 对接saas ERP的配置信息 KEY
    'matrix_realtion_url' => env('MATRIX_REALTION_URL', 'https://iframe.shopex.cn/?'), // 矩阵关系绑定API
    'matrix_api_url' => env('MATRIX_API_URL', 'http://matrix.ecos.shopex.cn/sync'), // 矩阵API
    'pc_wxcode_login' => env('PC_WXCODE_LOGIN', 120), // pc端扫码登录码有效期，单位秒
    'address_template_id' => env('ADDRESS_TEMPLATE_ID'),
    'transfer_mode' => env('TRANSFER_MODE', false), // 是否开启迁移模式，用于换开放平台刷新unionid
    #神州数码OMS
    'owner_id' => env('OWNER_ID'), // 电商编码
    'code_name' => env('CODE_NAME'), // OMS 系统在电商系统中的代号名称
    'system_id' => env('SYSTEM_ID'), // OMS 系统在电商系统中的代号，双方提前约定；String 类型
    'stock_id' => env('STOCK_ID'), // 仓库ID
    'oms_secret_key' => env('OMS_SECRET_KEY'), // key
    'platform_id' => env('PLATFORM_ID'), // 电商平台在第三方 OMS 系统中的 ID
    'request_url' => env('REQUEST_URL'), // 神州数码oms请求地址
    'company_name' => env('COMPANY_NAME'),
    'distributor_distance' => env('DISTRIBUTOR_DISTANCE', 5), // 默认查询店铺距离
    'openapi_gy_token' => env('OPENAPI_GY_TOKEN'), // 直连开放平台配置
    'email_smtp_port' => env('EMAIL_SMTP_PORT'),
    'email_relay_host' => env('EMAIL_RELAY_HOST'),
    'email_sender' => env('EMAIL_SENDER'),
    'email_user' => env('EMAIL_USER'),
    'email_password' => env('EMAIL_PASSWORD'),
    'email_encryption' => env('MAIL_ENCRYPTION'),
    'email_from_name' => env('MAIL_FROM_NAME'),
    'oms_openapi_url' => env('OMS_OPENAPI_URL'),
    'hfpay_notify_url' => env('HFPAY_NOTIFY_URL', env('APP_URL').'/api/third/hfpay/notify'), // 异步推送地址, 示例:https://域名/api/third/hfpay/notify
    'hfpay_is_open' => env('HFPAY_IS_OPEN', false), // (值为true、false)true启用汇付支付渠道其他支付渠道则隐藏
    'distribution_default_banner' => env('DISTRIBUTION_DEFAULT_BANNER','https://b-img-cdn.yuanyuanke.cn/image/21/2021/03/18/848fc4e902a165d46681c7224874aa38YCunwmbUpafj5WcXH7AnJJbItw4zmWyh'),
    'distribution_default_weapp' => env('DISTRIBUTION_DEFAULT_WEAPP','https://b-img-cdn.yuanyuanke.cn/image/21/2021/03/18/848fc4e902a165d46681c7224874aa38Ek2HvZa3JuB3FOa9KATKzLqKF34u00Yc'),
    'distribution_default_poster' => env('DISTRIBUTION_DEFAULT_POSTER','https://b-img-cdn.yuanyuanke.cn/image/21/2021/03/18/81b0d48a6a0a8559508df99006385519a8eK90ChQZFZ1zNzPtXhPTHGGIm9i8UT'),
    'qrcode_bg_img' => env('QRCODE_BG_IMG','https://b-img-cdn.yuanyuanke.cn/image/21/2021/10/21/9c8cbb5f6b6a346641fe151b5e1604118H6zDVOajmkASVYKgPePOYXIeFpc55Ta'),
    'promoter_qrcode_bg_img' => env('PROMOTER_QRCODE_BG_IMG','https://b-img-cdn.yuanyuanke.cn/image/21/2021/10/21/9c8cbb5f6b6a346641fe151b5e1604118H6zDVOajmkASVYKgPePOYXIeFpc55Ta'),
    'marketing_center_baseuri' => env('MARKETING_CENTER_BASEURI'), //
    'marketing_center_token' => env('MARKETING_CENTER_TOKEN'), //
    'marketing_center_appkey' => env('MARKETING_CENTER_APPKEY'), //
    'external_baseuri' => env('EXTERNAL_BASEURI', 'https://ba.shopex.cn'), //
    'dada_app_key' => env('DADA_APP_KEY'),// 达达同城配送app_key
    'dada_app_secret' => env('DADA_APP_SECRET'),// 达达同城配送app_secret
    'dada_is_online' => env('DADA_IS_ONLINE', false),// 达达同城配送是否为线上
    'api_base_url' => env('API_BASE_URL', env('APP_URL').'/api/'),// 达达同城配API地址
    'rand_salt' => env('RAND_SALT'),
    // 定位服务
    "map" => [
        // 腾讯地图
        "tencent" => [
            'baseuri'    => env('API_TENCENT_POSITION_BASEURI', 'https://apis.map.qq.com'),
            'app_key'    => env('API_TENCENT_POSITION_APP_KEY', ''),
            'app_secret' => env('API_TENCENT_POSITION_APP_SECRET', ''),
        ],
        // 高德地图
        "amap"    => [
            'baseuri'    => env('API_AMAP_POSITION_BASEURI', 'https://restapi.amap.com'),
            'app_key'    => env('API_AMAP_POSITION_APP_KEY'),
            'app_secret' => env('API_AMAP_POSITION_APP_SECRET', ''),
            // 高德地图的猎鹰轨迹
            "track" => [
                "baseuri" => env("API_AMAP_TRACK_BASEURI", "https://tsapi.amap.com")
            ],
        ],
    ],
    'amap_lbs_key' => env('AMAP_LBS_KEY'),// 高德地图key
    'dis_workwechat_h5_baseuri' => env('DIS_WORKWECHAT_H5_BASEURI', ''),
    'dis_workwechat_h5_authuri' => env('DIS_WORKWECHAT_H5_AUTHURI', ''),

    'encrypt_sensitive_data' => env('ENCRYPT_SENSITIVE_DATA', false),

    'sms_send_limit' => env("SMS_SEND_LIMIT", 5), // 一种短信验证码在一天里的发送上限

    'shop_admin_url' => env('SHOP_ADMIN_URL', ''),// 管理后台地址,结尾带斜杠

    'employee_purchanse_sharecode_expire' => env('EMPLOYEE_PURCHANSE_SHARECODE_EXPIRE', 86400),// 员工内购分享cocd过期时间（秒数）
    'employee_purchanse_buy_inactive' => env('EMPLOYEE_PURCHANSE_BUY_INACTIVE', true), // 没有开启的内购活动不能下单
    'employee_purchanse_dependents_hour' => env('EMPLOYEE_PURCHANSE_DEPENDENTS_HOUR', 24),// 员工内购亲友开始时间晚于员工时间（小时）
    'demo_company_id' => env('DEMO_COMPANY_ID', ''),
    'chinaums_payment_notify' => env('CHINAUMS_PAYMENT_NOTIFY'),
    'local_delivery_dirver' => env('LOCAL_DELIVERY_DIRVER', 'dada'),
    'shansong_app_key' => env('SHANSONG_APP_KEY'),
    'shansong_app_secret' => env('SHANSONG_APP_SECRET'),
    'shansong_is_online' => env('SHANSONG_IS_ONLINE'),
    'supplier_order_split' => env('SUPPLIER_ORDER_SPLIT', true),//供应商订单是否拆分
    'shuyun_app_key' => env('SHUYUN_APP_KEY', ''),// 数云app_key
    'shuyun_app_secret' => env('SHUYUN_APP_SECRET', ''),// 数云app_secret
    'shuyun_is_online' => env('SHUYUN_IS_ONLINE', false),// 数云是否为线上
    'wangdian_api_url' => env('WANGDIAN_API_URL', ''), // 旺店通接口地址
    'oem-shuyun' => env('OEM_SHUYUN', false),// 是否为oem数云
];
