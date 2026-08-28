<?php

declare(strict_types=1);

namespace EspierBundle\Support;

use Dingo\Api\Exception\ResourceException;

/**
 * Export log tenant-scope gate: bind auth company_id on export file reads.
 */
class ExportLogTenantScopeGuard
{
    /**
     * @param array<string, mixed> $row
     */
    public static function assertCompanyScope(array $row, int $companyId): void
    {
        if ($companyId <= 0 || empty($row) || (int) ($row['company_id'] ?? 0) !== $companyId) {
            throw new ResourceException('无权访问该导出文件');
        }
    }
}
