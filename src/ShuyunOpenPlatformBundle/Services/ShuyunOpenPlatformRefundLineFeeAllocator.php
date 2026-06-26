<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

/**
 * 将单笔退款金额（分）按订单行 total_fee（分）比例拆分，最后一行吃掉余数，保证总和一致。
 *
 * @phpstan-param  array<int|string, int>  $lineIdToTotalFeeFen  子订单主键 id => total_fee（分）
 * @phpstan-return array<int|string, int>
 */
final class ShuyunOpenPlatformRefundLineFeeAllocator
{
    /**
     * @param  array<int|string, int>  $lineIdToTotalFeeFen
     * @return array<int|string, int>
     */
    public static function allocateProportional(int $totalRefundFen, array $lineIdToTotalFeeFen): array
    {
        /** @var list<int|string> $keys */
        $keys = array_keys($lineIdToTotalFeeFen);
        if ($keys === []) {
            return [];
        }

        if ($totalRefundFen <= 0) {
            $z = [];
            foreach ($keys as $k) {
                $z[$k] = 0;
            }

            return $z;
        }

        $sumWeight = 0;
        foreach ($lineIdToTotalFeeFen as $w) {
            $sumWeight += max(0, (int) $w);
        }

        if ($sumWeight <= 0) {
            $out = [];
            foreach ($keys as $k) {
                $out[$k] = 0;
            }
            $out[$keys[0]] = $totalRefundFen;

            return $out;
        }

        $n = count($keys);
        $remaining = $totalRefundFen;
        $allocated = [];

        for ($i = 0; $i < $n; ++$i) {
            $k = $keys[$i];
            if ($i === $n - 1) {
                $allocated[$k] = max(0, $remaining);
                break;
            }
            $w = max(0, (int) ($lineIdToTotalFeeFen[$k] ?? 0));
            $part = (int) floor($totalRefundFen * $w / $sumWeight);
            $allocated[$k] = $part;
            $remaining -= $part;
        }

        return $allocated;
    }
}
