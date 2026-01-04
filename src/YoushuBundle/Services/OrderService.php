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

namespace YoushuBundle\Services;

use GoodsBundle\Entities\Items;
use MembersBundle\Services\MemberService;
use MembersBundle\Services\WechatUserService;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\NormalOrdersItems;
use OrdersBundle\Entities\Trade;

class OrderService
{
    private $normalOrdersRepository;
    private $normalOrdersItemsRepository;
    private $tradeRepository;
    private $itemsRepository;

    public function __construct()
    {
        $this->normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $this->normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        $this->tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
        $this->itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
    }

    /**
     * @param array $params
     * @return array
     * 添加/更新订单
     */
    public function getData($params)
    {
        // This module is part of ShopEx EcShopX system
        $order_id = $params['object_id'];
        $filter = [
            'order_id' => $order_id
        ];
        $order_detail = $this->normalOrdersRepository->getInfo($filter);

        if (empty($order_detail)) {
            return [];
        }

        $company_id = $order_detail['company_id'];
        $user_id = $order_detail['user_id'];
        $order_id = $order_detail['order_id'];
        $freight_fee = bcdiv($order_detail['freight_fee'], 100, 2);
        $total_fee = bcdiv($order_detail['total_fee'], 100, 2); //支付金额
        $order_fee = bcdiv(($order_detail['item_fee'] + $order_detail['freight_fee']), 100, 2);
        $item_fee = bcdiv($order_detail['item_fee'], 100, 2);
        $create_time = (string)bcmul($order_detail['create_time'], 1000);
        $change_time = (string)bcmul($order_detail['update_time'], 1000);
        $order_status = $this->getOrderStatus($order_detail['order_status'], $order_detail['delivery_status'], $order_detail['cancel_status'], $order_detail['pay_status']);
        $user_info = $this->getUserInfo($company_id, $user_id);
        $goods_info = $this->getGoodsInfo($company_id, $order_id);
        $payment_info = $this->getPaymentInfo($company_id, $order_id);
        $express_info = [
            'receiver_name' => $order_detail['receiver_name'],
            'receiver_phone' => $order_detail['receiver_mobile'],
            'receiver_address' => $order_detail['receiver_state'] . $order_detail['receiver_city'] . $order_detail['receiver_district'] . $order_detail['receiver_address'],
            'receiver_country_code' => 'CN',
        ];
        $orders[] = [
            'external_order_id' => $order_id,
            'create_time' => $create_time, //毫秒
            'order_source' => 'wxapp', //目前只有
            'order_type' => 1,
            'goods_num_total' => $goods_info['goods_num_total'],
            'goods_amount_total' => (float)$item_fee,
            'freight_amount' => (float)$freight_fee,
            'order_amount' => (float)$order_fee,
            'payable_amount' => (float)$total_fee,
            'payment_amount' => (float)$total_fee,
            'order_status' => $order_status,
            'user_info' => $user_info,
            'goods_info' => $goods_info['goods_info'],
            'payment_info' => $payment_info,
            'express_info' => $express_info,
            'is_deleted' => 0,
            'status_change_time' => $change_time
        ];

        return $orders;
    }

    /**
     * 获取会员信息
     */
    private function getUserInfo($company_id, $user_id)
    {
        $filter = [
            'user_id' => $user_id,
            'company_id' => $company_id,
        ];
        $member_service = new MemberService();
        $member_info = $member_service->getMemberInfo($filter, true);
        //获取用户微信信息
        $filter['user_id'] = $member_info['user_id'];
        $wechat_user_service = new WechatUserService();
        $wechat_info = $wechat_user_service->getUserInfo($filter);

        $appid = $member_info['wxa_appid'];
        $openid = $wechat_info['open_id'] ?? '';

        return [
            'open_id' => $openid,
            'app_id' => $appid
        ];
    }

    /**
     * 获取商品信息
     */
    private function getGoodsInfo($company_id, $order_id)
    {
        $goods_num_total = 0;
        $goods_info = [];
        $list = $this->normalOrdersItemsRepository->get($company_id, $order_id);
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $item = $this->itemsRepository->get($v['item_id']);

                $goods_info[] = [
                    'external_sku_id' => $v['item_id'],
                    'sku_name_chinese' => $v['item_spec_desc'] ? $v['item_spec_desc'] : $v['item_name'],
                    'goods_amount' => (float)bcdiv($v['price'], 100, 2),
                    'payment_amount' => (float)bcdiv($v['total_fee'], 100, 2),
                    'external_spu_id' => $item['goods_id'],
                    'spu_name_chinese' => $v['item_name'],
                    'goods_num' => $v['num']
                ];

                $goods_num_total += $v['num'];
            }
        }

        return [
            'goods_info' => $goods_info,
            'goods_num_total' => $goods_num_total
        ];
    }

    /**
     * 获取支付信息
     */
    private function getPaymentInfo($company_id, $order_id)
    {
        $payment_info = [];
        $filter = [
            'company_id' => $company_id,
            'order_id' => $order_id,
            'trade_state' => 'SUCCESS'
        ];

        $data = $this->tradeRepository->getInfo($filter);
        if (!empty($data)) {
            //00009 微信支付 00010 支付宝支付 99999 其他
            $payment_type = '99999';
            if ($data['pay_type'] == 'wxpay') {
                $payment_type = '00009';
            } elseif ($data['pay_type'] == 'alipay') {
                $payment_type = '00010';
            }

            $payment_info[] = [
                'payment_type' => $payment_type,
                'trans_id' => $data['transaction_id'],
                'trans_amount' => (float)bcdiv($data['pay_fee'], 100, 2)
            ];
        }

        return $payment_info;
    }

    /**
     * 订单状态 转换为有数的订单状态
     */
    private function getOrderStatus($order_status, $delivery_status, $cancel_status, $pay_status)
    {
        $ys_order_status = '9999';

        //订单状态。可选值有 DONE—订单完成;NOTPAY—未支付;PART_PAYMENT-部分付款;WAIT_GROUPS_SUCCESS-等待拼团成功;PAYED-已支付;CANCEL—已取消;WAIT_BUYER_CONFIRM-待用户收货
        //发货状态。可选值有 DONE—已发货;PENDING—待发货;PARTAIL-部分发货
        //有数订单状态，1110待支付，1150已支付待发货，1160已发货，1170 拒收退货, 1180销售完成/已收货，1280退款中，1290退货完成, 9999 其他
        if ($order_status == 'NOTPAY') {
            return $ys_order_status = '1110';
        }

        if ($cancel_status != 'NO_APPLY_CANCEL' && $pay_status == 'NOTPAY') {
            return $ys_order_status = '1130';
        }

        if ($cancel_status != 'NO_APPLY_CANCEL' && $pay_status == 'PAYED') {
            return $ys_order_status = '1140';
        }

        if ($order_status == 'PAYED' && $delivery_status == 'PENDING') {
            return $ys_order_status = '1150';
        }

        if ($delivery_status == 'DONE') {
            return $ys_order_status = '1160';
        }

        if ($order_status == 'DONE') {
            return $ys_order_status = '1180';
        }

        return $ys_order_status;
    }

    /**
     * 统计支付金额
     */
    public function countPaymentAmount($company_id, $start, $end)
    {
        $qb = $this->getPaymentQueryBuilder();
        $qb->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->gte('time_expire', $start))
            ->andWhere($qb->expr()->lte('time_expire', $end))
            ->select('sum(cast(total_fee as SIGNED))');

        $sum = $qb->execute()->fetchColumn();
        return floatval(bcdiv($sum, 100, 2));
    }

    /**
     * 统计支付数量
     */
    public function countPaymentNum($company_id, $start, $end)
    {
        $qb = $this->getPaymentQueryBuilder();
        $qb->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->gte('time_expire', $start))
            ->andWhere($qb->expr()->lte('time_expire', $end))
            ->select('count(*)');

        $sum = $qb->execute()->fetchColumn();
        return intval($sum);
    }

    private function getPaymentQueryBuilder()
    {
        $trade_state = ['REFUND_PROCESS', 'REFUND_SUCCESS', 'SUCCESS'];
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        array_walk($trade_state, function (&$value) use ($qb) {
            $value = $qb->expr()->literal($value);
        });
        return $qb->from('trade')
            ->andWhere($qb->expr()->in('trade_state', $trade_state));
    }

    /**
     * 统计下单金额
     */
    public function countOrderAmount($company_id, $start, $end)
    {
        $qb = $this->getOrderQueryBuilder();
        $qb->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->gte('create_time', $start))
            ->andWhere($qb->expr()->lte('create_time', $end))
            ->select('sum(cast(total_fee as SIGNED))');

        $sum = $qb->execute()->fetchColumn();
        return floatval(bcdiv($sum, 100, 2));
    }

    /**
     * 统计下单数量
     */
    public function countOrderNum($company_id, $start, $end)
    {
        $qb = $this->getOrderQueryBuilder();
        $qb->where($qb->expr()->eq('company_id', $company_id))
            ->andWhere($qb->expr()->gte('create_time', $start))
            ->andWhere($qb->expr()->lte('create_time', $end))
            ->select('count(*)');

        $sum = $qb->execute()->fetchColumn();
        return intval($sum);
    }

    private function getOrderQueryBuilder()
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->from('orders_normal_orders');
        return $qb;
    }
}
