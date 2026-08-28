<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Idor;

use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Http\FrontApi\V1\Action\Rights;
use OrdersBundle\Http\FrontApi\V1\Action\UserInvoice;
use OrdersBundle\Http\FrontApi\V1\Action\WxappOrder;
use OrdersBundle\Services\OfflinePaymentService;
use OrdersBundle\Support\GuideOrderTenantScopeGuard;
use TestCase;

/**
 * codex-security-04-medium T31-GREEN / F-092,F-102..105,F-112 / TC-04-01
 */
class OrdersFrontOwnerScopeTest extends TestCase
{
    /**
     * TC-04-01 / F-092,F-105：权益读/码须绑定 auth company+user。
     * #given Rights getRightsDetail/getRightsCode
     * #when 检查 owner scope
     * #then 须调用 GuideOrderTenantScopeGuard
     */
    public function testTc0401RightsReadAndCodeBindAuthOwner(): void
    {
        #given
        $detailBody = $this->methodBody(Rights::class, 'getRightsDetail');
        $codeBody = $this->methodBody(Rights::class, 'getRightsCode');

        #when / #then
        $this->assertStringContainsString('GuideOrderTenantScopeGuard', $detailBody);
        $this->assertStringContainsString('assertRightsBelongsToAuthUser', $detailBody);
        $this->assertStringContainsString('GuideOrderTenantScopeGuard', $codeBody);
        $this->assertStringContainsString('assertRightsBelongsToAuthUser', $codeBody);
    }

    /**
     * TC-04-01 / F-102：疫情登记删除须绑定 auth company+user。
     * #given WxappOrder@delEpidemicRegister
     * #when 检查 owner scope
     * #then 须调用 GuideOrderTenantScopeGuard
     */
    public function testTc0401EpidemicDeleteBindsAuthOwner(): void
    {
        #given
        $body = $this->methodBody(WxappOrder::class, 'delEpidemicRegister');

        #when / #then
        $this->assertStringContainsString('GuideOrderTenantScopeGuard', $body);
        $this->assertStringContainsString('assertEpidemicRecordBelongsToAuthUser', $body);
    }

    /**
     * TC-04-01 / F-103,F-112：线下凭证读/改须绑定订单归属。
     * #given WxappOrder/OfflinePaymentService
     * #when 检查 owner scope
     * #then 须调用 GuideOrderTenantScopeGuard
     */
    public function testTc0401OfflineVoucherReadUpdateBindOrderOwner(): void
    {
        #given
        $readBody = $this->methodBody(WxappOrder::class, 'getOfflineVoucher');
        $updateServiceBody = $this->methodBody(OfflinePaymentService::class, 'updateVoucher');

        #when / #then
        $this->assertStringContainsString('GuideOrderTenantScopeGuard', $readBody);
        $this->assertStringContainsString('assertOrderBelongsToAuthUser', $readBody);
        $this->assertStringContainsString('GuideOrderTenantScopeGuard', $updateServiceBody);
        $this->assertStringContainsString('assertOfflineVoucherBelongsToOrder', $updateServiceBody);
    }

    /**
     * TC-04-01 / F-104：发票更新须绑定 auth user。
     * #given UserInvoice@updateInvoice
     * #when 检查 owner scope
     * #then 须调用 GuideOrderTenantScopeGuard
     */
    public function testTc0401InvoiceUpdateBindsAuthUser(): void
    {
        #given
        $body = $this->methodBody(UserInvoice::class, 'updateInvoice');

        #when / #then
        $this->assertStringContainsString('GuideOrderTenantScopeGuard', $body);
        $this->assertStringContainsString('assertInvoiceBelongsToAuthUser', $body);
    }

    /**
     * TC-04-01：跨用户权益须 fail-closed 拒绝。
     * #given 权益属于 user_id=200，auth user_id=100
     * #when assertRightsBelongsToAuthUser
     * #then ResourceException
     */
    public function testTc0401CrossUserRightsRejected(): void
    {
        #given / #when / #then
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('无权访问该权益');

        GuideOrderTenantScopeGuard::assertRightsBelongsToAuthUser(
            ['company_id' => 1, 'user_id' => 200],
            1,
            100
        );
    }

    private function methodBody(string $class, string $method): string
    {
        $ref = new \ReflectionMethod($class, $method);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);

        return implode("\n", array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));
    }
}
