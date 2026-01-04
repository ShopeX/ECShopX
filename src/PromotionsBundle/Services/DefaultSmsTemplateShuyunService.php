<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace PromotionsBundle\Services;

class DefaultSmsTemplateShuyunService
{
    public $defaultTemplate = [
        // 'verification_code' => [
        //     'content' => '验证码：{{验证码}}，30分钟内有效，如非本人操作请忽略！',
        //     'tmpl_type' => 'vcode',
        //     'sms_type' => 'notice',
        //     'send_time_desc' => [
        //         'tmpl_title' => '短信验证码',
        //         'title' => '获取验证码后触发'
        //     ],
        //     'tmpl_name' => 'verification_code',
        //     'is_open' => true,
        // ],
        'trade_pay_success' => [
            'content' => '您于{{支付时间}}成功支付{{支付金额}}元。',
            'tmpl_type' => 'trade',
            'sms_type' => 'notice',
            'send_time_desc' => [
                'tmpl_title' => '支付成功通知',
                'title' => '支付完成后触发',
                'variables' => ['支付时间' => 19, '支付金额' => 8],
            ],
            'tmpl_name' => 'trade_pay_success',
            'is_open' => false,
        ],
        // 'order_pickup' => [
        //     'content'   => '您{{订单号}}的订单提货码是{{提货码}}，30分钟内有效，请勿泄漏。',
        //     'tmpl_type' => 'trade',
        //     'sms_type'  => 'notice',
        //     'send_time_desc' => [
        //         'tmpl_title'=> '到店自提-发送提货码',
        //         'title'=>'发送订单提货码触发'
        //     ],
        //     'tmpl_name' => 'order_pickup',
        //     'is_open'  => false,
        // ],
        /*'register_notice' => [
            'content' => '恭喜您成为（品牌名称，请手动替换）会员，了解更多活动信息请留意（品牌名称，请手动替换）官方小程序',
            'tmpl_type' => 'member',
            'sms_type' => 'notice',
            'tmpl_name' => 'register_notice',
            'send_time_desc' => [
                'tmpl_title' => '注册成功通知',
                'title' => '获取到手机号后触发'
            ],
            'is_open' => false,
        ],*/
        /*'reservation_notice' => [
            'content' => '您已预约成功在{{日期}}到{{门店名称}}使用{{权益名称}}。门店地址：{{门店地址}}，联系电话：{{联系电话}}。如需更改请提前联系门店',
            'tmpl_type' => 'member',
            'sms_type' => 'notice',
            'send_time_desc' => [
                'tmpl_title' => '预约成功通知',
                'title' => '预约成功后触发'
            ],
            'tmpl_name' => 'reservation_notice',
            'is_open' => false,
        ],
        'gotoShop_notice' => [
            'content' => '您预约了今天{{日期}}在{{门店名称}}的{{权益名称}}，请准时到店。门店地址：{{门店地址}}，联系电话：{{联系电话}}',
            'tmpl_type' => 'member',
            'sms_type' => 'notice',
            'send_time_desc' => [
                'tmpl_title' => '预约到店提醒通知',
                'title' => '在指定时间触发预约提醒通知（默认为一小时前）'
            ],
            'tmpl_name' => 'gotoShop_notice',
            'is_open' => false,
        ],*/
        'bargainFinish_notice' => [
            'content' => '恭喜您，您的{{商品名称}}助力成功，支付金额：{{支付金额}}元，请于{{结束时间}}前完成支付！',
            'tmpl_type' => 'promotions',
            'sms_type' => 'notice',
            'send_time_desc' => [
                'tmpl_title' => '助力成功通知',
                'title' => '助力成功后触发'
            ],
            'tmpl_name' => 'bargainFinish_notice',
            'is_open' => false,
        ],
        'registration_success_notice' => [
            'content' => '您好！您参与的{{活动名称}}活动，已经报名成功，活动开始时间：{{活动开始时间}} 活动地点：{{活动地点}} 活动详细地址：{{活动详细地址}} 感谢您的参与让这次活动更加精彩。欢迎参会，顺祝时祺！',
            'tmpl_type' => 'registration',
            'sms_type' => 'notice',
            'send_time_desc' => [
                'tmpl_title' => '活动报名成功通知',
                'title' => '活动报名成功后触发'
            ],
            'tmpl_name' => 'registration_success_notice',
            'is_open' => false,
        ],
        'registration_fail_notice' => [
            'content' => '您好！您参与的{{活动名称}}活动，尚未登记成功。这可能由于以下原因：{{拒绝原因}} 请核对后尽快再次填报。顺祝时祺！',
            'tmpl_type' => 'registration',
            'sms_type' => 'notice',
            'send_time_desc' => [
                'tmpl_title' => '活动报名审核失败通知',
                'title' => '活动报名审核失败后触发'
            ],
            'tmpl_name' => 'registration_fail_notice',
            'is_open' => false,
        ],
        'registration_result_notice' => [
            'content' => '您参与的{{活动名称}}活动，已经{{审核结果}}',
            'tmpl_type' => 'registration',
            'sms_type' => 'notice',
            'send_time_desc' => [
                'tmpl_title' => '报名活动审核通过通知',
                'title' => '报名活动审核通过后触发'
            ],
            'tmpl_name' => 'registration_result_notice',
            'is_open' => false,
        ],
        // 'merchant_audit_success_notice' => [
        //     'content' => "您申请的商户入驻已审批通过，请登录（商户入驻链接（H5）请手动替换）查看商户后台登录的账号和密码。",
        //     'tmpl_type' => 'merchant',
        //     'sms_type' => 'notice',
        //     'send_time_desc' => [
        //         'tmpl_title' => 'ECShopX商户入驻-申请入驻成功通知',
        //         'title' => '商户入驻成功后触发'
        //     ],
        //     'tmpl_name' => 'merchant_audit_success_notice',
        //     'is_open' => false,
        // ],
        // 'merchant_audit_fail_notice' => [
        //     'content' => "您提交的商户入驻审批未通过，请及时登录（商户入驻链接（H5）请手动替换）查看。",
        //     'tmpl_type' => 'merchant',
        //     'sms_type' => 'notice',
        //     'send_time_desc' => [
        //         'tmpl_title' => 'ECShopX商户入驻-申请入驻审批未通过通知',
        //         'title' => '商户入驻审批未通过时触发'
        //     ],
        //     'tmpl_name' => 'merchant_audit_fail_notice',
        //     'is_open' => false,
        // ],
        // 'merchant_enter_success_notice' => [
        //     'content' => "商户入驻成功，请使用（商户后台登录地址请手动替换），登录账号为{{手机号}}，密码为{{随机密码}}登录商户后台。",
        //     'tmpl_type' => 'merchant',
        //     'sms_type' => 'notice',
        //     'send_time_desc' => [
        //         'tmpl_title' => 'ECShopX商户入驻-后台添加商户成功通知',
        //         'title' => '后台添加商户功后触发'
        //     ],
        //     'tmpl_name' => 'merchant_enter_success_notice',
        //     'is_open' => false,
        // ],
        // 'merchant_reset_password_notice' => [
        //     'content' => "您的密码是{{随机密码}}，请勿泄漏。",
        //     'tmpl_type' => 'merchant',
        //     'sms_type' => 'notice',
        //     'send_time_desc' => [
        //         'tmpl_title' => 'ECShopX商户后台登录密码重置成功通知',
        //         'title' => '商户后台登录密码重置后触发'
        //     ],
        //     'tmpl_name' => 'merchant_reset_password_notice',
        //     'is_open' => false,
        // ],
        // 'admin_account_approved' => [
        //     'content'   => '{{商户名称}}提交的Adapay分账开户申请{{步骤}}已审批完成，请及时查看。',
        //     'tmpl_type' => 'adapay',
        //     'sms_type'  => 'notice',
        //     'send_time_desc' => [
        //         'tmpl_title'=> 'Adapay分账-主商户开户审批结果通知',
        //         'title'=>'主商户开户申请审批完成触发'
        //     ],
        //     'tmpl_name' => 'admin_account_approved',
        //     'is_open'  => false,
        // ],
        // 'sub_account_approved' => [
        //     'content'   => '{{商户名称}}提交的开户申请已审批完成，请及时查看。',
        //     'tmpl_type' => 'adapay',
        //     'sms_type'  => 'notice',
        //     'send_time_desc' => [
        //         'tmpl_title'=> 'Adapay分账-子商户开户成功通知',
        //         'title'=>'子商户开户申请审批完成触发'
        //     ],
        //     'tmpl_name' => 'sub_account_approved',
        //     'is_open'  => false,
        // ],
        // 'dealer_account_reset_pwd' => [
        //     'content'   => '{{经销商名称}}登录密码已重置，密码为:{{随机密码}}，请勿泄漏！',
        //     'tmpl_type' => 'adapay',
        //     'sms_type'  => 'notice',
        //     'send_time_desc' => [
        //         'tmpl_title'=> 'Adapay分账-经销商后台登录密码重置',
        //         'title'=>'经销商后台登录密码重置'
        //     ],
        //     'tmpl_name' => 'delear_account_reset_pwd',
        //     'is_open'  => false,
        // ],
        'member_birthday' => [
            'content'   => '尊敬的会员：值此您生日之际，衷心祝您生日快乐！为感谢您对本店的支持，特此赠送您专属优惠券，请及时查收！',
            'tmpl_type' => 'member',
            'sms_type'  => 'fan-out',
            'send_time_desc' => [
                'tmpl_title'=> '场景营销-会员生日',
                'title'=>'会员生日赠送触发',
                'variables' => [],
            ],
            'tmpl_name' => 'member_birthday',
            'is_open'  => false,
        ],
        'member_anniversary' => [
            'content'   => '历史上的今天，您成为了（品牌名称，请手动替换）会员。感谢您一路的支持，特此为您奉上会员专属优惠券，请及时查收！',
            'tmpl_type' => 'member',
            'sms_type'  => 'fan-out',
            'send_time_desc' => [
                'tmpl_title'=> '场景营销-入会周年',
                'title'=>'会员周年日赠送触发',
                'variables' => [],
            ],
            'tmpl_name' => 'member_anniversary',
            'is_open'  => false,
        ],
        'member_day' => [
            'content'   => '（会员日日期，请手动替换）是（品牌名称，请手动替换）会员日，特此为您奉上会员专属优惠券，请及时查看！到店更有其他惊喜',
            'tmpl_type' => 'member',
            'sms_type'  => 'fan-out',
            'send_time_desc' => [
                'tmpl_title'=> '场景营销-会员日',
                'title'=>'会员日赠送触发',
                'variables' => [],
            ],
            'tmpl_name' => 'member_day',
            'is_open'  => false,
        ],
        'member_upgrade' => [
            'content'   => '恭喜您在（品牌名称，请手动替换）的会员升级成功，特此为您奉上会员专属优惠券，请及时查收！到店更有其他惊喜',
            'tmpl_type' => 'member',
            'sms_type'  => 'fan-out',
            'send_time_desc' => [
                'tmpl_title'=> '场景营销-普通会员升级',
                'title'=>'会员升级赠送触发',
                'variables' => [],
            ],
            'tmpl_name' => 'member_upgrade',
            'is_open'  => false,
        ],
        'member_vip_upgrade' => [
            'content'   => '恭喜您在（品牌名称，请手动替换）的会员升级成功，特此为您奉上会员专属优惠券，请及时查收！',
            'tmpl_type' => 'member',
            'sms_type'  => 'fan-out',
            'send_time_desc' => [
                'tmpl_title'=> '场景营销-付费会员升级',
                'title'=>'付费会员升级赠送触发',
                'variables' => [],
            ],
            'tmpl_name' => 'member_vip_upgrade',
            'is_open'  => false,
        ],
    ];

    public $replaceParams = [
        'telephone'       => '联系电话',
        'rights_name'     => '权益名称',
        'num'             => '数量',
        'available_times' => '可用次数',
        'brand_name'      => '品牌名称',
        'date'            => '日期',
        'shop_name'       => '门店名称',
        'shop_address'    => '门店地址',
        'item_name'       => '商品名称',
        'pay_money'       => '支付金额',
        'pay_time'        => '支付时间',
        'recharge_money'  => '充值金额',
        'recharge_date'   => '充值时间',
        'deposit_money'   => '账户余额',
        'end_time'        => '结束时间',

        'activity_name'   => '活动名称',
        'activity_start_time'   => '活动开始时间',
        'activity_place'   => '活动地点',
        'activity_address'   => '活动详细地址',
        'activity_refuse_reason'   => '拒绝原因',

        'review_result'   => '审核结果',
        'password'        => '随机密码',
        'phone'           => '手机号',
        'code'            => '验证码',
        'order_id'        => '订单号',
        'pickup_code'     => '提货码',
        'step'            => '步骤',
        'dealer'          => '经销商名称',
        'mer_name'        => '商户名称',
    ];

    public function getByName($name)
    {
        if (isset($this->defaultTemplate[$name])) {
            return $this->defaultTemplate[$name];
        } else {
            return null;
        }
    }

    //获取替换参数
    public function getReplaceParams()
    {
        return $this->replaceParams;
    }

    public function lists()
    {
        return $this->defaultTemplate;
    }
}
