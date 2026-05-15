<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 */

namespace EmployeePurchaseBundle\Services;

use Dingo\Api\Exception\ResourceException;

final class StoreHomePageAccess
{
    /**
     * @param array<string,mixed> $row
     */
    public static function assertRowMatchesDealer(array $row, int $authDistributorId): void
    {
        if ($authDistributorId > 0 && (int) ($row['distributor_id'] ?? 0) !== $authDistributorId) {
            throw new ResourceException('未查询到数据');
        }
    }
}
