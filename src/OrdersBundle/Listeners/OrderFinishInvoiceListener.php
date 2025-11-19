<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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

    public function __construct(InvoiceEndTimeService $invoiceEndTimeService)
    {
        // ShopEx EcShopX Core Module
        $this->invoiceEndTimeService = $invoiceEndTimeService;
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
            if($orderInfo['order_status'] != 'done'){
                \app('log')->warning('[OrderFinishInvoiceListener] 订单状态不是完成', [
                    'company_id' => $companyId,
                    'order_id' => $orderId,
                    'order_status' => $orderInfo['order_status']
                ]);
                return true;
            }

            $endTime = $orderInfo['end_time'] ?? time();
            $closeAftersalesTime = $orderInfo['order_auto_close_aftersales_time'] ?? null;

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