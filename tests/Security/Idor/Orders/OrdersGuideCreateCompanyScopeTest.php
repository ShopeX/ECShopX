<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Orders;

use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Http\AdminApi\V1\Action\NormalOrder;
use OrdersBundle\Support\GuideOrderTenantScopeGuard;
use TestCase;

/**
 * codex-security-03-high-idor T23 / F-020 / TC-03-10
 */
class OrdersGuideCreateCompanyScopeTest extends TestCase
{
    /**
     * TC-03-10 / F-020：Guide create 外源 company_id 须 fail-closed 拒绝。
     * #given auth company_id=1，请求 company_id=2
     * #when resolveAuthCompanyId
     * #then ResourceException
     */
    public function testTc0310ForeignCompanyIdRejected(): void
    {
        #given / #when / #then
        $this->expectException(ResourceException::class);

        GuideOrderTenantScopeGuard::resolveAuthCompanyId(['company_id' => 1], 2);
    }

    /**
     * TC-03-10 / F-020：createUserOrder 须绑定 auth company_id。
     * #given NormalOrder@createUserOrder 源码
     * #when 检查租户绑定
     * #then 须调用 GuideOrderTenantScopeGuard::resolveAuthCompanyId
     */
    public function testTc0310CreateUserOrderBindsAuthCompanyId(): void
    {
        #given
        $body = $this->methodBody(NormalOrder::class, 'createUserOrder');

        #when / #then
        $this->assertStringContainsString('GuideOrderTenantScopeGuard', $body);
        $this->assertStringContainsString('resolveAuthCompanyId', $body);
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
