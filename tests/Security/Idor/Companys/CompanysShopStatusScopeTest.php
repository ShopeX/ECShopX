<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Companys;

use CompanysBundle\Http\Api\V1\Action\Shops;
use TestCase;

/**
 * codex-security-03-high-idor T26 / F-024 / TC-03-07b
 */
class CompanysShopStatusScopeTest extends TestCase
{
    /**
     * TC-03-07b / F-024：门店状态变更须校验 wx_shop 归属 auth company。
     * #given setShopStatus 源码
     * #when 检查租户绑定
     * #then 须调用 WxShopTenantScopeGuard
     */
    public function testTc0307bSetShopStatusScopesByAuthCompanyId(): void
    {
        #given
        $body = $this->methodBody(Shops::class, 'setShopStatus');

        #when / #then
        $this->assertStringContainsString('WxShopTenantScopeGuard', $body);
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
