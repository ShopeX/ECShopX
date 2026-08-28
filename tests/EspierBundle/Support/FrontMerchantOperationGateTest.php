<?php

declare(strict_types=1);

namespace Tests\EspierBundle\Support;

use CompanysBundle\Services\EmployeeService;
use EspierBundle\Support\FrontMerchantOperationGate;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * codex-security-02-high-auth-crypto T11：Front 商户操作门控（TC-02-05a..e）
 */
class FrontMerchantOperationGateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * TC-02-05a / F-048：顾客 JWT 取消配送 → 403
     * #given 普通会员 auth（user_id + mobile，非配送员）
     * #when 调用 assertSelfDeliveryStaffOrForbidden
     * #then HttpException 403
     */
    public function testTc0205aPlainCustomerCannotCancelDeliveryStaff(): void
    {
        #given
        $auth = [
            'user_id' => 100,
            'company_id' => 1,
            'mobile' => '13800138000',
            'operator_type' => 'user',
        ];
        $employeeService = $this->createMock(EmployeeService::class);
        $employeeService->method('getListStaff')->willReturn([]);

        #when / #then
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('无权执行此操作');
        try {
            FrontMerchantOperationGate::assertSelfDeliveryStaffOrForbidden($auth, $employeeService);
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            throw $e;
        }
    }
}
