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

namespace OrdersBundle\Listeners;

use OrdersBundle\Events\NormalOrderPaySuccessEvent;
use OrdersBundle\Events\TradeFinishEvent;
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Services\OrderAssociationService;
use DataCubeBundle\Services\TrackService;
use OrdersBundle\Services\TradeService;
use OrdersBundle\Events\OrderProcessLogEvent;

use MembersBundle\Services\MemberService;
use PointBundle\Services\PointMemberService;

class UpdateOrderStatusListener
{
    use GetOrderServiceTrait;

    /**
     * Handle the event.
     *
     * @param  TradeFinishEvent  $event
     * @return void
     */
    public function handle(TradeFinishEvent $event)
    {
        $orderId = $event->entities->getOrderId();
        $order_prefix = mb_substr($orderId, 0, 2);

        return $this->handle_common_order($event);
    }

    private function handle_common_order($event)
    {
        app('log')->debug('订单号更新：' . json_encode($event, 256));
        $userId = $event->entities->getUserId();
        $companyId = $event->entities->getCompanyId();
        $orderId = $event->entities->getOrderId();
        $payType = $event->entities->getPayType();
        $tradeId = $event->entities->getTradeId();
        // 会员小程序直接买单 只有支付单 没有订单
        if (!$orderId) {
            return false;
        }

        $paymentstate = $event->entities->getTradeState();
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $orderId);
        if (!$order) {
            // 后续改为Sentry抛错
            app('log')->debug('订单号'.$orderId.' 不存在');
            return false;
        }

        if (! in_array($order['order_status'], ['NOTPAY', 'PART_PAYMENT'])) {
            $orderService = $this->getOrderService($order['order_type']);
            try {
                // 未被取消的订单，直接取消
                if ($order['order_status'] != 'CANCEL') {
                    $params = [
                        'company_id' => $order['company_id'],
                        'order_id' => $order['order_id'],
                        'user_id' => $order['user_id'],
                        'mobile' => $order['mobile'],
                        'cancel_from' => 'system', //用户取消订单
                        'cancel_reason' => '',
                        'other_reason' => '订单异常退款'
                    ];
                    $result = $orderService->cancelOrder($params);
                    // 去退款
                    $params = [
                        'company_id' => $order['company_id'],
                        'order_id' => $order['order_id'],
                        'order_type' => $order['order_type'],
                        'check_cancel' => '1',
                        'shop_reject_reason' => '',
                        'operator_type' => 'system',
                        'operator_id' => '0',
                    ];
                    $result = $orderService->confirmCancelOrder($params);
                } else {
                    $params = [
                        'company_id' => $order['company_id'],
                        'order_id' => $order['order_id'],
                        'user_id' => $order['user_id'],
                    ];
                    $orderService->systemCreateRefund($params);
                }
            } catch (\Exception $e) {
                app('log')->debug('已取消订单自动退款报错：'.$e->getMessage());
                app('log')->debug('已取消订单自动退款报错：'.$e->getFile().' '.$e->getLine());
            }
            //$tradeService = new TradeService();
            //$tradeService->refundTrade($tradeId);
            return false;
        }

        $orderService = $this->getOrderServiceByOrderInfo($order);

        try {
            // 交易但支付成功
            if ($paymentstate == 'SUCCESS') {
                $orderService->tradeSuccUpdateOrderStatus($order, $payType);
                //临时解决会员消费记录筛选
                $membersService = new MemberService();
                $memFilter['user_id'] = $userId;
                $params['have_consume'] = true;
                $membersService->memberInfoUpdate($params, $memFilter);

                // $orderData = $orderService->getOrderInfo($order['company_id'], $order['order_id'])['orderInfo'];
                // //扣减积分
                // if ($orderData['point_use'] && $orderData['pay_type'] != 'point') {
                //     app('log')->debug('订单使用了积分 order_id:'.$orderData['order_id']);
                //     $pointMemberService = new PointMemberService();
                //     $pointMemberService->addPoint($orderData['user_id'], $orderData['company_id'], $orderData['point_use'], 6, false, '购物扣减积分', $orderData['order_id']);
                // }

                $trackService = new TrackService();
                $trackParams = [
                    'monitor_id' => $order['monitor_id'],
                    'company_id' => $companyId,
                    'source_id' => $order['source_id']
                ];
                $trackService->addEntriesNum($trackParams);

                $orderProcessLog = [
                    'order_id' => $orderId,
                    'company_id' => $companyId,
                    'operator_type' => 'system',
                    'is_show' => true,
                    'remarks' => '订单支付',
                    'detail' => '订单号：' . $orderId . '，订单支付成功',
                ];
                event(new OrderProcessLogEvent($orderProcessLog));

                $eventData = [
                    'company_id' => $companyId,
                    'order_id' => $order['order_id']
                ];
                event(new NormalOrderPaySuccessEvent($eventData));

                return true;
            }
        } catch (\Exception $e) {
            app('log')->debug('订单号:'.$orderId.', 状态更新错误: '.$e->getMessage());
        }
        return true;
    }
}
