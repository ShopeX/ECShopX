<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Services\HistoricalSync\HistoricalSyncMobileValidator;

class HistoricalSyncMobileValidatorTest extends \TestCase
{
    public function testValidMainlandMobile(): void
    {
        $this->assertTrue(HistoricalSyncMobileValidator::isValidMainlandMobile('13800138000'));
    }

    public function testInvalidMobileRejected(): void
    {
        $this->assertFalse(HistoricalSyncMobileValidator::isValidMainlandMobile(''));
        $this->assertFalse(HistoricalSyncMobileValidator::isValidMainlandMobile('23800138000'));
        $this->assertFalse(HistoricalSyncMobileValidator::isValidMainlandMobile('1380013800'));
    }
}
