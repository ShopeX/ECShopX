<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services\HistoricalSync;

final class HistoricalSyncMobileValidator
{
    public static function isValidMainlandMobile(string $mobile): bool
    {
        $mobile = trim($mobile);

        return $mobile !== '' && (bool) preg_match('/^1[0-9]{10}$/', $mobile);
    }
}
