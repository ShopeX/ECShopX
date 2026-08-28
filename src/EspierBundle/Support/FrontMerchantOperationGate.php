<?php

declare(strict_types=1);

namespace EspierBundle\Support;

use CompanysBundle\Services\EmployeeService;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Front API merchant-only operation gate (self-delivery staff linkage).
 */
class FrontMerchantOperationGate
{
    /**
     * @param array<string, mixed> $auth
     */
    public static function assertSelfDeliveryStaffOrForbidden(array $auth, ?EmployeeService $employeeService = null): void
    {
        $employeeService = $employeeService ?? new EmployeeService();

        $companyId = (int) ($auth['company_id'] ?? 0);
        $mobile = (string) ($auth['mobile'] ?? '');
        if ($companyId <= 0 || $mobile === '') {
            throw new HttpException(403, '无权执行此操作');
        }

        $deliveryStaffList = $employeeService->getListStaff([
            'company_id' => $companyId,
            'mobile' => $mobile,
            'operator_type' => 'self_delivery_staff',
        ]);
        if ($deliveryStaffList) {
            return;
        }

        throw new HttpException(403, '无权执行此操作');
    }
}
