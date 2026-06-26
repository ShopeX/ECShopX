<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;

interface ShuyunOpenPlatformTokenRefreshServiceInterface
{
    public function triggerRefresh(CompanyShuyunOpenPlatformConfig $config, bool $ignoreEnabledCheck = false): bool;
}
