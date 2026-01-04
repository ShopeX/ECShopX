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

use OrdersBundle\Events\NormalOrderConfirmReceiptEvent;
use OrdersBundle\Services\InvoiceEndTimeService;

/**
 * 订单完成时更新发票结束时间监听器
 */
class OrderFinishInvoiceListener
{
    private $invoiceEndTimeService;

    public function __construct()
    {
        // 通过容器解析服务，而不是直接依赖注入
        $this->invoiceEndTimeService = app(InvoiceEndTimeService::class);
    }

    /**
     * 处理订单确认收货事件
     * @param NormalOrderConfirmReceiptEvent $event
     */
    public function handle(NormalOrderConfirmReceiptEvent $event)
    {
        $eventData = $event->entities;
        $companyId = $eventData['company_id'];
        $orderId = $eventData['order_id'];

        try {
            // 获取订单信息
            $normalOrderService = new \OrdersBundle\Services\Orders\NormalOrderService();
            $orderInfo = $normalOrderService->getOrderInfo($companyId, $orderId);
            if (!$orderInfo) {
                \app('log')->warning('[OrderFinishInvoiceListener] 订单信息不存在', [
                    'company_id' => $companyId,
                    'order_id' => $orderId
                ]);
                return true;
            }
            if( strtolower($orderInfo['orderInfo']['order_status']) != 'done'){
                \app('log')->warning('[OrderFinishInvoiceListener] 订单状态不是完成', [
                    'company_id' => $companyId,
                    'order_id' => $orderId,
                    'order_status' => $orderInfo['orderInfo']['order_status']
                ]);
                return true;
            }

            $endTime = $orderInfo['orderInfo']['end_time'] ?? time();
            $closeAftersalesTime = $orderInfo['orderInfo']['order_auto_close_aftersales_time'] ?? null;

            // 更新发票结束时间
            $result = $this->invoiceEndTimeService->updateInvoiceEndTime(
                $orderId, 
                $endTime, 
                $closeAftersalesTime
            );

            if ($result) {
                \app('log')->info('[OrderFinishInvoiceListener] 订单完成时更新发票结束时间成功', [
                    'company_id' => $companyId,
                    'order_id' => $orderId,
                    'end_time' => $endTime,
                    'close_aftersales_time' => $closeAftersalesTime
                ]);
            } else {
                \app('log')->error('[OrderFinishInvoiceListener] 订单完成时更新发票结束时间失败', [
                    'company_id' => $companyId,
                    'order_id' => $orderId
                ]);
            }
        } catch (\Exception $e) {
            \app('log')->error('[OrderFinishInvoiceListener] 处理订单完成事件异常', [
                'company_id' => $companyId,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
} 