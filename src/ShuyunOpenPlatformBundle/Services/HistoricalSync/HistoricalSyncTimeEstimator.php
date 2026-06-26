<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services\HistoricalSync;

/**
 * 存量同步耗时粗算（串行、含 20%–120% 安全系数）。
 */
final class HistoricalSyncTimeEstimator
{
    private const BATCH_SHOPS = 100;

    private const BATCH_CHUNK = 50;

    /**
     * @param  array<string, int>  $counts  keys: shops, categories, product_units, members, orders, refunds, points
     *
     * @return array{min: int, max: int}
     */
    public static function estimateSecondsRange(array $counts, float $secondsPerRequest): array
    {
        $secondsPerRequest = max(0.05, $secondsPerRequest);
        $requests = 0.0;
        $requests += max(1, (int) ($counts['shops'] ?? 0)) / self::BATCH_SHOPS;
        $requests += max(0, (int) ($counts['categories'] ?? 0)) / self::BATCH_CHUNK;
        $requests += ceil(max(0, (int) ($counts['product_units'] ?? 0)) / self::BATCH_CHUNK);
        $requests += max(0, (int) ($counts['members'] ?? 0));
        $requests += ceil(max(0, (int) ($counts['orders'] ?? 0)) / self::BATCH_CHUNK);
        $requests += ceil(max(0, (int) ($counts['refunds'] ?? 0)) / self::BATCH_CHUNK);
        $requests += max(0, (int) ($counts['points'] ?? 0));

        $base = $requests * $secondsPerRequest;

        return [
            'min' => (int) max(1, floor($base * 0.8)),
            'max' => (int) max(1, ceil($base * 2.0)),
        ];
    }
}
