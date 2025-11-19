<?php

use \EspierBundle\Services\Config\ConfigRequestFieldsService as Service;

return [
    // 用户注册模块
    Service::MODULE_TYPE_MEMBER_INFO => [
        // 必须开启且必填的字段
        "must_start_required" => env("REQUEST_FIELD_MEMBER_INFO_MUST_START_REQUIRED", "username,mobile"),
        // 默认显示的内容，must_start_required的字段必须要存在default中，否则业务逻辑的流程会有问题
        "default" => [
            "mobile"         => [
                "name"         => "手机号",
                "is_open"      => true,
                "element_type" => "mobile",
                "is_required"  => true,
                "prompt"       => "请填写手机号"
            ],
            "username"       => [
                "name"         => "昵称",
                "is_open"      => true,
                "element_type" => "input",
                "is_required"  => true,
                "prompt"       => "请填写您的昵称"
            ],
//            "avatar" => [
//                "name" => env("REQUEST_FIELD_MEMBER_INFO_LABEL_AVATAR", "头像"),
//                "is_open" => true,
//                "element_type" => "input",
//                "is_required" => false
//            ],
            "sex"            => [
                "name"         => "性别",
                "is_open"      => true,
                "element_type" => "select",
                "is_required"  => false,
                "prompt"       => "请填写您的性别",
                "items"        => [
                    0 => "未知",
                    1 => "男",
                    2 => "女",
                ]
            ],
            "birthday"       => [
                "name"         => "生日",
                "is_open"      => true,
                "element_type" => "select",
                "is_required"  => false,
                "prompt"       => "请填写您的生日",
            ],
            "address"        => [
                "name"         => "家庭地址",
                "is_open"      => true,
                "element_type" => "input",
                "is_required"  => false,
                "prompt"       => "请输入您的家庭地址",
            ],
            "email"          => [
                "name"         => "email",
                "is_open"      => true,
                "element_type" => "input",
                "is_required"  => false,
                "prompt"       => "请输入您的电子邮件",
            ],
            "industry"       => [
                "name"         => "行业",
                "is_open"      => true,
                "element_type" => "select",
                "is_required"  => false,
                "prompt"       => "请选择您的工作行业",
                "items"        => [
                    0  => "金融/银行/投资",
                    1  => "计算机/互联网",
                    2  => "媒体/出版/影视/文化",
                    3  => "政府/公共事业",
                    4  => "房地产/建材/工程",
                    5  => "咨询/法律",
                    6  => "加工制造",
                    7  => "教育培训",
                    8  => "医疗保健",
                    9  => "运输/物流/交通",
                    10 => "零售/贸易",
                    11 => "旅游/度假",
                    12 => "其他",
                ],
            ],
            "income"         => [
                "name"         => "年收入",
                "is_open"      => true,
                "element_type" => "select",
                "is_required"  => false,
                "prompt"       => "请选择您的年收入区间",
                "items"        => [
                    0 => "5万以下",
                    1 => "5万 ~ 15万",
                    2 => "15万 ~ 30万",
                    3 => "30万以上",
                    4 => "其他",
                ],
            ],
            "edu_background" => [
                "name"         => "学历",
                "is_open"      => true,
                "element_type" => "select",
                "is_required"  => false,
                "prompt"       => "请选择您的学历",
                "items"        => [
                    0 => "硕士及以上",
                    1 => "本科",
                    2 => "大专",
                    3 => "高中/中专及以下",
                    4 => "其他",
                ],
            ],
            "habbit"         => [
                "name"         => "爱好",
                "is_open"      => true,
                "element_type" => "checkbox",
                "is_required"  => false,
                "prompt"       => "请选择您的爱好",
                "items"        => [
                    0  => [
                        "name"      => "游戏",
                        "ischecked" => true,
                    ],
                    1  => [
                        "name"      => "阅读",
                        "ischecked" => true,
                    ],
                    2  => [
                        "name"      => "音乐",
                        "ischecked" => true,
                    ],
                    3  => [
                        "name"      => "运动",
                        "ischecked" => true,
                    ],
                    4  => [
                        "name"      => "动漫",
                        "ischecked" => true,
                    ],
                    5  => [
                        "name"      => "旅游",
                        "ischecked" => true,
                    ],
                    6  => [
                        "name"      => "家居",
                        "ischecked" => true,
                    ],
                    7  => [
                        "name"      => "曲艺",
                        "ischecked" => true,
                    ],
                    8  => [
                        "name"      => "宠物",
                        "ischecked" => true,
                    ],
                    9  => [
                        "name"      => "美食",
                        "ischecked" => true,
                    ],
                    10 => [
                        "name"      => "娱乐",
                        "ischecked" => true,
                    ],
                    11 => [
                        "name"      => "电影/电视",
                        "ischecked" => true,
                    ],
                    12 => [
                        "name"      => "健康养生",
                        "ischecked" => true,
                    ],
                    13 => [
                        "name"      => "数码",
                        "ischecked" => true
                    ],
                    14 => [
                        "name"      => "其他",
                        "ischecked" => true,
                    ]
                ]
            ],
        ],
    ],
    // 用户注册模块
    Service::MODULE_TYPE_CHIEF_INFO => [
        // 必须开启且必填的字段
        "must_start_required" => env("REQUEST_FIELD_CHIEF_INFO_MUST_START_REQUIRED", "chief_name"),
        // 默认显示的内容，must_start_required的字段必须要存在default中，否则业务逻辑的流程会有问题
        "default" => [
            "chief_name"       => [
                "name"         => "姓名",
                "is_open"      => true,
                "element_type" => "input",
                "is_required"  => true,
                "prompt"       => "请输入姓名",
            ],
        ],
    ],
];
