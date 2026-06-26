<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\HistoricalSync\HistoricalSyncAssessor;
use ShuyunOpenPlatformBundle\Services\HistoricalSync\HistoricalSyncStatisticsProviderInterface;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;

class HistoricalSyncAssessorTest extends \TestCase
{
    public function testAssessReturnsStatisticsAndEstimate(): void
    {
        $stats = [
            'shops' => ['total' => 2, 'eligible' => 2],
            'categories' => ['total' => 5, 'eligible' => 5],
            'products' => ['product_units' => 128, 'eligible' => 128],
            'members' => ['total' => 254, 'eligible' => 254, 'invalid' => 0],
            'orders' => ['total' => 364, 'eligible' => 95, 'skipped' => 269],
            'refunds' => ['total' => 8, 'eligible' => 8],
            'points' => ['total' => 50, 'eligible' => 50],
        ];
        $provider = $this->createMock(HistoricalSyncStatisticsProviderInterface::class);
        $provider->method('collect')->with(1)->willReturn($stats);

        $cfg = new CompanyShuyunOpenPlatformConfig();
        $cfg->setIsEnabled(1);
        $cfg->setAuthValue('a');
        $cfg->setAppId('id');
        $cfg->setAppSecret('s');
        $cfg->setAccessToken('t');
        $openRepo = $this->createMock(CompanyShuyunOpenPlatformConfigRepository::class);
        $openRepo->method('findOneByCompanyId')->willReturn($cfg);
        $shop = $this->createMock(ShuyunOpenPlatformShopSyncService::class);
        $shop->method('isEligible')->willReturn(true);

        $assessor = new HistoricalSyncAssessor($provider, $openRepo, $shop);
        $report = $assessor->assess(1, 0.4);

        $this->assertTrue($report['gateway_eligible']);
        $this->assertSame(95, $report['statistics']['orders']['eligible']);
        $this->assertArrayHasKey('min', $report['estimate_seconds']);
        $this->assertGreaterThan(0, $report['estimate_seconds']['max']);
    }
}
