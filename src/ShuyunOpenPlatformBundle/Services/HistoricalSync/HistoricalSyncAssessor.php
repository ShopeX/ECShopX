<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services\HistoricalSync;

use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;

final class HistoricalSyncAssessor
{
    public function __construct(
        private readonly HistoricalSyncStatisticsProviderInterface $statistics,
        private readonly CompanyShuyunOpenPlatformConfigRepository $configRepository,
        private readonly ShuyunOpenPlatformShopSyncService $shopSyncService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(int $companyId, float $secondsPerRequest = 0.4): array
    {
        $stats = $this->statistics->collect($companyId);
        $config = $this->configRepository->findOneByCompanyId($companyId);
        $eligible = $this->shopSyncService->isEligible($config);

        $estimateCounts = [
            'shops' => (int) ($stats['shops']['eligible'] ?? 0),
            'categories' => (int) ($stats['categories']['eligible'] ?? 0),
            'product_units' => (int) ($stats['products']['product_units'] ?? 0),
            'members' => (int) ($stats['members']['eligible'] ?? 0),
            'orders' => (int) ($stats['orders']['eligible'] ?? 0),
            'refunds' => (int) ($stats['refunds']['eligible'] ?? 0),
            'points' => (int) ($stats['points']['eligible'] ?? 0),
        ];
        $timeRange = HistoricalSyncTimeEstimator::estimateSecondsRange($estimateCounts, $secondsPerRequest);

        return [
            'company_id' => $companyId,
            'gateway_eligible' => $eligible,
            'statistics' => $stats,
            'estimate_seconds' => $timeRange,
            'seconds_per_request_assumed' => $secondsPerRequest,
        ];
    }
}
