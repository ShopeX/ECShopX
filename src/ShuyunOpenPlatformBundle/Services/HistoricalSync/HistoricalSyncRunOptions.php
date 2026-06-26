<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services\HistoricalSync;

final class HistoricalSyncRunOptions
{
    /**
     * @param  list<string>  $steps
     */
    public function __construct(
        public readonly int $companyId,
        public readonly array $steps,
        public readonly int $limit = 0,
        public readonly int $offset = 0,
        public readonly bool $resume = false,
        public readonly float $rate = 0.0,
        public readonly bool $force = false,
        public readonly bool $dryRun = false,
        public readonly bool $assumeCardBound = false,
        public readonly int $defaultItemId = 0,
        public readonly int $distributorId = 0,
    ) {
    }
}
