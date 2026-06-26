<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services\HistoricalSync;

final class HistoricalSyncSteps
{
    public const SHOPS = 'shops';

    public const CATEGORIES = 'categories';

    public const PRODUCTS = 'products';

    public const MEMBERS = 'members';

    public const ORDERS = 'orders';

    public const REFUNDS = 'refunds';

    public const POINTS = 'points';

    public const ALL = 'all';

    /** @return list<string> */
    public static function orderedSteps(): array
    {
        return [
            self::SHOPS,
            self::CATEGORIES,
            self::PRODUCTS,
            self::MEMBERS,
            self::ORDERS,
            self::REFUNDS,
            self::POINTS,
        ];
    }

    /** @return list<string> */
    public static function parseStepOption(string $step): array
    {
        $step = strtolower(trim($step));
        if ($step === self::ALL) {
            return self::orderedSteps();
        }
        $parts = array_map('trim', explode(',', $step));
        $valid = [];
        foreach ($parts as $p) {
            if ($p !== '' && in_array($p, self::orderedSteps(), true)) {
                $valid[] = $p;
            }
        }

        return $valid === [] ? [] : array_values(array_unique($valid));
    }
}
