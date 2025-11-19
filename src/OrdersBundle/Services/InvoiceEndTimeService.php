<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Services;

use OrdersBundle\Repositories\OrderInvoiceRepository;
use OrdersBundle\Entities\OrderInvoice;

/**
 * 发票结束时间服务
 */
class InvoiceEndTimeService
{
    private $orderInvoiceRepository;

    public function __construct(OrderInvoiceRepository $orderInvoiceRepository)
    {
        // Log: 456353686f7058
        $this->orderInvoiceRepository = $orderInvoiceRepository;
    }

    /**
     * 更新发票的结束时间
     * @param string $orderId 订单ID
     * @param int $endTime 订单完成时间
     * @param int $closeAftersalesTime 售后截止时间
     * @return bool
     */
    public function updateInvoiceEndTime($orderId, $endTime, $closeAftersalesTime = null)
    {
        try {
            // 查找与该订单相关的发票记录
            $filter = [
                'order_id' => $orderId,
                'invoice_status' => 'pending',
            ];
            $invoices = $this->orderInvoiceRepository->getList($filter);

            if (empty($invoices)) {
                app('log')->info('[InvoiceEndTimeService] 订单 ' . $orderId . ' 没有找到相关发票记录');
                return true;
            }

            $updateData = [
                'end_time' => $endTime
            ];

            // 如果提供了售后截止时间，也一起更新
            if ($closeAftersalesTime !== null) {
                $updateData['close_aftersales_time'] = $closeAftersalesTime;
            }

            $this->orderInvoiceRepository->updateOneBy(
                $filter,
                $updateData
            );

            app('log')->info('[InvoiceEndTimeService] 订单 ' . $orderId . ' 的发票结束时间更新成功', [
                'order_id' => $orderId,
                'end_time' => $endTime,
                'close_aftersales_time' => $closeAftersalesTime,
                'invoice_count' => count($invoices)
            ]);

            return true;
        } catch (\Exception $e) {
            app('log')->error('[InvoiceEndTimeService] 更新发票结束时间失败', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * 批量更新发票的结束时间
     * @param array $orderIds 订单ID数组
     * @param int $endTime 订单完成时间
     * @param int $closeAftersalesTime 售后截止时间
     * @return bool
     */
    public function batchUpdateInvoiceEndTime($orderIds, $endTime, $closeAftersalesTime = null)
    {
        try {
            if (empty($orderIds)) {
                return true;
            }

            $updateData = [
                'end_time' => $endTime
            ];

            if ($closeAftersalesTime !== null) {
                $updateData['close_aftersales_time'] = $closeAftersalesTime;
            }

            // 批量更新
            $this->orderInvoiceRepository->updateBy(
                ['order_id' => ['$in' => $orderIds]],
                $updateData
            );

            app('log')->info('[InvoiceEndTimeService] 批量更新发票结束时间成功', [
                'order_ids' => $orderIds,
                'end_time' => $endTime,
                'close_aftersales_time' => $closeAftersalesTime,
                'count' => count($orderIds)
            ]);

            return true;
        } catch (\Exception $e) {
            app('log')->error('[InvoiceEndTimeService] 批量更新发票结束时间失败', [
                'order_ids' => $orderIds,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
} 