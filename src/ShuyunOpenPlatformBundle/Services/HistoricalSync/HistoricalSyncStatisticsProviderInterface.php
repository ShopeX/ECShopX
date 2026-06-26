<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services\HistoricalSync;

interface HistoricalSyncStatisticsProviderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function collect(int $companyId): array;
}
