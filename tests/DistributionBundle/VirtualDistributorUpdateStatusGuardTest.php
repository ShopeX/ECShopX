<?php

declare(strict_types=1);

namespace Tests\DistributionBundle;

use DistributionBundle\Services\VirtualDistributorUpdateStatusGuard;
use Dingo\Api\Exception\StoreResourceFailedException;

/** @see .tasks/plans/shuyun-virtual-shop-open-sync.md V-STA-01、API-01V（多字段 PUT 下 has/is_valid 意图与原始取值） */
class VirtualDistributorUpdateStatusGuardTest extends \TestCase
{
    /**
     * @dataProvider enabledSemanticProvider
     *
     * @param  mixed  $value
     */
    public function testIsEnabledSemanticAcceptsTrueOneAndStrings($value): void
    {
        $this->assertTrue(VirtualDistributorUpdateStatusGuard::isEnabledSemanticIsValidValue($value));
    }

    /**
     * @return iterable<string, array{0: mixed}>
     */
    public static function enabledSemanticProvider(): iterable
    {
        yield 'bool true' => [true];
        yield 'int 1' => [1];
        yield 'string true' => ['true'];
        yield 'string TRUE' => ['TRUE'];
        yield 'string 1' => ['1'];
    }

    /**
     * @dataProvider nonEnabledSemanticProvider
     *
     * @param  mixed  $value
     */
    public function testIsEnabledSemanticRejectsNonEnabled($value): void
    {
        $this->assertFalse(VirtualDistributorUpdateStatusGuard::isEnabledSemanticIsValidValue($value));
    }

    /**
     * @return iterable<string, array{0: mixed}>
     */
    public static function nonEnabledSemanticProvider(): iterable
    {
        yield 'bool false' => [false];
        yield 'int 0' => [0];
        yield 'string false' => ['false'];
        yield 'string 0' => ['0'];
        yield 'closed' => ['closed'];
        yield 'delete' => ['delete'];
    }

    public function testVirtualShopWithIsValidFalseThrows(): void
    {
        $this->expectException(StoreResourceFailedException::class);
        VirtualDistributorUpdateStatusGuard::forbidNonEnabledStatusIntentForVirtualShop(
            true,
            true,
            false,
            false,
            ''
        );
    }

    public function testVirtualShopWithReviewRejectThrows(): void
    {
        $this->expectException(StoreResourceFailedException::class);
        VirtualDistributorUpdateStatusGuard::forbidNonEnabledStatusIntentForVirtualShop(
            true,
            false,
            null,
            true,
            'false'
        );
    }

    public function testNonVirtualShopDoesNotThrowForIllegalIsValid(): void
    {
        VirtualDistributorUpdateStatusGuard::forbidNonEnabledStatusIntentForVirtualShop(
            false,
            true,
            'closed',
            false,
            ''
        );
        $this->assertTrue(true);
    }

    public function testVirtualShopProfileOnlyIntentDoesNotThrow(): void
    {
        VirtualDistributorUpdateStatusGuard::forbidNonEnabledStatusIntentForVirtualShop(
            true,
            false,
            null,
            false,
            ''
        );
        $this->assertTrue(true);
    }

    public function testVirtualShopIsValidTrueDoesNotThrow(): void
    {
        VirtualDistributorUpdateStatusGuard::forbidNonEnabledStatusIntentForVirtualShop(
            true,
            true,
            true,
            false,
            ''
        );
        $this->assertTrue(true);
    }
}
