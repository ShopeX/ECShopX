<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CompanysBundle\Services;

use CompanysBundle\Repositories\OperatorsRepository;
use CompanysBundle\Support\OperatorDistributorIdsPatterns;
use CompanysBundle\Support\OperatorDistributorNamePatch;

class OperatorDistributorNameSyncService
{
    private const PAGE_SIZE = 100;

    /** @var OperatorsRepository */
    private $operatorsRepository;

    public function __construct(OperatorsRepository $operatorsRepository)
    {
        $this->operatorsRepository = $operatorsRepository;
    }

    public function syncIfNameChanged(int $companyId, $distributorId, string $oldName, string $newName): void
    {
        if ($oldName === $newName) {
            return;
        }

        $patterns = OperatorDistributorIdsPatterns::forDistributorId($distributorId);
        $filter = [
            'company_id' => $companyId,
            'distributor_ids' => $patterns,
        ];

        $page = 1;
        $totalPages = 1;

        do {
            $result = $this->operatorsRepository->lists($filter, '*', $page, self::PAGE_SIZE);
            $list = $result['list'] ?? [];
            $totalCount = (int) ($result['total_count'] ?? 0);
            $totalPages = max(1, (int) ceil($totalCount / self::PAGE_SIZE));

            foreach ($list as $operator) {
                $originalItems = $operator['distributor_ids'] ?? [];
                if (!is_array($originalItems)) {
                    continue;
                }

                $patchedItems = OperatorDistributorNamePatch::patchDistributorNameInJson(
                    $originalItems,
                    $distributorId,
                    $newName
                );

                if ($patchedItems != $originalItems) {
                    $this->operatorsRepository->updateOneBy(
                        ['operator_id' => $operator['operator_id']],
                        ['distributor_ids' => $patchedItems]
                    );
                }
            }

            $page++;
        } while ($page <= $totalPages);
    }
}
