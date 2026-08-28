<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Goods;

use GoodsBundle\Http\Api\V1\Action\MemberPrice;
use PromotionsBundle\Services\MemberPriceService;
use TestCase;

/**
 * codex-security-03-high-idor T24 / F-031 / TC-03-05c
 */
class GoodsMemberPriceScopeTest extends TestCase
{
    /**
     * TC-03-05c / F-031：会员价保存须校验 item 归属 auth company。
     * #given saveMemberPrice 源码
     * #when 检查租户绑定
     * #then 须调用 GoodsTenantScopeGuard
     */
    public function testTc0305cSaveMemberPriceScopesByAuthCompanyId(): void
    {
        #given
        $body = $this->methodBody(MemberPrice::class, 'saveMemberPrice');

        #when / #then
        $this->assertStringContainsString('GoodsTenantScopeGuard', $body);
    }

    /**
     * TC-03-05c / F-031：saveMemberPrice service 须校验 item 归属。
     * #given MemberPriceService::saveMemberPrice 源码
     * #when 检查 item 归属
     * #then 须调用 GoodsTenantScopeGuard::assertItemIdsBelongToCompany
     */
    public function testTc0305cSaveMemberPriceServiceValidatesItemCompanyScope(): void
    {
        #given
        $body = $this->methodBody(MemberPriceService::class, 'saveMemberPrice');

        #when / #then
        $this->assertStringContainsString('GoodsTenantScopeGuard', $body);
        $this->assertStringContainsString('assertItemIdsBelongToCompany', $body);
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
