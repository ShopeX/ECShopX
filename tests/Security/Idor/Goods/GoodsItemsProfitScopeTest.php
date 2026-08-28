<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Goods;

use GoodsBundle\Http\Api\V1\Action\ItemsProfit;
use GoodsBundle\Services\ItemsProfitService;
use TestCase;

/**
 * codex-security-03-high-idor T24 / F-028 / TC-03-05b
 */
class GoodsItemsProfitScopeTest extends TestCase
{
    /**
     * TC-03-05b / F-028：导购分润保存须校验 item 归属 auth company。
     * #given saveGoodsProfit 源码
     * #when 检查租户绑定
     * #then 须调用 GoodsTenantScopeGuard
     */
    public function testTc0305bSaveGoodsProfitScopesByAuthCompanyId(): void
    {
        #given
        $body = $this->methodBody(ItemsProfit::class, 'saveGoodsProfit');

        #when / #then
        $this->assertStringContainsString('GoodsTenantScopeGuard', $body);
    }

    /**
     * TC-03-05b / F-028：saveItemsProfit 须校验 item 归属。
     * #given saveItemsProfit 源码
     * #when 检查 item 归属
     * #then 须调用 GoodsTenantScopeGuard::assertItemIdsBelongToCompany
     */
    public function testTc0305bSaveItemsProfitValidatesItemCompanyScope(): void
    {
        #given
        $body = $this->methodBody(ItemsProfitService::class, 'saveItemsProfit');

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
