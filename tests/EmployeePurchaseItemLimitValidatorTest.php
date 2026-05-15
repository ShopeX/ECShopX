<?php

use Dingo\Api\Exception\ResourceException;
use EmployeePurchaseBundle\Services\EmployeePurchaseItemLimitValidator;
use PHPUnit\Framework\TestCase;

final class EmployeePurchaseItemLimitValidatorTest extends TestCase
{
    private function activityRow(int $limitNum, int $limitFee): array
    {
        return ['limit_num' => $limitNum, 'limit_fee' => $limitFee, 'activity_price' => 100];
    }

    public function testAllowsWithinLimits(): void
    {
        $this->expectNotToPerformAssertions();
        EmployeePurchaseItemLimitValidator::assertWithPreloadedData(
            ['1' => $this->activityRow(10, 100000)],
            [],
            [['item_id' => '1', 'num' => 1, 'item_fee' => 100]]
        );
    }

    public function testQuantityOnlyUsesNumMessage(): void
    {
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage(EmployeePurchaseItemLimitValidator::MSG_NUM);
        EmployeePurchaseItemLimitValidator::assertWithPreloadedData(
            ['1' => $this->activityRow(1, 100000)],
            [],
            [['item_id' => '1', 'num' => 2, 'item_fee' => 200]]
        );
    }

    public function testFeeOnlyUsesFeeMessage(): void
    {
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage(EmployeePurchaseItemLimitValidator::MSG_FEE);
        EmployeePurchaseItemLimitValidator::assertWithPreloadedData(
            ['1' => $this->activityRow(100, 100)],
            [],
            [['item_id' => '1', 'num' => 1, 'item_fee' => 200]]
        );
    }

    public function testBothViolationsPreferFeeMessage(): void
    {
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage(EmployeePurchaseItemLimitValidator::MSG_FEE);
        EmployeePurchaseItemLimitValidator::assertWithPreloadedData(
            ['1' => $this->activityRow(1, 100)],
            [],
            [['item_id' => '1', 'num' => 2, 'item_fee' => 500]]
        );
    }

    public function testMultiSkuSecondLineFailsQuantity(): void
    {
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage(EmployeePurchaseItemLimitValidator::MSG_NUM);
        EmployeePurchaseItemLimitValidator::assertWithPreloadedData(
            [
                '1' => $this->activityRow(10, 100000),
                '2' => $this->activityRow(1, 100000),
            ],
            [],
            [
                ['item_id' => '1', 'num' => 1, 'item_fee' => 100],
                ['item_id' => '2', 'num' => 2, 'item_fee' => 200],
            ]
        );
    }

    public function testFixedMessagesDoNotContainItemName(): void
    {
        try {
            EmployeePurchaseItemLimitValidator::assertWithPreloadedData(
                ['9' => $this->activityRow(1, 100000)],
                [],
                [['item_id' => '9', 'num' => 5, 'item_fee' => 500]]
            );
            $this->fail('expected exception');
        } catch (ResourceException $e) {
            $msg = $e->getMessage();
            $this->assertTrue(
                $msg === EmployeePurchaseItemLimitValidator::MSG_NUM
                || $msg === EmployeePurchaseItemLimitValidator::MSG_FEE
            );
            $this->assertStringNotContainsString('某某商品', $msg);
        }
    }
}
