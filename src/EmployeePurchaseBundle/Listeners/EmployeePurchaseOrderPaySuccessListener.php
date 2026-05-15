<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace EmployeePurchaseBundle\Listeners;

use EmployeePurchaseBundle\Services\ActivityEnterpriseBehaviorLogService;
use OrdersBundle\Events\NormalOrderPaySuccessEvent;

/**
 * 普通订单支付成功：内购订单写入 activity_enterprise_behavior_log（order）
 */
class EmployeePurchaseOrderPaySuccessListener
{
    /**
     * @param NormalOrderPaySuccessEvent $event
     */
    public function handle($event): void
    {
        $data = $event->entities ?? [];
        $companyId = (int) ($data['company_id'] ?? 0);
        $orderId = $data['order_id'] ?? null;
        if ($companyId <= 0 || $orderId === null || $orderId === '') {
            return;
        }

        try {
            $service = new ActivityEnterpriseBehaviorLogService();
            $service->recordEmployeePurchaseOrderPaid($companyId, $orderId);
        } catch (\Throwable $e) {
            app('log')->warning('employee purchase order behavior log failed: '.$e->getMessage(), ['exception' => $e]);
        }
    }
}
