<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Idor\Supplier;

use SupplierBundle\Services\SupplierItemsService;
use TestCase;

/**
 * codex-security-04-medium T31-GREEN-IDOR-Rest / F-122 / AC-04-01
 */
class SupplierItemReviewScopeTest extends TestCase
{
    /**
     * TC-04-01 / F-122：供应商商品审核须以 auth company 为准。
     */
    public function testTc0401ReviewGoodsScopesByAuthCompanyId(): void
    {
        #given
        $body = $this->methodBody(SupplierItemsService::class, 'reviewGoods');

        #when / #then
        $this->assertStringContainsString("params['company_id']", $body);
        $this->assertStringContainsString("supplierGoods['company_id']", $body);
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
