<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Orders;

use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Http\AdminApi\V1\Action\Orders;
use OrdersBundle\Support\GuideOrderTenantScopeGuard;
use TestCase;

/**
 * codex-security-03-high-idor T23 / TC-03-10r：导购订单读守门
 */
class OrdersGuideReadScopeWiringTest extends TestCase
{
    /**
     * TC-03-10r：跨店订单详情须 fail-closed 拒绝。
     * #given 订单属于 distributor_id=2，导购 scope distributor_id=1
     * #when assertOrderInGuideStore
     * #then ResourceException
     */
    public function testTc0310rCrossStoreOrderDetailRejected(): void
    {
        #given / #when / #then
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('此订单不是本店订单');

        GuideOrderTenantScopeGuard::assertOrderInGuideStore(['distributor_id' => 2], 1);
    }

    /**
     * TC-03-10r：导购订单详情须校验本店 distributor scope。
     * #given Orders@getOrdersInfo 源码
     * #when 检查读守门
     * #then 须调用 GuideOrderTenantScopeGuard::assertOrderInGuideStore
     */
    public function testTc0310rOrderDetailEnforcesGuideStoreScope(): void
    {
        #given
        $body = $this->methodBody(Orders::class, 'getOrdersInfo');

        #when / #then
        $this->assertStringContainsString('GuideOrderTenantScopeGuard', $body);
        $this->assertStringContainsString('assertOrderInGuideStore', $body);
    }

    /**
     * TC-03-10r：导购订单列表须校验会员归属 auth company。
     * #given Orders@getOrdersList 源码
     * #when 检查读守门
     * #then 须调用 GuideOrderTenantScopeGuard::assertMemberBelongsToCompany
     */
    public function testTc0310rOrderListEnforcesMemberCompanyScope(): void
    {
        #given
        $body = $this->methodBody(Orders::class, 'getOrdersList');

        #when / #then
        $this->assertStringContainsString('GuideOrderTenantScopeGuard', $body);
        $this->assertStringContainsString('assertMemberBelongsToCompany', $body);
    }

    /**
     * TC-03-10r：店铺订单列表须绑定 auth company_id。
     * #given Orders@getSalespersonOrdersList 源码
     * #when 检查租户过滤
     * #then filter 含 auth company_id 且调用 GuideOrderTenantScopeGuard
     */
    public function testTc0310rSalespersonOrderListBindsAuthCompanyId(): void
    {
        #given
        $body = $this->methodBody(Orders::class, 'getSalespersonOrdersList');

        #when / #then
        $this->assertStringContainsString('GuideOrderTenantScopeGuard', $body);
        $this->assertStringContainsString('resolveAuthCompanyId', $body);
        $this->assertStringContainsString('resolveGuideDistributorId', $body);
    }

    private function methodBody(string $class, string $method): string
    {
        $ref = new \ReflectionMethod($class, $method);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);
        $slice = array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1);

        return implode("\n", $slice);
    }
}
