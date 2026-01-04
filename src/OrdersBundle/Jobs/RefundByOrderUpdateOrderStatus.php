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

namespace OrdersBundle\Jobs;

use EspierBundle\Jobs\Job;

use OrdersBundle\Entities\Trade;
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Traits\GetPaymentServiceTrait;
use OrdersBundle\Entities\CancelOrders;
use AftersalesBundle\Services\AftersalesRefundService;
use OrdersBundle\Services\TradeService;

class RefundByOrderUpdateOrderStatus extends Job
{
    use GetOrderServiceTrait;
    use GetPaymentServiceTrait;

    // 开团id
    public $orderId = '';
    public $companyId = '';
    public $orderType = '';

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($orderId, $companyId, $orderType)
    {
        $this->orderId = $orderId;
        $this->companyId = $companyId;
        $this->orderType = $orderType;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        app('log')->debug('执行退款:order_id:'. $this->orderId);

        $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
        $info = $tradeRepository->getInfo(['company_id' => $this->companyId, 'order_id' => $this->orderId,'trade_state' => 'SUCCESS']);
        if (!$info) {
            app('log')->debug('交易流水单不是成功支付状态，不可进行退款操作'. var_export($info));
            return true;
        }

        $orderService = $this->getOrderService($this->orderType);
        $orderData = $orderService->getOrderInfo($this->companyId, $this->orderId);
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
            'refund_channel' => $trade['pay_type'] == 'offline_pay' ? 'offline' : 'original', // 默认取消订单原路返回,pay_type=offline_pay为线下退款
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
            'company_id' => $this->companyId,
            'order_id' => $this->orderId,
        ];
        $orderService = $this->getOrderService($this->orderType);
        $orderService->orderStatusUpdate($filter, 'CANCEL');

        // $orderInfo = $orderService->getOrderInfo($this->companyId, $this->orderId);
        // $orderService->groupOrderCancel($orderService, $orderInfo['orderInfo']);

        return true;
    }
}
