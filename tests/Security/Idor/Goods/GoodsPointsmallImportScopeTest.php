<?php

declare(strict_types=1);

namespace Tests\Security\Idor\Goods;

use PointsmallBundle\Services\NormalGoodsStoreUploadService;
use TestCase;

/**
 * codex-security-03-high-idor T24 / F-023 / TC-03-05d
 */
class GoodsPointsmallImportScopeTest extends TestCase
{
    /**
     * TC-03-05d / F-023：积分商城库存导入须带 company_id 解析 SKU。
     * #given handleRow 源码
     * #when 检查 getInfo 过滤
     * #then 须包含 company_id 与 GoodsTenantScopeGuard
     */
    public function testTc0305dPointsmallImportResolvesSkuWithCompanyScope(): void
    {
        #given
        $body = $this->methodBody(NormalGoodsStoreUploadService::class, 'handleRow');

        #when / #then
        $this->assertStringContainsString("'company_id' => \$companyId", $body);
        $this->assertStringContainsString('GoodsTenantScopeGuard', $body);
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
