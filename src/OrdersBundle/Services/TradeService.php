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

namespace OrdersBundle\Services;

// use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Interfaces\Trade as InterfacesTrade;
use OrdersBundle\Entities\CancelOrders;
use OrdersBundle\Entities\Trade;
use OrdersBundle\Events\TradeFinishEvent;
use OrdersBundle\Jobs\RefundByOrderUpdateOrderStatus;
use OrdersBundle\Jobs\RefundTrade;
// use OrdersBundle\Repositories\NormalOrdersRepository;
// use OrdersBundle\Repositories\TradeRepository;
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Traits\GetUserIdByMobileTrait;

use PaymentBundle\Services\PaymentsService;
use PaymentBundle\Services\Payments\AlipayService;
use PaymentBundle\Services\Payments\WechatPayService;

use AftersalesBundle\Services\AftersalesRefundService;

use CompanysBundle\Traits\GetDefaultCur;

use DistributionBundle\Services\DistributorService;
use SystemLinkBundle\Events\WdtErp\TradeFinishEvent as WdtErpTradeFinishEvent;
use SystemLinkBundle\Events\Jushuitan\TradeFinishEvent as JushuitanTradeFinishEvent;
use CompanysBundle\Entities\Operators;

class TradeService implements InterfacesTrade
{
    use GetUserIdByMobileTrait;
    use GetDefaultCur;
    use GetOrderServiceTrait;

    /** @var \OrdersBundle\Repositories\TradeRepository */
    public $tradeRepository;

    public function __construct()
    {
        $this->tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
    }

    /**
     * 获取用户交易ID
     *
     * $userId
     */
    public function genTradeId($userId)
    {
        $time = time();
        $startTime = 1325347200;//2012-01-01 做为初始年
        //当前时间相距初始年的天数，4位可使用20年
        $day = floor(($time - $startTime) / 86400);

        //确定每90秒的的订单生成 一天总共有960个90秒，控制在三位
        $minute = floor(($time - strtotime(date('Y-m-d'))) / 90);

        //防止通过订单号计算出商城生成的订单数量，导致泄漏关键数据
        $redisId = app('redis')->hincrby(date('Ymd'), $minute, rand(1, 9));

        //设置过期时间
        app('redis')->expire(date('Ymd'), 86400);

        $id = $day . str_pad($minute, 3, '0', STR_PAD_LEFT) . str_pad($redisId, 5, '0', STR_PAD_LEFT) . str_pad($userId % 10000, 4, '0', STR_PAD_LEFT);//16位

        return $id;
    }

    /**
     * 创建交易单
     */
    public function create(array $data, $isDiscount = false)
    {
        $discountService = new DiscountService();
        if ($isDiscount) {
            $countDiscount = $discountService->discount($data);
            $data['pay_fee'] = $countDiscount['pay_fee'] >= 0 ? intval($countDiscount['pay_fee']) : 0;
            $data['discount_fee'] = isset($countDiscount['discount_fee']) ? intval($countDiscount['discount_fee']) : 0;
            $data['discount_info'] = isset($countDiscount['discount_info']) ? json_encode($countDiscount['discount_info']) : null;
        } else {
            $data['discount_fee'] = isset($data['discount_fee']) ? intval($data['discount_fee']) : 0;
            $data['discount_info'] = isset($data['discount_info']) ? json_encode($data['discount_info']) : null;
        }
        unset($data['member_card_code']);
        unset($data['coupon_code']);
        unset($data['poiid']);

        $data['cur_pay_fee'] = $data['pay_fee'];
        if (substr($data['pay_type'], 0, 5) == 'wxpay' || substr($data['pay_type'], 0, 6) == 'alipay') {
            if (isset($data['fee_rate']) && $data['fee_rate']) {
                $rate = round(floatval($data['fee_rate']), 4);
                $data['cur_fee_rate'] = $rate;
                $data['cur_fee_symbol'] = isset($data['fee_symbol']) ? $data['fee_symbol'] : '';
                $data['cur_fee_type'] = isset($data['fee_type']) ? $data['fee_type'] : '';
                $data['pay_fee'] = round($data['pay_fee'] * $rate);
            } else {
                $cur = $this->getCur($data['company_id']);
                if (isset($cur['rate']) && $cur['rate']) {
                    $rate = round(floatval($cur['rate']), 4);
                    $data['cur_fee_rate'] = $rate;
                    $data['cur_fee_symbol'] = isset($cur['symbol']) ? $cur['symbol'] : '';
                    $data['cur_fee_type'] = isset($cur['currency']) ? $cur['currency'] : '';
                    $data['pay_fee'] = round($data['pay_fee'] * $rate);
                }
            }
        }
        unset($data['fee_rate'], $data['fee_symbol'], $data['fee_type']);

        //若设置了店铺号，则在产生的交易单拼接店铺号
        if ($data['distributor_id'] ?? 0) {
            $distributorService = new DistributorService();
            $distributorInfo = $distributorService->getInfo(['distributor_id' => $data['distributor_id'], 'company_id' => $data['company_id']]);
            $data['dealer_id'] = $distributorInfo['dealer_id'];
            $data['merchant_id'] = $distributorInfo['merchant_id'] ?? 0;
        }
        if ($distributorInfo['shop_code'] ?? 0) {
            $data['trade_id'] = $distributorInfo['shop_code'].$this->genTradeId($data['user_id']);
        } else {
            $data['trade_id'] = $this->genTradeId($data['user_id']);
        }
        $data = $this->tradeRepository->create($data);

        //如果为0元订单，直接支付成功
        if ($data['pay_fee'] === 0 && !($data['is_create_prescription_order'] ?? 0)) { // 处方药订单创建完订单后不支付，需补充处方信息)
            $this->updateStatus($data['trade_id'], 'SUCCESS', ['pay_type' => 'localPay']);
            $data['pay_status'] = true;
        }

        return $data;
    }

    /**
     * 更新交易单状态
     */
    public function updateStatus($tradeId, $status = null, $options = array())
    {
        $data = $this->tradeRepository->updateStatus($tradeId, $status, $options);
        app('log')->debug('updateStatus data:'.json_encode($data, 256));
        if ($status == 'SUCCESS') {
            // $data->setTradeState('SUCCESS');
            app('log')->debug("saaserp ".__FUNCTION__.",".__LINE__.", 交易完成 去交易完成事件 埋点");
            $this->finishEvents($data);
        }
        return $data;
    }

    /**
     * 对订单进行退款，并且修改状态
     * @param $tradeId
     * @param $companyId
     * @return bool
     */
    public function refundStatus($orderId, $companyId, $orderType)
    {
        $job = (new RefundByOrderUpdateOrderStatus($orderId, $companyId, $orderType))->delay(5);
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        return true;
    }

    /**
     * 拼团取消订单
     * @param $orderId
     * @param $companyId
     * @param $orderType
     * @return bool
     * @throws \Exception
     */
    public function refundStatusRightNow($orderId, $companyId, $orderType)
    {
        app('log')->debug('执行退款:order_id:'. $orderId);

        $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
        $info = $tradeRepository->getInfo(['company_id' => $companyId, 'order_id' => $orderId,'trade_state' => 'SUCCESS']);
        if (!$info) {
            app('log')->debug('交易流水单不是成功支付状态，不可进行退款操作'. var_export($info));
            return true;
        }

        $orderService = $this->getOrderService($orderType);
        $orderData = $orderService->getOrderInfo($companyId, $orderId);
        $orderInfo = $orderData['orderInfo'];

        // 创建取消订单记录
        $cancelData = [
            'company_id' => $orderInfo['company_id'],
            'user_id' => $orderInfo['user_id'],
            'order_id' => $orderInfo['order_id'],
            'distributor_id' => $orderInfo['distributor_id'],
            'order_type' => $orderInfo['order_type'],
            'refund_status' => 'AUDIT_SUCCESS', // 审核成功
            'progress' => 2, // 2 处理中
            'total_fee' => $orderInfo['total_fee'],
            'point' => $orderInfo['point'] ?? 0,
            'pay_type' => $orderInfo['pay_type'] ?? '',
            'cancel_from' => 'shop',
            'cancel_reason' => '拼团自动取消',
            'payed_fee' => $orderInfo['total_fee'],
        ];
        $cancelOrderRepository = app('registry')->getManager('default')->getRepository(CancelOrders::class);
        $cancelOrder = $cancelOrderRepository->create($cancelData);
        // 生成退款单，不实际退款
        $tradeService = new TradeService();
        $trade = $tradeService->getInfo(['company_id' => $orderInfo['company_id'], 'order_id' => $orderInfo['order_id'], 'trade_state' => 'SUCCESS']);
        $aftersalesRefundService = new AftersalesRefundService();
        $refundData = [
            'company_id' => $orderInfo['company_id'],
            'user_id' => $orderInfo['user_id'],
            'order_id' => $orderInfo['order_id'],
            'trade_id' => $trade['trade_id'],
            'shop_id' => $orderInfo['shop_id'] ?? 0,
            'distributor_id' => $orderInfo['distributor_id'] ?? 0,
            'refund_type' => 1, // 1:取消订单退款,
            'refund_channel' => 'original', // 默认取消订单原路返回
            'refund_status' => 'AUDIT_SUCCESS', // 售前取消订单退款默认审核成功
            'refund_fee' => $trade['total_fee'],
            'refund_point' => $orderInfo['point'],
            'return_freight' => 1, // 1:退运费,
            'pay_type' => $orderInfo['pay_type'], // 退款支付方式
            'currency' => ($trade['pay_type'] == 'point') ? '' : $trade['fee_type'],
            'cur_fee_type' => ($trade['pay_type'] == 'point') ? '' : $trade['cur_fee_type'],
            'cur_fee_rate' => $trade['cur_fee_rate'],
            'cur_fee_symbol' => ($trade['pay_type'] == 'point') ? '' : $trade['cur_fee_symbol'],
            'cur_pay_fee' => ($trade['pay_type'] == 'point') ? $orderInfo['point'] : $trade['cur_pay_fee'], // trade表没有单独积分字段，所以这样写
        ];
        $refund = $aftersalesRefundService->createRefund($refundData);

        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
        ];
        $orderService = $this->getOrderService($orderType);
        $orderService->orderStatusUpdate($filter, 'CANCEL');

        // $orderInfo = $orderService->getOrderInfo($this->companyId, $this->orderId);
        // $orderService->groupOrderCancel($orderService, $orderInfo['orderInfo']);

        return true;
    }




    /**
     * 对交易的进行退款处理
     */
    public function refundTrade($tradeId, $isQueue = true)
    {
        if ($isQueue) {
            $job = (new RefundTrade($tradeId))->delay(5);
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
            $data['status'] = 'SUCCESS';
        } else {
            $info = $this->tradeRepository->getInfo(['trade_id' => $tradeId]);
            if (!$info) {
                app('log')->debug('处理的退款申请不存在:trade_id'. $tradeId);
                return true;
            }

            if ($info['trade_state'] != 'SUCCESS') {
                app('log')->debug('交易流水单不是成功支付状态，不可进行退款操作'. var_export($info));
                return true;
            }

            //将退款状态设置为处理中
            $this->tradeRepository->updateOneBy(['trade_id' => $tradeId], ['trade_state' => 'REFUND_PROCESS']);

            $data = [];

            if (substr($info['pay_type'], 0, 5) == 'wxpay') {

                // 微信支付
                $paymentsService = new PaymentsService(new WechatPayService($info['distributor_id']));
                $data = $paymentsService->doRefund($info['company_id'], $info['wxa_appid'], $info);

                if ($data['status'] == 'SUCCESS') {
                    $trade = $this->tradeRepository->updateOneBy(['trade_id' => $info['trade_id']], ['trade_state' => 'REFUND_SUCCESS']);
                }
            } elseif (substr($info['pay_type'], 0, 6) == 'alipay') {
                // 微信支付
                $paymentsService = new PaymentsService(new AlipayService($info['distributor_id']));
                $data = $paymentsService->doRefund($info['company_id'], $info['wxa_appid'], $info);

                if ($data['status'] == 'SUCCESS') {
                    $trade = $this->tradeRepository->updateOneBy(['trade_id' => $info['trade_id']], ['trade_state' => 'REFUND_SUCCESS']);
                }
            }
        }

        return $data;
    }

    /**
     * 交易完成处理事件
     */
    public function finishEvents($eventsParams)
    {
        app('log')->debug("saaserp ".__FUNCTION__.",".__LINE__.",交易完成处理事件 埋点");
        event(new JushuitanTradeFinishEvent($eventsParams));
        event(new TradeFinishEvent($eventsParams));
        event(new WdtErpTradeFinishEvent($eventsParams));
    }

    /**
     * 获取交易列表
     */
    public function getTradeList($filter, $orderBy = ['time_start' => 'DESC'], $pageSize = 20, $page = 1)
    {
        $filter = $this->checkMobile($filter);
        $tradeList = $this->tradeRepository->getTradeList($filter, $orderBy, $pageSize, $page);
        if ($tradeList['total_count'] > 0) {
            //提取所有订单号
            $orderIds = array_column($tradeList['list'], 'orderId');
            $orderService = $this->getOrderService('normal');
            $companyId = $filter['company_id'];
            unset($filter);
            $filter['company_id'] = $companyId;
            $filter['order_id'] = $orderIds;
            $orderList = $orderService->getOrderList($filter, 1,  count($orderIds), [], false);
            $orderListData = array_column($orderList['list'], null, 'order_id');

            $selfDeliveryOperatorIds = array_column($orderList['list'], 'self_delivery_operator_id', 'order_id');
            $selfDeliveryOperator = [];
            if ($selfDeliveryOperatorIds) {
                $operatorsRepository = app('registry')->getManager('default')->getRepository(Operators::class);
                $selfDeliveryOperator = $operatorsRepository->lists(['operator_id' => $selfDeliveryOperatorIds]);
                $selfDeliveryOperator = array_column($selfDeliveryOperator['list'], null, 'operator_id');
            }
        }
        foreach ($tradeList['list'] as &$value) {
            if (isset($orderListData[$value['orderId']])) {
                $value['receipt_type'] = $orderListData[$value['orderId']]['receipt_type'];
                $value['order_holder'] = $orderListData[$value['orderId']]['order_holder'];
                $value['self_delivery_fee'] = $orderListData[$value['orderId']]['self_delivery_fee'] ?? 0;
                if (isset($selfDeliveryOperatorIds[$value['orderId']]) && $selfDeliveryOperatorIds[$value['orderId']] > 0) {
                    $value['self_delivery_operator_id'] = $selfDeliveryOperatorIds[$value['orderId']] ?? 0;
                    $value['self_delivery_operator_mobile'] = $selfDeliveryOperator[$value['self_delivery_operator_id']]['mobile'] ?? '';
                    $value['self_delivery_operator_name'] = $selfDeliveryOperator[$value['self_delivery_operator_id']]['username'] ?? '';
                }
            }
        }
        return $tradeList;
    }

    public function getTradeCount($filter)
    {
        $filter = $this->checkMobile($filter);
        return $this->tradeRepository->getTradeCount($filter);
    }


    public function getOrderTradeInfo($filter = [])
    {
        return $this->tradeRepository->getTradeByOrderIds($filter);
    }

    public function getTodayTradeNo($companyId, $distributorId, $orderId)
    {
        $today = date('md');
        $key = 'h_trade_no_' . $companyId . '_' . $distributorId . '_' . $today;
        $countKey = 'c_trade_no_' . $companyId . '_' . $distributorId . '_' . $today;
        $countNum = app('redis')->hget($key, $orderId);
        if (!$countNum) {
            $countNum = app('redis')->incr($countKey);
            app('redis')->hset($key, $orderId, $countNum);
            app('redis')->expire($key, 86400);
            app('redis')->expire($countKey, 86400);
        }
        return $countNum;
    }

    public function getTradeIndexByOrderIdList($companyId, $orderIdList)
    {
        if (empty($orderIdList)) {
            return [];
        }
        $filter = [
          'company_id' => $companyId,
          'order_id' => $orderIdList,
          'trade_state' => 'SUCCESS',
        ];

        $tradeList = $this->tradeRepository->getTradeByOrderIds($filter);

        $indexTrade = [];
        foreach ($tradeList as $value) {
            $indexTrade[$value['order_id']] = $value['trade_no'] && $value['trade_no'] != '0' ? $value['trade_no'] : '-';
        }

        return $indexTrade;
    }

    public function getSuccTradeInfoByOrderIdList($companyId, $orderIdList)
    {
        if (empty($orderIdList)) {
            return [];
        }
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderIdList,
            'trade_state' => 'SUCCESS',
        ];

        $tradeList = $this->tradeRepository->getTradeByOrderIds($filter);

        $indexTrade = [];
        foreach ($tradeList as $value) {
            $indexTrade[$value['order_id']] = $value;
        }

        return $indexTrade;
    }

    public function getSuccTradeByOrder($companyId, $orderId)
    {
        $filter = [
          'company_id' => $companyId,
          'order_id' => $orderId,
          'trade_state' => 'SUCCESS',
        ];

        $tradeInfo = $this->tradeRepository->getInfo($filter);
        if (empty($tradeInfo)) {
            return [];
        }
        $tradeInfo['pay_time'] = date('Y-m-d H:i:s', $tradeInfo['time_expire']);
        return $tradeInfo;
    }

    /**
     * Dynamically call the KaquanService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->tradeRepository->$method(...$parameters);
    }
}
