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

namespace OrdersBundle\Services\Orders;

use OrdersBundle\Entities\CancelOrders;

/**
 * 店务端app附加字段的状态处理
 * Class OrderAppAttachService
 * @package OrdersBundle\Services\Orders
 */
class OrderAppAttachService
{
    public const DETAIL_STATUS_TYPE_TEXT = 'text';
    public const DETAIL_STATUS_TYPE_NOT_PAY = 'not_pay';
    public const DETAIL_STATUS_TYPE_CANCEL = 'cancel';
    public const DETAIL_STATUS_TYPE_DADA_CANCEL = 'dada_cancel';

    public const DETAIL_STATUS_ICON_SUCCESS_1 = 'success_1';
    // 未妥投
    public const DETAIL_STATUS_ICON_SUCCESS_2 = 'success_2';

    public const DETAIL_STATUS_ICON_DELIVERY_1 = 'delivery_1';
    // 骑手配送
    public const DETAIL_STATUS_ICON_DELIVERY_2 = 'delivery_2';

    public const DETAIL_STATUS_ICON_PAYED_1 = 'payed_1';
    // 骑手取消
    public const DETAIL_STATUS_ICON_PAYED_2 = 'payed_2';
    // 尚未接单
    public const DETAIL_STATUS_ICON_PAYED_3 = 'payed_3';
    // 已接单
    public const DETAIL_STATUS_ICON_PAYED_4 = 'payed_4';
    // 已到店
    public const DETAIL_STATUS_ICON_PAYED_5 = 'payed_5';
    // 自提
    public const DETAIL_STATUS_ICON_PAYED_6 = 'payed_6';

    public const DETAIL_STATUS_ICON_NOT_PAY = 'not_pay';

    public const DETAIL_STATUS_ICON_CANCEL = 'cancel';

    /**
     * - status_info
     * - main_status 主状态
     * - `cancel`   已取消
     * - `notpay`   待支付
     * - `notship`  待发货
     * - `shipping` 已发货
     * - `finish`   已完成
     * - child_status 子状态
     * - `cancel_buyer` 已取消状态下用户取消
     * - `cancel_shop`  已取消状态下商家取消
     * - `dada_0`       同城配待发货状态下商家接单
     * - `dada_1`       同城配待发货状态下骑士接单
     * - `dada_2`       同城配待发货状态下待取货
     * - `dada_100`     同城配待发货状态下骑士到店
     * - `dada_5`       同城配待发货状态下已取消
     * - `dada_3`       同城配已发货状态下配送中
     * @param $order_status
     * @param $delivery_type
     * @param $params
     * @return string[]
     */
    public function getAppInfo($order_status, $delivery_type, $params)
    {
        // TODO: optimize this method
        $status_info = [
            'main_status' => '',   // 主状态
            'child_status' => '',  // 子状态
        ];
        $list_status_mag = '';

        $detail_status_msg = '';
        $detail_status = [
            // success | danger | delivery | dada_delivery | ziti | not_pay | cancel
            'icon' => self::DETAIL_STATUS_ICON_SUCCESS_1,
            'main_msg' => '',
            'description' => '',
            // text | cancel | dada_cancel | not_pay
            'type' => self::DETAIL_STATUS_TYPE_TEXT,
        ];

        $terminal_info = null;

        $buttons = [];

        $dada_status = $params['dada_status'] ?? '';
        // 特殊映射
        if ($params['order_status_des'] == 'PAYED_WAIT_PROCESS') {
            // 已支付待退款
            $order_status = 'CANCEL';
        }
        if ($params['order_status_des'] == 'PAYED_PARTAIL') {
            // 部分发货
            $order_status = 'WAIT_BUYER_CONFIRM';
        }
        switch ($order_status) {
            case 'WAIT_GROUPS_SUCCESS':
                $status_info['main_status'] = 'notship';
                $list_status_mag = $detail_status_msg = '等待成团';
                break;
            // 前两个状态都是没有的
            case 'REFUND_PROCESS':
            case 'REFUND_SUCCESS':
            case 'CANCEL':
                // 已取消
                $status_info['main_status'] = 'cancel';
                $cancel_from = $this->getCancelFrom($params);
                if ($cancel_from == 'buyer') {
                    // 子状态
                    $status_info['child_status'] = 'cancel_buyer';
                    //  详情文案描述
                    $detail_status['description'] = '用户自主取消订单';
                } else {
                    $status_info['child_status'] = 'cancel_shop';
                    $buttons[] = 'contact';
                    $detail_status['description'] = '商家取消订单';
                }
                if ($params['order_status_des'] == 'PAYED_WAIT_PROCESS') {
                    $buttons[] = 'confirmcancel';
                    if ($cancel_from == 'buyer') {
                        $detail_status['description'] = '用户取消已支付订单，待退款';
                    } else {
                        $detail_status['description'] = '商家取消已支付订单，待退款';
                    }
                }
                $detail_status_msg = $list_status_mag = '订单已取消';
                $detail_status['icon'] = self::DETAIL_STATUS_ICON_CANCEL;
                $terminal_info = [
                    'msg' => '取消时间',
                    'time' => $params['update_time'],
                ];
                break;
            case 'NOTPAY':
                // 待付款
                $status_info['main_status'] = 'notpay';
                $list_status_mag = '待付款';
                $detail_status_msg = '订单待付款';
                $buttons = array_merge($buttons, ['contact', 'payment', 'cancel', 'markdown']);
                $detail_status['type'] = self::DETAIL_STATUS_TYPE_NOT_PAY;
                $detail_status['icon'] = self::DETAIL_STATUS_ICON_NOT_PAY;
                break;
            case 'REVIEW_PASS':
            case 'PAYED':
                // 待发货
                $status_info['main_status'] = 'notship';
                $detail_status_msg = '订单已付款，待发货';
                if ($delivery_type == 'dada') {
                    // 子状态
                    $status_info['child_status'] = 'dada_'.($dada_status);
                    switch ($dada_status) {
                        case '0':
                            $list_status_mag = '已付款待接单';
                            $detail_status['description'] = '买家已支付，请尽快接单';
                            $detail_status['icon'] = self::DETAIL_STATUS_ICON_PAYED_1;
                            break;
                        case '1':
                            $list_status_mag = '骑士待接单';
                            $detail_status['description'] = '当前尚未有骑士接单…';
                            $detail_status['icon'] = self::DETAIL_STATUS_ICON_PAYED_3;
                            break;
                        case '5':
                            $list_status_mag = '骑士已取消';
                            $detail_status['type'] = self::DETAIL_STATUS_TYPE_DADA_CANCEL;
                            $detail_status['icon'] = self::DETAIL_STATUS_ICON_PAYED_2;
                            break;
                        case '2':
                            $list_status_mag = '骑士待取货';
                            $detail_status['description'] = '骑士已接单，请尽快拣货';
                            $detail_status['icon'] = self::DETAIL_STATUS_ICON_PAYED_4;
                            break;
                        case '100':
                            $detail_status['description'] = '骑士已到店，正在取货';
                            $list_status_mag = '骑士已到店';
                            $detail_status['icon'] = self::DETAIL_STATUS_ICON_PAYED_5;
                            break;
                        case '3':
                            // 同城配送未同步
                            break;
                    }
                    if ($dada_status == 0) {
                        $buttons = array_merge($buttons, ['contact', 'accept', 'cancel']);
                    } else {
                        $buttons = array_merge($buttons, ['contact']);
                    }
                    if ($dada_status == 5) {
                        $detail_status_msg = '';
                    }
                } elseif ($delivery_type == 'ziti') {
                    $list_status_mag = '待自提';
                    $buttons = array_merge($buttons, ['contact', 'consume']);
                    $detail_status['icon'] = self::DETAIL_STATUS_ICON_PAYED_6;
                }elseif ($delivery_type == 'merchant') {
                    $buttons = array_merge($buttons, ['delivery', 'cancel']);
                    if($params['self_delivery_status'] == 'CONFIRMING'){
                        $list_status_mag = '待确认';
                        $buttons = array_merge($buttons, ['confirmdeliverystaff','confirmpackag']);
                    }elseif($params['self_delivery_status'] == 'RECEIVEORDER'){
                        $list_status_mag = '已接单';
                        $buttons = array_merge($buttons, ['canceldeliverystaff','confirmpackag']);
                    }
                    elseif($params['self_delivery_status'] == 'PACKAGED'){
                        $list_status_mag = '已打包';
                        $buttons = array_merge($buttons, ['canceldeliverystaff']);
                    }
                    if(isset($params['self_delivery_operator_id']) && !$params['self_delivery_operator_id']){
                        $buttons = array_merge($buttons, ['confirmdeliverystaff']);
                    }
                    $detail_status['icon'] = self::DETAIL_STATUS_ICON_PAYED_6;
                } else {
                    $list_status_mag = '已付款待发货';
                    $buttons = array_merge($buttons, ['contact', 'delivery', 'cancel']);
                    $detail_status['description'] = '买家已支付，请尽快发货';
                    $detail_status['icon'] = self::DETAIL_STATUS_ICON_PAYED_1;
                }
                break;
            case 'WAIT_BUYER_CONFIRM':
                // 已发货
                $status_info['main_status'] = 'shipping';
                if ($delivery_type == 'dada') {
                    $list_status_mag = '配送中';
                    $detail_status['description'] = '骑士正在送货…';
                    $detail_status['icon'] = self::DETAIL_STATUS_ICON_DELIVERY_1;
                }elseif ($delivery_type == 'merchant') {
                    if($params['self_delivery_status'] == 'CONFIRMING'){
                        $list_status_mag = '配送取消';
                        $buttons = array_merge($buttons, ['confirmdeliverystaff','updatedelivery']);
                        $detail_status['description'] = '商家正在重新分配配送员…';
                    }elseif($params['self_delivery_status'] == 'DONE'){
                        $list_status_mag = '已送达';
                    }else{
                        $list_status_mag = '配送中';
                        $buttons = array_merge($buttons, ['updatedelivery','canceldeliverystaff']);
                        $detail_status['description'] = '配送员正在送货…';
                    }
                    if(isset($params['self_delivery_operator_id']) && !$params['self_delivery_operator_id']){
                        $buttons = array_merge($buttons, ['confirmdeliverystaff']);
                    }
                    $detail_status['icon'] = self::DETAIL_STATUS_ICON_DELIVERY_2;
                } else {
                    $list_status_mag = '已发货待收货';
                    $detail_status['description'] = '商品配送中';
                    $detail_status['icon'] = self::DETAIL_STATUS_ICON_DELIVERY_2;
                }
                if ($params['order_status_des'] == 'PAYED_PARTAIL') {
                    // 部分发货的已发货状态也是可以发货
                    $buttons = array_merge($buttons, ['contact', 'delivery2']);
                } else {
                    $buttons = array_merge($buttons, ['contact']);
                }
                if ($params['left_aftersales_num'] > 0) {
                    $buttons = array_merge($buttons, ['aftersales']);
                }

                $detail_status_msg = '订单已发货，待收货';
                break;
            case 'DONE':
                // 已完成
                $status_info['main_status'] = 'finish';
                $list_status_mag = '已完成';
                $detail_status_msg = '订单已完成';
                $detail_status['description'] = '收货人已签收';
                $detail_status['icon'] = self::DETAIL_STATUS_ICON_SUCCESS_1;
                if ($params['end_time']) {
                    $terminal_info = [
                        'msg' => '完成时间',
                        'time' => $params['end_time'],
                    ];
                }
                if ($delivery_type == 'dada') {
                    if ($dada_status == '10') {
                        $status_info['child_status'] = 'dada_10';
                        $buttons = array_merge($buttons, ['contact']);
                        $list_status_mag = '未妥投';
                        $detail_status_msg = '订单已完成，未妥投';
                        $detail_status['description'] = '请联系收货人确认订单地址';
                        $detail_status['icon'] = self::DETAIL_STATUS_ICON_SUCCESS_2;
                        $terminal_info = [
                            'msg' => '未妥投时间',
                            'time' => $params['end_time'],
                        ];
                    }
                }elseif ($delivery_type == 'merchant') {
                    $list_status_mag = '已送达';
//                    $buttons = array_merge($buttons, ['aftersales']);
                    $detail_status['icon'] = self::DETAIL_STATUS_ICON_SUCCESS_2;
                }
                if ($params['order_auto_close_aftersales_time'] > time() && $params['left_aftersales_num'] > 0) {
                    $buttons = array_merge($buttons, ['aftersales']);
                }
                break;
        }
        $delivery_type_msg = '';
        $delivery_type_name = '';
        $order_class_name = '';
        switch ($params['order_class']) {
            case 'normal':
                $order_class_name = '普通订单';
                break;
            case 'groups':
                $order_class_name = '拼团订单';
                break;
            case 'community':
                $order_class_name = '社区活动订单';
                break;
            case 'bargain':
                $order_class_name = '助力订单';
                break;
            case 'seckill':
                $order_class_name = '秒杀订单';
                break;
            case 'shopguide':
                $order_class_name = '导购订单';
                break;
            case 'pointsmall':
                $order_class_name = '积分商城';
                break;
            case 'excard':
                $order_class_name = '兑换券订单';
                break;
        }
        switch ($delivery_type) {
            case 'dada':
                $delivery_type_msg = '商家同城配送';
                $delivery_type_name = '同城快递';
                break;
            case 'ziti':
                $delivery_type_msg = '门店自提';
                $delivery_type_name = '自提';
                break;
            case 'merchant':
                $delivery_type_msg = '商家自配';
                $delivery_type_name = '自配';
                break;
            default:
                $delivery_type_msg = '商家快递配送';
                $delivery_type_name = '普通快递';
                break;
        }
        $detail_status['main_msg'] = $detail_status_msg;
        $buttons = $this->getOrderButtons(...$buttons);
        return  compact(
            'status_info',
            'list_status_mag',
            'detail_status',
            'buttons',
            'delivery_type_name',
            'delivery_type_msg',
            'order_class_name',
            'terminal_info'
        );
    }

    public function getCancelFrom($params)
    {
        $cancelOrderRepository = app('registry')->getManager('default')->getRepository(CancelOrders::class);
        $cancelFilter = [
            'order_id' => $params['order_id'],
            'company_id' => $params['company_id'],
            'order_type' => $params['order_type'],
        ];
        $cancelOrder = $cancelOrderRepository->getInfo($cancelFilter);
        if ($cancelOrder) {
            return $cancelOrder['cancel_from'] ?? '';
        }
        return '';
    }

    private $order_buttons = [
        'cancel' =>
            ['type' => 'cancel', 'name' => '取消订单'],
        'contact' =>
            ['type' => 'contact', 'name' => '联系客户'],
        'mark' =>
            ['type' => 'mark', 'name' => '备注'],
        'confirmdeliverystaff' =>
            ['type' => 'confirmdeliverystaff', 'name' => '分配配送员'],
        'canceldeliverystaff' =>
            ['type' => 'canceldeliverystaff', 'name' => '取消配送'],
        'confirmpackag' =>
            ['type' => 'confirmpackag', 'name' => '已打包'],
        'delivery' =>
            ['type' => 'delivery', 'name' => '发货'],
        'delivery2' =>
            ['type' => 'delivery', 'name' => '部分发货：发货'],
        'updatedelivery' =>
            ['type' => 'updatedelivery', 'name' => '更新发货'],
        'consume' =>
            ['type' => 'consume', 'name' => '核销'],
        'accept' =>
            ['type' => 'accept', 'name' => '接单'],
        'confirmcancel' =>
            ['type' => 'confirmcancel', 'name' => '退款'],
        'markdown' =>
            ['type' => 'markdown', 'name' => '改价'],
        'aftersales' =>
            ['type' => 'aftersales', 'name' => '申请售后'],
        'payment' =>
            ['type' => 'payment', 'name' => '收款'],
    ];

    public function getOrderButtons(...$types)
    {
        // TODO: optimize this method
        $buttons = [];
        foreach ($types as $type) {
            $buttons[] = $this->order_buttons[$type];
        }
        return $buttons;
    }
}
