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

namespace PromotionsBundle\Services\WxaTemplateMsg;

use Dingo\Api\Exception\ResourceException;
use PromotionsBundle\Entities\WxaNoticeTemplate;
use WechatBundle\Services\OpenPlatform;
use WechatBundle\Services\WeappService;
use OrdersBundle\Services\OrderAssociationService;

class TemplateList
{
    public $lists = [
        'memberCreateSucc' => [
            'id' => '5117', // 小程序模版ID
            'title' => '注册成功提醒',
            'scene_desc' => '注册成功提醒', // 模板描述
            'keyword_id_list' => [2, 3],
            'template_name' => ['yykcutdown', 'yykweishop', 'appleweishop', 'yykcommunity'],
            'value' => [ // 对应的参数字段名称
                ['column' => 'date', 'title' => '注册时间','keyword' => 'date2'],
                ['column' => 'notice', 'title' => '温馨提示', 'keyword' => 'thing3'], // 订单号
            ],
            // 'emp_has_is_keyword' => false, //是否为放大
            'pages' => 'pages/index',
            'send_time_desc' => ['title' => '会员注册成功后触发'], // 触发规则描述
            'tmpl_type' => '会员提醒', // 模版分类 交易
        ],
        'paymentSucc' => [
            'id' => '1648', // 小程序模版ID
            'title' => '订单支付成功通知',
            'scene_desc' => '订单支付成功通知', // 模板描述
            'keyword_id_list' => [2, 3, 6, 8, 7], //小程序模版ID需要使用的字段id
            'template_name' => ['yykcutdown', 'yykweishop','appleweishop', 'yykcommunity', 'yykmendian', 'yykmembership'],
            'value' => [ // 对应的参数字段名称
                ['column' => 'order_id','title' => '订单号',   'keyword' => 'character_string2'],
                ['column' => 'pay_money', 'title' => '订单金额', 'keyword' => 'amount3'],
                ['column' => 'item_name','title' => '订单商品', 'keyword' => 'thing6'],
                ['column' => 'pay_date', 'title' => '支付时间', 'keyword' => 'date8'],
                ['column' => 'receipt_type', 'title' => '取货方式', 'keyword' => 'thing7'],
            ],
            // 'emp_has_is_keyword' => 'keyword1',
            'send_time_desc' => ['title' => '订单支付后触发'],
            'tmpl_type' => '交易提醒',
        ],
        'reservationRemind' => [ // 预约提醒
            'id' => '5378',
            'title' => '预约到店提醒',
            'scene_desc' => '预约到店提醒', // 模板描述
            'keyword_id_list' => [1, 2, 6, 8, 4],
            'template_name' => ['yykmendian'],
            'value' => [ // 对应的参数字段名称
                ['column' => 'name', 'title' => '预约内容',   'keyword' => 'thing1'],
                ['column' => 'date', 'title' => '预约时间',   'keyword' => 'date2'],
                ['column' => 'shop_name', 'title' => '预约门店',   'keyword' => 'thing6'],
                ['column' => 'shop_address', 'title' => '门店地址',   'keyword' => 'thing8'],
                ['column' => 'remarks', 'title' => '温馨提示',   'keyword' => 'thing4'],
            ],
            'send_time_desc' => ['title' => '预约到期前', 'time_list' => [90, 60, 30, 10], 'value' => 60],//时间为分钟
            'tmpl_type' => '交易提醒',
        ],
        'payOrdersRemind' => [
            'id' => '2723',
            'title' => '订单待支付提醒',
            'scene_desc' => '订单待支付提醒', // 模板描述
            'keyword_id_list' => [1, 7, 6, 8, 4],
            'template_name' => ['yykweishop','appleweishop',],
            'value' => [ // 对应的参数字段名称
                ['column' => 'order_id', 'keyword' => 'character_string1', 'title' => '订单号'], // 订单号
                ['column' => 'pay_money', 'keyword' => 'amount7', 'title' => '待支付金额'], // 待支付金额
                ['column' => 'item_name', 'keyword' => 'thing6', 'title' => '商品名称'], // 商品名称
                ['column' => 'created', 'keyword' => 'date8', 'title' => '下单时间'], // 下单时间
                ['column' => 'remarks', 'keyword' => 'thing4', 'title' => '温馨提示'], // 温馨提示
            ],
            'send_time_desc' => ['title' => '买家下单', 'time_list' => [5, 10, 15, 20, 30, 60, 120, 180], 'value' => 10, 'end_title' => '未付款触发'],//时间为分钟
            // 'emp_has_is_keyword' => 'keyword1',
            'tmpl_type' => '交易提醒',
        ],
        'orderDeliverySucc' => [
            'id' => '1856',
            'title' => '订单发货提醒',
            'scene_desc' => '订单发货提醒', // 模板描述
            'keyword_id_list' => [7, 14, 3, 5],
            'template_name' => ['yykweishop','appleweishop'],
            'value' => [ // 对应的参数字段名称
                ['column' => 'order_id', 'title' => '订单号', 'keyword' => 'character_string7'],
                ['column' => 'delivery_corp', 'title' => '物流公司', 'keyword' => 'thing14'],
                ['column' => 'delivery_code', 'title' => '快递单号', 'keyword' => 'character_string3'],
                ['column' => 'item_name', 'title' => '商品信息', 'keyword' => 'thing5'],
            ],
            'send_time_desc' => ['title' => '商家发货后立即触发'],
            'tmpl_type' => '交易提醒',
        ],
        'aftersalesRefuse' => [
            'id' => '4330',
            'title' => '售后通知',
            'scene_desc' => '售后通知', // 模板描述
            'keyword_id_list' => [1, 5, 6],
            'template_name' => ['yykweishop','appleweishop'],
            'value' => [ // 对应的参数字段名称
                ['column' => 'order_id', 'title' => '订单编号', 'keyword' => 'character_string1'],
                ['column' => 'refund_fee', 'title' => '退款金额', 'keyword' => 'amount5'],
                ['column' => 'remarks', 'title' => '备注', 'keyword' => 'thing6'],
            ],
            'send_time_desc' => ['title' => '商家售后操作后触发'],
            'tmpl_type' => '交易提醒',
        ],
        'userGetCardSucc' => [ // 用户获取优惠券提醒
            'id' => '3995',
            'title' => '优惠券领取通知',
            'scene_desc' => '优惠券状态通知', // 模板描述
            'keyword_id_list' => [1, 2, 3, 4],
            'template_name' => ['yykweishop','appleweishop'],
            'value' => [ // 对应的参数字段名称
                ['column' => 'title', 'title' => '优惠券名称', 'keyword' => 'thing1'],
                ['column' => 'active_date', 'title' => '有效期', 'keyword' => 'character_string2'],
                ['column' => 'used_action', 'title' => '使用方式', 'keyword' => 'thing3'],
                ['column' => 'remarks', 'title' => '温馨提醒', 'keyword' => 'thing4'],
            ],
            'send_time_desc' => ['title' => '优惠券领取触发'],//时间为分钟
            'tmpl_type' => '交易提醒',
        ],
        //'cardUsedSucc' => [ // 用户获取优惠券提醒
        //    'id' => '2767',
        //    'title' => '优惠券状态变更通知',
        //    'scene_desc' => '优惠券状态通知', // 模板描述
        //    'keyword_id_list' => [3, 1, 2, 4],
        //    'template_name' => ['yykweishop','appleweishop'],
        //    'value' => [ // 对应的参数字段名称
        //        ['column'=>'amount', 'title'=>'面额', 'keyword' => 'amount3'],
        //        ['column'=>'status', 'title'=>'状态', 'keyword' => 'phrase1'],
        //        ['column'=>'activedate', 'title'=>'有效期', 'keyword' => 'date2'],
        //        ['column'=>'remarks', 'title'=>'使用说明', 'keyword' => 'thing4'],
        //    ],
        //    'send_time_desc' => ['title'=>'优惠券状态改变触发'],//时间为分钟
        //    'tmpl_type' => '交易提醒',
        //],
        'registrationResultNotice' => [ // 用户获取优惠券提醒
            'id' => '6618',
            'title' => '报名结果通知',
            'scene_desc' => '报名结果通知', // 模板描述
            'keyword_id_list' => [1, 2], //27备注
            'template_name' => ['yykweishop','appleweishop'],
            'value' => [ // 对应的参数字段名称
                ['column' => 'activity_name', 'title' => '报名活动', 'keyword' => 'thing1'],
                ['column' => 'review_result', 'title' => '报名结果', 'keyword' => 'thing2'],
            ],
            'send_time_desc' => ['title' => '报名审核结果通知'],//时间为分钟
            'tmpl_type' => '交易提醒',
        ],
        'registrationActivityNotice' => [ // 活动开始前提醒
            'id' => '342',
            'title' => '活动开始提醒',
            'scene_desc' => '活动开始提醒', // 模板描述
            'keyword_id_list' => [10, 12, 13, 6], //27备注
            'template_name' => ['yykweishop','appleweishop'],
            'value' => [ // 对应的参数字段名称
                ['column' => 'activity_name', 'title' => '报名活动', 'keyword' => 'thing10'],
                ['column' => 'activity_start_time', 'title' => '开始时间', 'keyword' => 'time12'],
                ['column' => 'activity_end_time', 'title' => '结束时间', 'keyword' => 'time13'],
                ['column' => 'activity_address', 'title' => '活动地址', 'keyword' => 'thing6'],
            ],
            'pages' => 'marketing/pages/member/activity-detail',
            'send_time_desc' => ['title' => '活动开始前', 'time_list' => [2, 4, 8, 12, 24, 48, 72], 'value' => 24, 'time_unit' => '小时', 'end_title' => '，触发通知'],//时间为小时
            'tmpl_type' => '交易提醒',
        ],
        'goodsArrivalNotice' => [ //商品到货通知
            'id' => '5019',
            'title' => '商品到货通知',
            'scene_desc' => '商品到货通知',
            'keyword_id_list' => [1, 2], //27备注
            'template_name' => ['yykweishop','appleweishop'],
            'value' => [ // 对应的参数字段名称
                ['column' => 'item_name', 'title' => '商品名称', 'keyword' => 'thing1'],
                ['column' => 'notice', 'title' => '温馨提示', 'keyword' => 'thing2'],
            ],
            'pages' => 'pages/item/espier-detail',
            'send_time_desc' => ['title' => '缺货商品到货通知到会员'],//时间为分钟
            'tmpl_type' => '会员提醒',
        ],
        /** 暂时不用
        'pickSuccess' => [
            'id' => '4037',
            'title' => '自提订单取货通知',
            'scene_desc' => '自提订单取货通知', // 模板描述
            'keyword_id_list' => [1, 6, 3, 4], //27备注
            'template_name' => ['yykweishop','appleweishop'],
            'value' => [ // 对应的参数字段名称
                ['column'=>'order_id', 'title'=>'订单编号', 'keyword' => 'character_string1'],
                ['column'=>'store_name', 'title'=>'自提门店', 'keyword' => 'thing6'],
                ['column'=>'title', 'title'=>'自提内容', 'keyword' => 'thing3'],
                ['column'=>'', 'title'=>'友情提示', 'keyword' => 'thing4', 'value' => '请按时提货！'],
            ],
            'send_time_desc' => ['title'=>'自提订单取货通知'],//时间为分钟
            'tmpl_type' => '交易提醒',
        ],
        */
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
     * 获取小程序通知模版列表，并且初始化
     */
    public function getTemplateList($companyId, $templateName, $wxappAppid)
    {
        $wxaNoticeTemplateRepository = app('registry')->getManager('default')->getRepository(WxaNoticeTemplate::class);
        $templateList = [];
        foreach ($this->lists as $scenesName => $row) {
            if (in_array($templateName, $row['template_name'])) {
                $templateList[$scenesName] = $row;
            }
        }
        if (empty($templateList)) {
            return [];
        }
        $lists = $wxaNoticeTemplateRepository->lists(['company_id' => $companyId, 'template_name' => $templateName]);
        if ($lists['total_count'] > 0) {
            $tmpList = array_column($lists['list'], null, 'scenes_name');
        }

        foreach ($templateList as $scenesName => &$row) {
            if (isset($tmpList[$scenesName])) {
                $row = $tmpList[$scenesName];
            } else {
                // 兼容老数据
                $templateId = $this->getTemplateId($row['id'], $scenesName, $wxappAppid);
                $row = [
                    'template_name' => $templateName,
                    'wxa_template_id' => $row['id'],
                    'template_id' => $templateId ?: '',
                    'company_id' => $companyId,
                    'notice_type' => 'wxa',
                    'tmpl_type' => $row['tmpl_type'],
                    'title' => $row['title'] ?? '',
                    'scenes_name' => $scenesName ?? '',
                    'scene_desc' => $row['scene_desc'] ?? ($row['title'] ?? ''),
                    'content' => json_encode($row['value']),
                    'is_open' => false,
                    'send_time_desc' => json_encode($row['send_time_desc']),
                    'created' => time(),
                ];
                $wxaNoticeTemplateRepository->create($row);
            }

            $row['send_time_desc'] = json_decode($row['send_time_desc'], true);
            $row['content'] = json_decode($row['content'], true);
            $row['is_open'] = $row['is_open'] ? true : false;
        }
        return $templateList;
    }

    /**
     * 开启/关闭 模版通知
     */
    public function openTemplate($companyId, $scenesName, $templateName, $wxappAppid, $isOpen = true, $sendTime = 0)
    {
        $wxaNoticeTemplateRepository = app('registry')->getManager('default')->getRepository(WxaNoticeTemplate::class);
        $info = $wxaNoticeTemplateRepository->getInfo(['company_id' => $companyId, 'scenes_name' => $scenesName, 'template_name' => $templateName]);
        $templateId = $info['template_id'];
        if (!$isOpen && $templateId) {
            $delRes = $this->delTemplate($templateId, $wxappAppid, $scenesName, $info);
            if ($delRes) {
                $updateData['template_id'] = '';
            }
        } else {
            $oldData = $this->lists[$scenesName] ?? null;
            if ($oldData) {
                $updateData['content'] = json_encode($oldData['value']);
            }
            if (!$templateId && $isOpen) {
                $templateId = $this->addTemplate($info, $wxappAppid, $scenesName);
                $updateData['template_id'] = $templateId;
            } else {
                $updateData['template_id'] = $templateId;
            }

            if ($sendTime) {
                $info['send_time_desc'] = json_decode($info['send_time_desc'], true);
                $info['send_time_desc']['value'] = $sendTime;
                $updateData['send_time_desc'] = json_encode($info['send_time_desc']);
            }
        }

        $updateData['is_open'] = $isOpen;
        return $wxaNoticeTemplateRepository->updateOneBy(['id' => $info['id']], $updateData);
    }

    /**
     * 获取单个模版详情，判断是否开启等
     */
    public function getTemplateInfo($companyId, $scenesName, $wxappAppid)
    {
        $weappService = new WeappService();
        $weappInfo = $weappService->getWeappInfo($companyId, $wxappAppid);
        if (!$weappInfo) {
            return [];
        }

        $templateName = $weappInfo['template_name'];
        $wxaNoticeTemplateRepository = app('registry')->getManager('default')->getRepository(WxaNoticeTemplate::class);
        $info = $wxaNoticeTemplateRepository->getInfo(['company_id' => $companyId, 'scenes_name' => $scenesName, 'template_name' => $templateName]);
        if (!$info || !$info['is_open'] || !$info['template_id']) {
            return [];
        }

        $info['content'] = json_decode($info['content'], true);
        $isOldKey = true;
        if ($info['content']) {
            foreach ($info['content'] as $value) {
                if (isset($value['keyword'])) {
                    $isOldKey = false;
                }
            }
        }

        if ($isOldKey) {
            if (!isset($this->lists[$info['scenes_name']]['value']) || !$this->lists[$info['scenes_name']]['value']) {
                return [];
            }
            $info['content'] = $this->lists[$info['scenes_name']]['value'];
        }
        $info['send_time_desc'] = json_decode($info['send_time_desc'], true);
        $info['pages'] = isset($this->lists[$info['scenes_name']]['pages']) ? $this->lists[$info['scenes_name']]['pages'] : 'pages/index';
        if (isset($this->lists[$info['scenes_name']]['emp_has_is_keyword'])) {
            $info['emp_has_is_keyword'] = $this->lists[$info['scenes_name']]['emp_has_is_keyword'];
        }
        return $info;
    }

    /**
     * 获取模版ID
     */
    public function getTemplateId($id, $scenesName, $wxaAppId)
    {
        return app('redis')->hget('wxopen_template_library:'.$wxaAppId.':'.$id, $scenesName);
    }

    //添加模版
    public function addTemplate($row, $wxaAppId, $scenesName)
    {
        $openPlatform = new OpenPlatform();
        $app = $openPlatform->getAuthorizerApplication($wxaAppId);
        $tid = $row['wxa_template_id'];
        $kidList = $this->lists[$row['scenes_name']]['keyword_id_list'];
        $sceneDesc = $this->lists[$row['scenes_name']]['scene_desc'];
        $result = $app->subscribe_message->addTemplate($tid, $kidList, $sceneDesc);
        if (!empty($result['errcode'])) {
            throw new ResourceException($result['errmsg']);
        }
        $templateId = $result['priTmplId'];

        app('redis')->hset('wxopen_template_library:'.$wxaAppId.':'.$row['wxa_template_id'], $scenesName, $templateId);

        return $templateId;
    }

    public function delTemplate($templateId, $wxaAppId, $scenesName, $row)
    {
        $openPlatform = new OpenPlatform();
        $app = $openPlatform->getAuthorizerApplication($wxaAppId);

        $result = $app->subscribe_message->deleteTemplate($templateId);
        if ($result['errmsg'] == 'ok') {
            app('redis')->hdel('wxopen_template_library:'.$wxaAppId.':'.$row['wxa_template_id'], $scenesName);
            return true;
        }
        return false;
    }
}
