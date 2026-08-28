<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Goods;

use GoodsBundle\Http\Api\V1\Action\Items;
use GoodsBundle\Services\ItemsService;
use TestCase;

/**
 * codex-security-03-high-idor T24 / F-027 / TC-03-05
 */
class GoodsBatchUpdateItemStoreScopeTest extends TestCase
{
    /**
     * TC-03-05 / F-027：批量改库存须校验 item 归属 auth company。
     * #given batchUpdateItemStore 源码
     * #when 检查租户绑定
     * #then 须调用 GoodsTenantScopeGuard
     */
    public function testTc0305BatchUpdateItemStoreScopesByAuthCompanyId(): void
    {
        #given
        $body = $this->methodBody(Items::class, 'batchUpdateItemStore');

        #when / #then
        $this->assertStringContainsString('GoodsTenantScopeGuard', $body);
    }

    /**
     * TC-03-05 / F-027：非 default SKU 改库存须校验 company_id。
     * #given updateItemsStore 源码
     * #when 检查 item 归属
     * #then 须调用 GoodsTenantScopeGuard::assertItemIdsBelongToCompany
     */
    public function testTc0305UpdateItemsStoreValidatesItemCompanyScope(): void
    {
        #given
        $body = $this->methodBody(ItemsService::class, 'updateItemsStore');

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
