<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services\HistoricalSync;

final class HistoricalSyncStepResult
{
    public function __construct(
        public readonly string $step,
        public readonly int $processed,
        public readonly int $succeeded,
        public readonly int $skipped,
        public readonly int $failed,
    ) {
    }

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        return [
            'step' => $this->step,
            'processed' => $this->processed,
            'succeeded' => $this->succeeded,
            'skipped' => $this->skipped,
            'failed' => $this->failed,
        ];
    }
}
