<?php

/**
 * store-ops-buy-now-cloud-stock：共存与回归（结构断言）
 * TC-COEX-01、TC-COEX-02、TC-REG-01
 *
 * 通过方法体隔离性保证：立即购买不写 OperatorCart；普通加车不碰店务立即购买 Redis 分桶。
 */

use CompanysBundle\Services\OperatorCartService;

class OperatorCartFastBuyCoexistenceStructureTest extends TestCase
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

    /**
     * TC-COEX-01 / S16：立即购买路径不持久化到 OperatorCart 表（与普通行互不影响的一侧保证）。
     */
    public function testTcCoex01FastBuyDoesNotUseEntityRepository(): void
    {
        $body = $this->methodBody(OperatorCartService::class, 'addFastBuyCartdata');
        $this->assertStringNotContainsString('entityRepository', $body);
    }

    /**
     * TC-COEX-02 / S17：普通 cartDataAdd 不读写店务立即购买 Redis 分桶。
     */
    public function testTcCoex02CartDataAddDoesNotTouchShopFastBuyRedis(): void
    {
        $body = $this->methodBody(OperatorCartService::class, 'addCartdata');
        $this->assertStringNotContainsString('OperatorShopFastBuyRedisService', $body);
        $this->assertStringNotContainsString('shop_fastbuy', $body);
    }

    /**
     * TC-REG-01 / S15：普通加车仍走 _checkAddCartItems（门店 store 校验保留在重构后路径上）。
     */
    public function testTcReg01AddCartdataStillUsesCheckAddCartItems(): void
    {
        $body = $this->methodBody(OperatorCartService::class, 'addCartdata');
        $this->assertStringContainsString('_checkAddCartItems', $body);
    }
}
