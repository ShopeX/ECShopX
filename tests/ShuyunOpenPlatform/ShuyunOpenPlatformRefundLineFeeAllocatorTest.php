<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformRefundLineFeeAllocator;

class ShuyunOpenPlatformRefundLineFeeAllocatorTest extends \TestCase
{
    public function testAllocateProportionalSplitsAndPreservesSum(): void
    {
        $map = [10 => 30, 11 => 30, 12 => 40];
        $out = ShuyunOpenPlatformRefundLineFeeAllocator::allocateProportional(100, $map);
        $this->assertSame(30, $out[10]);
        $this->assertSame(30, $out[11]);
        $this->assertSame(40, $out[12]);
        $this->assertSame(100, array_sum($out));
    }

    public function testZeroWeightsPutAllOnFirstLine(): void
    {
        $map = [1 => 0, 2 => 0];
        $out = ShuyunOpenPlatformRefundLineFeeAllocator::allocateProportional(50, $map);
        $this->assertSame(50, $out[1]);
        $this->assertSame(0, $out[2]);
    }

    public function testNonPositiveTotalYieldsZeros(): void
    {
        $out = ShuyunOpenPlatformRefundLineFeeAllocator::allocateProportional(0, [5 => 100]);
        $this->assertSame([5 => 0], $out);
    }
}
