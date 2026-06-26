<?php

declare(strict_types=1);

namespace Tests\DistributionBundle;

use DistributionBundle\Services\DistributorCloudShopStatusGuard;
use PHPUnit\Framework\TestCase;

final class DistributorCloudShopStatusGuardTest extends TestCase
{
    public function testIsValidValuesForCloudAllListFilterIncludesLegacyNumericStrings(): void
    {
        $values = DistributorCloudShopStatusGuard::isValidValuesForCloudAllListFilter();

        $this->assertContains('true', $values);
        $this->assertContains('false', $values);
        $this->assertContains('closed', $values);
        $this->assertContains('1', $values);
        $this->assertContains('0', $values);
        $this->assertNotContains('delete', $values);
    }
}
