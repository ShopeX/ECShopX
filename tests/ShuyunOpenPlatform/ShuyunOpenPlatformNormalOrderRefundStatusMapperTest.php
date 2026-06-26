<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformNormalOrderRefundStatusMapper;

class ShuyunOpenPlatformNormalOrderRefundStatusMapperTest extends \TestCase
{
    public function testMapRefundStatus(): void
    {
        $this->assertSame('SY_REFUND_SUCC', ShuyunOpenPlatformNormalOrderRefundStatusMapper::mapRefundStatus(['refund_status' => 'SUCCESS']));
        $this->assertSame('SY_CHECKING', ShuyunOpenPlatformNormalOrderRefundStatusMapper::mapRefundStatus(['refund_status' => 'READY']));
        $this->assertSame('SY_REFUNDING', ShuyunOpenPlatformNormalOrderRefundStatusMapper::mapRefundStatus(['refund_status' => 'PROCESSING']));
        $this->assertSame('SY_REFUND_FAIL', ShuyunOpenPlatformNormalOrderRefundStatusMapper::mapRefundStatus(['refund_status' => 'CHANGE']));
    }

    public function testMapGoodReturnFromAftersalesDetailType(): void
    {
        $this->assertSame('SY_RETURN_FEE_GOOD', ShuyunOpenPlatformNormalOrderRefundStatusMapper::mapGoodReturnFromAftersalesDetailType('REFUND_GOODS'));
        $this->assertSame('SY_ONLY_FEE', ShuyunOpenPlatformNormalOrderRefundStatusMapper::mapGoodReturnFromAftersalesDetailType('ONLY_REFUND'));
    }

    public function testResolveRefundPhase(): void
    {
        $this->assertSame(1, ShuyunOpenPlatformNormalOrderRefundStatusMapper::resolveRefundPhase(['order_status' => 'DONE'], '1'));
        $this->assertSame(1, ShuyunOpenPlatformNormalOrderRefundStatusMapper::resolveRefundPhase(['order_status' => 'DONE'], 1));
        $this->assertSame(2, ShuyunOpenPlatformNormalOrderRefundStatusMapper::resolveRefundPhase(['order_status' => 'DONE'], '0'));
        $this->assertSame(1, ShuyunOpenPlatformNormalOrderRefundStatusMapper::resolveRefundPhase(['order_status' => 'PAYED'], '0'));
    }
}
