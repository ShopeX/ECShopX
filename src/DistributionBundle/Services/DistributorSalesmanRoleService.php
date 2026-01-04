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

namespace DistributionBundle\Services;

use DistributionBundle\Entities\DistributorSalesmanRole;
use SalespersonBundle\Services\SalespersonService;
use Dingo\Api\Exception\ResourceException;

class DistributorSalesmanRoleService
{
    private $distributorSalesmanRoleRepository;

    public function __construct()
    {
        $this->distributorSalesmanRoleRepository = app('registry')->getManager('default')->getRepository(DistributorSalesmanRole::class);
    }

    /**
     * 发货权限获取
     *
     * @param int $salespersonId
     * @return void
     */
    public function checkSalespersonRole($salespersonId, $route)
    {
        if ($route[1]['role'] ?? 0) {
            $salespersonService = new SalespersonService();
            $filter = [
                'salesperson_id' => $salespersonId
            ];
            $info = $salespersonService->salesperson->getInfo($filter);
            if ($info['role'] ?? 0) {
                $roleFilter = [
                'salesman_role_id' => $info['role']
            ];
                $roleInfo = $this->getInfo($roleFilter);
                if (($roleInfo['rule_ids'] ?? 0) && in_array($route[1]['role'], $roleInfo['rule_ids'])) {
                    return true;
                }
            }
            throw new ResourceException(trans('DistributionBundle/Services/DistributorSalesmanRoleService.no_permission_contact_admin'));
        }
        return true;
    }

    public function __call($method, $parameters)
    {
        return $this->distributorSalesmanRoleRepository->$method(...$parameters);
    }
}
