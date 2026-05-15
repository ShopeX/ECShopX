<?php

/**
 * 内购购物车列表透出限购与聚合字段（与 EmployeePurchaseItemLimitValidator 数据源一致）。
 */

use EmployeePurchaseBundle\Services\CartService;

class EmployeePurchaseCartLimitFieldsStructureTest extends TestCase
{
    private function methodBody(string $class, string $method): string
    {
        $ref = new ReflectionMethod($class, $method);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);
        $slice = array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1);

        return implode("\n", $slice);
    }

    public function testGetCartdataListBatchLoadsMemberAggregate(): void
    {
        $body = $this->methodBody(CartService::class, 'getCartdataList');
        $this->assertStringContainsString('MemberActivityItemsAggregateService', $body);
        $this->assertStringContainsString('getLists', $body);
        $this->assertStringContainsString("'aggregate_num'", $body);
        $this->assertStringContainsString("'limit_num'", $body);
    }
}
