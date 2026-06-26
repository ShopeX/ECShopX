<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformOrderMoneyUtil;

class ShuyunOpenPlatformOrderMoneyUtilTest extends \TestCase
{
    public function testFenToYuan(): void
    {
        $this->assertSame('1.00', ShuyunOpenPlatformOrderMoneyUtil::fenToYuan(100));
        $this->assertSame('0.99', ShuyunOpenPlatformOrderMoneyUtil::fenToYuan(99));
        $this->assertSame('0.00', ShuyunOpenPlatformOrderMoneyUtil::fenToYuan(0));
        $this->assertSame('12.34', ShuyunOpenPlatformOrderMoneyUtil::fenToYuan('1234'));
    }

    public function testFenToYuanNumberForGatewayJson(): void
    {
        $this->assertSame(1.0, ShuyunOpenPlatformOrderMoneyUtil::fenToYuanNumber(100));
        $this->assertSame(0.99, ShuyunOpenPlatformOrderMoneyUtil::fenToYuanNumber(99));
        $this->assertSame(0.0, ShuyunOpenPlatformOrderMoneyUtil::fenToYuanNumber(0));
        $this->assertSame(0.03, ShuyunOpenPlatformOrderMoneyUtil::fenToYuanNumber(3));
        $encoded = json_encode(
            [
                'trade_discount_fee' => ShuyunOpenPlatformOrderMoneyUtil::fenToYuanNumber(0),
                'post_fee' => ShuyunOpenPlatformOrderMoneyUtil::fenToYuanNumber(1),
            ],
            JSON_UNESCAPED_SLASHES
        );
        $this->assertIsString($encoded);
        $this->assertStringNotContainsString('"0.00"', (string) $encoded);
        $this->assertStringContainsString('0.01', (string) $encoded);
    }
}
