<?php

namespace DistributionBundle\Tests\Services;

use DistributionBundle\Services\DistributorCloudShopStatusGuard;

class DistributorCloudShopStatusGuardTest extends \TestCase
{
    public function testNormalizeIsValidFromRowClosed()
    {
        $row = ['is_valid' => DistributorCloudShopStatusGuard::IS_VALID_CLOSED];
        $this->assertSame(DistributorCloudShopStatusGuard::IS_VALID_CLOSED, DistributorCloudShopStatusGuard::normalizeIsValidFromRow($row));
    }

    public function testNormalizeIsValidFromRowFalse()
    {
        $row = ['is_valid' => DistributorCloudShopStatusGuard::IS_VALID_CLOUD_DISABLED];
        $this->assertSame(DistributorCloudShopStatusGuard::IS_VALID_CLOUD_DISABLED, DistributorCloudShopStatusGuard::normalizeIsValidFromRow($row));
    }

    public function testRequiresOpenOrdersCheckForRevoke()
    {
        $this->assertTrue(DistributorCloudShopStatusGuard::requiresOpenNormalOrdersCheck(
            DistributorCloudShopStatusGuard::IS_VALID_CLOUD_ENABLED,
            DistributorCloudShopStatusGuard::IS_VALID_REVOKED
        ));
    }

    public function testRequiresOpenOrdersCheckForClosed()
    {
        $this->assertTrue(DistributorCloudShopStatusGuard::requiresOpenNormalOrdersCheck(
            DistributorCloudShopStatusGuard::IS_VALID_CLOUD_ENABLED,
            DistributorCloudShopStatusGuard::IS_VALID_CLOSED
        ));
    }

    public function testRequiresOpenOrdersCheckForDisable()
    {
        $this->assertTrue(DistributorCloudShopStatusGuard::requiresOpenNormalOrdersCheck(
            DistributorCloudShopStatusGuard::IS_VALID_CLOUD_ENABLED,
            DistributorCloudShopStatusGuard::IS_VALID_CLOUD_DISABLED
        ));
    }

    public function testNoOrderCheckWhenLeavingClosedToEnabled()
    {
        $this->assertFalse(DistributorCloudShopStatusGuard::requiresOpenNormalOrdersCheck(
            DistributorCloudShopStatusGuard::IS_VALID_CLOSED,
            DistributorCloudShopStatusGuard::IS_VALID_CLOUD_ENABLED
        ));
    }

    public function testNormalizeIncomingIsValidBoolean()
    {
        $this->assertSame(DistributorCloudShopStatusGuard::IS_VALID_CLOUD_ENABLED, DistributorCloudShopStatusGuard::normalizeIncomingIsValid(true));
        $this->assertSame(DistributorCloudShopStatusGuard::IS_VALID_CLOUD_DISABLED, DistributorCloudShopStatusGuard::normalizeIncomingIsValid(false));
    }
}
