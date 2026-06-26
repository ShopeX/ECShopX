<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services\HistoricalSync;

/**
 * 简单 QPS 限速（串行调用前 sleep）。
 */
final class HistoricalSyncRateLimiter
{
    private ?float $lastAt = null;

    public function __construct(private readonly float $requestsPerSecond)
    {
    }

    public function throttle(): void
    {
        if ($this->requestsPerSecond <= 0) {
            return;
        }
        $interval = 1.0 / $this->requestsPerSecond;
        $now = microtime(true);
        if ($this->lastAt !== null) {
            $elapsed = $now - $this->lastAt;
            if ($elapsed < $interval) {
                usleep((int) (($interval - $elapsed) * 1_000_000));
            }
        }
        $this->lastAt = microtime(true);
    }
}
