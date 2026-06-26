<?php

declare(strict_types=1);

namespace Tests\OpenapiBundle;

use OpenapiBundle\Services\Member\MemberCardGradeBatchSavePromotionConditionResolver;
use PHPUnit\Framework\TestCase;

class MemberCardGradeBatchSavePromotionConditionTest extends TestCase
{
    public function testPreservesExistingTotalConsumptionWhenPreserveEnabled(): void
    {
        $result = MemberCardGradeBatchSavePromotionConditionResolver::resolve(
            2,
            true,
            ['total_consumption' => 1200],
        );

        $this->assertSame(['total_consumption' => 1200], $result);
    }

    public function testPreservesDefaultGradeZeroConsumption(): void
    {
        $result = MemberCardGradeBatchSavePromotionConditionResolver::resolve(
            1,
            true,
            ['total_consumption' => 0],
        );

        $this->assertSame(['total_consumption' => 0], $result);
    }

    public function testUsesGradeLevelForNewRowWhenPreserveEnabled(): void
    {
        $result = MemberCardGradeBatchSavePromotionConditionResolver::resolve(3, true, null);

        $this->assertSame(['total_consumption' => 3], $result);
    }

    public function testUsesGradeLevelWhenPreserveDisabled(): void
    {
        $result = MemberCardGradeBatchSavePromotionConditionResolver::resolve(
            2,
            false,
            ['total_consumption' => 1200],
        );

        $this->assertSame(['total_consumption' => 2], $result);
    }

    public function testPreservesTotalConsumptionFromJsonString(): void
    {
        $result = MemberCardGradeBatchSavePromotionConditionResolver::resolve(
            4,
            true,
            '{"total_consumption": 2400}',
        );

        $this->assertSame(['total_consumption' => 2400], $result);
    }

    public function testFallsBackToGradeLevelWhenExistingConditionEmpty(): void
    {
        $result = MemberCardGradeBatchSavePromotionConditionResolver::resolve(5, true, []);

        $this->assertSame(['total_consumption' => 5], $result);
    }
}
