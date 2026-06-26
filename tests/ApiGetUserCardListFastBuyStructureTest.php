<?php

/**
 * 店务 getUserCardList：立即购买须读 Redis fastbuy 分桶，与 checkout 一致。
 */

use KaquanBundle\Http\Api\V1\Action\UserDiscount;

class ApiGetUserCardListFastBuyStructureTest extends TestCase
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

    public function testGetUserCardListFastBuyUsesRedisBucketPath(): void
    {
        $body = $this->methodBody(UserDiscount::class, 'getUserCardList');
        $this->assertStringContainsString('getFastBuyCartdataList', $body);
        $this->assertStringContainsString('fastbuy', $body);
    }

    public function testGetUserCardListCartModeStillUsesOperatorCartList(): void
    {
        $body = $this->methodBody(UserDiscount::class, 'getUserCardList');
        $this->assertStringContainsString('getCartdataList', $body);
    }
}
