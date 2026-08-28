<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Orders;

use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Http\AdminApi\V1\Action\NormalOrder;
use OrdersBundle\Support\GuideOrderTenantScopeGuard;
use TestCase;

/**
 * codex-security-03-high-idor T23 / F-019 / TC-03-10b
 */
class OrdersGuideCartCheckoutScopeTest extends TestCase
{
    /**
     * TC-03-10b / F-019：cartCheckout 跨租户会员须 fail-closed 拒绝。
     * #given auth company_id=1，会员属于 company_id=2
     * #when assertMemberBelongsToCompany
     * #then ResourceException
     */
    public function testTc0310bCrossTenantMemberRejected(): void
    {
        #given / #when / #then
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('会员信息有误');

        GuideOrderTenantScopeGuard::assertMemberBelongsToCompany(
            ['company_id' => 2, 'user_id' => 100],
            1
        );
    }

    /**
     * TC-03-10b / F-019：cartCheckout 须绑定 auth company 并校验会员归属。
     * #given NormalOrder@cartCheckout 与 _getOrderParams 源码
     * #when 检查租户绑定
     * #then 须调用 GuideOrderTenantScopeGuard
     */
    public function testTc0310bCartCheckoutBindsAuthCompanyAndMemberScope(): void
    {
        #given
        $cartCheckoutBody = $this->methodBody(NormalOrder::class, 'cartCheckout');
        $orderParamsBody = $this->methodBody(NormalOrder::class, '_getOrderParams');

        #when / #then
        $this->assertStringContainsString('GuideOrderTenantScopeGuard', $cartCheckoutBody);
        $this->assertStringContainsString('resolveAuthCompanyId', $cartCheckoutBody);
        $this->assertStringContainsString('assertMemberBelongsToCompany', $orderParamsBody);
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
