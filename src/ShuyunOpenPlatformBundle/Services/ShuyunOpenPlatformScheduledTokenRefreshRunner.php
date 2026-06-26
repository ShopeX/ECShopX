<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;

/**
 * TA5：批量触发数云 Token 刷新 GET；具体跳过规则见仓储查询与 {@see ShuyunOpenPlatformTokenRefreshService}。
 */
final class ShuyunOpenPlatformScheduledTokenRefreshRunner
{
    private CompanyShuyunOpenPlatformConfigRepository $repository;

    private ShuyunOpenPlatformTokenRefreshServiceInterface $tokenRefreshService;

    public function __construct(
        CompanyShuyunOpenPlatformConfigRepository $repository,
        ShuyunOpenPlatformTokenRefreshServiceInterface $tokenRefreshService
    ) {
        $this->repository = $repository;
        $this->tokenRefreshService = $tokenRefreshService;
    }

    /**
     * @return array{attempted:int, ok:int, failed:int}
     */
    public function run(?int $companyId = null): array
    {
        if ($companyId !== null) {
            $row = $this->repository->findOneByCompanyId($companyId);
            $rows = $row !== null ? [$row] : [];
        } else {
            $rows = $this->repository->findEligibleForScheduledRefresh();
        }

        $attempted = count($rows);
        $ok = 0;
        foreach ($rows as $row) {
            if ($this->tokenRefreshService->triggerRefresh($row, $companyId !== null)) {
                ++$ok;
            }
        }

        return [
            'attempted' => $attempted,
            'ok' => $ok,
            'failed' => $attempted - $ok,
        ];
    }
}
