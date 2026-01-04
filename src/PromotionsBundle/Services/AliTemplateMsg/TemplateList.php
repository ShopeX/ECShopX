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

namespace PromotionsBundle\Services\AliTemplateMsg;

use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Services\OrderAssociationService;

class TemplateList
{
    public $lists = [
        'memberCreateSucc' => [
            'title' => '注册成功提醒',
            'scene_desc' => '注册成功提醒', // 模板描述
            'value' => [ // 对应的参数字段名称
                ['column' => 'date', 'title' => '注册时间','keyword' => 'date'],
                ['column' => 'notice', 'title' => '温馨提示', 'keyword' => 'thing'], // 订单号
            ],
            'pages' => 'pages/index',
            'send_time_desc' => ['title' => '会员注册成功后触发'], // 触发规则描述
            'tmpl_type' => '会员提醒', // 模版分类 交易
        ],
        'paymentSucc' => [
            'title' => '订单支付成功通知',
            'scene_desc' => '订单支付成功通知', // 模板描述
            'value' => [ // 对应的参数字段名称
                ['column' => 'order_id','title' => '订单号',   'keyword' => 'character_string'],
                ['column' => 'pay_money', 'title' => '订单金额', 'keyword' => 'amount'],
                ['column' => 'item_name','title' => '订单商品', 'keyword' => 'thing'],
                ['column' => 'pay_date', 'title' => '支付时间', 'keyword' => 'date'],
                ['column' => 'receipt_type', 'title' => '取货方式', 'keyword' => 'thing'],
            ],
            'send_time_desc' => ['title' => '订单支付后触发'],
            'tmpl_type' => '交易提醒',
        ],
        'payOrdersRemind' => [
            'title' => '订单待支付提醒',
            'scene_desc' => '订单待支付提醒', // 模板描述
            'value' => [ // 对应的参数字段名称
                ['column' => 'order_id', 'keyword' => 'character_string', 'title' => '订单号'], // 订单号
                ['column' => 'pay_money', 'keyword' => 'amount', 'title' => '待支付金额'], // 待支付金额
                ['column' => 'item_name', 'keyword' => 'thing', 'title' => '商品名称'], // 商品名称
                ['column' => 'created', 'keyword' => 'date', 'title' => '下单时间'], // 下单时间
                ['column' => 'remarks', 'keyword' => 'thing', 'title' => '温馨提示'], // 温馨提示
            ],
            'send_time_desc' => ['title' => '买家下单', 'time_list' => [5, 10, 15, 20, 30, 60, 120, 180], 'value' => 10, 'end_title' => '未付款触发'],//时间为分钟
            'tmpl_type' => '交易提醒',
        ],
        'orderDeliverySucc' => [
            'title' => '订单发货提醒',
            'scene_desc' => '订单发货提醒', // 模板描述
            'value' => [ // 对应的参数字段名称
                ['column' => 'order_id', 'title' => '订单号', 'keyword' => 'character_string'],
                ['column' => 'delivery_corp', 'title' => '物流公司', 'keyword' => 'thing'],
                ['column' => 'delivery_code', 'title' => '快递单号', 'keyword' => 'character_string'],
                ['column' => 'item_name', 'title' => '商品信息', 'keyword' => 'thing'],
            ],
            'send_time_desc' => ['title' => '商家发货后立即触发'],
            'tmpl_type' => '交易提醒',
        ],
        'aftersalesRefuse' => [
            'title' => '售后通知',
            'scene_desc' => '售后通知', // 模板描述
            'value' => [ // 对应的参数字段名称
                ['column' => 'order_id', 'title' => '订单编号', 'keyword' => 'character_string'],
                ['column' => 'refund_fee', 'title' => '退款金额', 'keyword' => 'amount'],
                ['column' => 'remarks', 'title' => '备注', 'keyword' => 'thing'],
            ],
            'send_time_desc' => ['title' => '商家售后操作后触发'],
            'tmpl_type' => '交易提醒',
        ],
        'userGetCardSucc' => [ // 用户获取优惠券提醒
            'title' => '优惠券领取通知',
            'scene_desc' => '优惠券状态通知', // 模板描述
            'value' => [ // 对应的参数字段名称
                ['column' => 'title', 'title' => '优惠券名称', 'keyword' => 'thing'],
                ['column' => 'active_date', 'title' => '有效期', 'keyword' => 'character_string'],
                ['column' => 'used_action', 'title' => '使用方式', 'keyword' => 'thing'],
                ['column' => 'remarks', 'title' => '温馨提醒', 'keyword' => 'thing'],
            ],
            'send_time_desc' => ['title' => '优惠券领取触发'],//时间为分钟
            'tmpl_type' => '交易提醒',
        ],
        'registrationResultNotice' => [ // 用户获取优惠券提醒
            'title' => '报名结果通知',
            'scene_desc' => '报名结果通知', // 模板描述
            'value' => [ // 对应的参数字段名称
                ['column' => 'activity_name', 'title' => '报名活动', 'keyword' => 'thing'],
                ['column' => 'review_result', 'title' => '报名结果', 'keyword' => 'thing'],
            ],
            'send_time_desc' => ['title' => '报名审核结果通知'],//时间为分钟
            'tmpl_type' => '交易提醒',
        ],
        'goodsArrivalNotice' => [ //商品到货通知
            'title' => '商品到货通知',
            'scene_desc' => '商品到货通知',
            'value' => [ // 对应的参数字段名称
                ['column' => 'item_name', 'title' => '商品名称', 'keyword' => 'thing'],
                ['column' => 'notice', 'title' => '温馨提示', 'keyword' => 'thing'],
            ],
            'pages' => 'pages/item/espier-detail',
            'send_time_desc' => ['title' => '缺货商品到货通知到会员'],//时间为分钟
            'tmpl_type' => '会员提醒',
        ],
    ];

    /**
     * 检查是否需要发送
     */
    public function checkSend($data)
    {
        $isSend = true;
        switch ($data['scenes_name']) {
          case 'payOrdersRemind':
              $orderAssociationService = new OrderAssociationService();
              $order = $orderAssociationService->getOrder($data['company_id'], $data['data']['order_id']);
              if (!$order || $order['order_status'] != 'NOTPAY' || $order['order_class'] == 'drug') {
                  $isSend = false;
              }
              break;
        }
        return $isSend;
    }

    /**
     * 获取支付宝小程序通知模版列表，并且初始化
     */
    public function getTemplateList($companyId)
    {
        $templateList = [];
        foreach ($this->lists as $scenesName => $row) {
            $templateList[$scenesName] = $row;
        }

        foreach ($templateList as $scenesName => &$row) {
            $template = $this->getTemplate($companyId, $scenesName);
            $templateId = $template['template_id'] ?? '';
            $row = [
                'template_id' => $templateId,
                'company_id' => $companyId,
                'notice_type' => 'alipay',
                'tmpl_type' => $row['tmpl_type'],
                'title' => $row['title'],
                'scenes_name' => $scenesName,
                'scene_desc' => $row['scene_desc'],
                'content' => $row['value'],
                'is_open' => $templateId ? true : false,
                'send_time_desc' => $row['send_time_desc'],
            ];
            if ($template && $template['send_time'] > 0) {
                $row['send_time_desc']['value'] = $template['send_time'];
            }
        }
        return $templateList;
    }

    /**
     * 开启/关闭 模版通知
     */
    public function openTemplate($companyId, $scenesName, $isOpen = true, $templateId = null, $sendTime = 0)
    {
        if (!$isOpen) {
            $this->delTemplate($companyId, $scenesName);
        } else {
            $template = [
                'template_id' => $templateId,
                'send_time' => $sendTime,
            ];
            $this->addTemplate($companyId, $scenesName, $template);
        }

        return true;
    }

    /**
     * 获取单个模版详情，判断是否开启等
     */
    public function getTemplateInfo($companyId, $scenesName)
    {
        $template = $this->getTemplate($companyId, $scenesName);
        if (!$template) {
            return [];
        }

        $row = $this->lists[$scenesName];
        $info = [
            'template_id' => $template['template_id'],
            'company_id' => $companyId,
            'notice_type' => 'alipay',
            'tmpl_type' => $row['tmpl_type'],
            'title' => $row['title'],
            'scenes_name' => $scenesName,
            'scene_desc' => $row['scene_desc'],
            'content' => $row['value'],
            'send_time_desc' => $row['send_time_desc'],
            'pages' => $row['pages'] ?? 'pages/index',
        ];
        if ($template['send_time'] > 0) {
            $info['send_time_desc']['value'] = $template['send_time'];
        }

        return $info;
    }

    /**
     * 获取模版ID
     */
    public function getTemplate($companyId, $scenesName)
    {
        $template = app('redis')->get('aliopen_template_library:'.$companyId.':'.$scenesName);
        if ($template) {
            return json_decode($template, true);
        }
        return [];
    }

    //添加模版
    public function addTemplate($companyId, $scenesName, $template)
    {
        app('redis')->set('aliopen_template_library:'.$companyId.':'.$scenesName, json_encode($template));

        return $template['template_id'];
    }

    public function delTemplate($companyId, $scenesName)
    {
        app('redis')->del('aliopen_template_library:'.$companyId.':'.$scenesName);
        return true;
    }
}
