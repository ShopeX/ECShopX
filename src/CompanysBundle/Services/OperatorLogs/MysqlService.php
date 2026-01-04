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

namespace CompanysBundle\Services\OperatorLogs;

use CompanysBundle\Services\EmployeeService;
use CompanysBundle\Services\OperatorsService;
use CompanysBundle\Entities\OperatorLogs;
use CompanysBundle\Interfaces\OperatorLogsInterface;
use MerchantBundle\Services\MerchantService;

class MysqlService implements OperatorLogsInterface
{
    /** @var operatorLogsRepository */
    private $operatorLogsRepository;

    /**
     * MysqlService 构造函数.
     */
    public function __construct()
    {
        $this->operatorLogsRepository = app('registry')->getManager('default')->getRepository(OperatorLogs::class);
    }

    public function addLogs($params)
    {
        return $this->operatorLogsRepository->create($params);
    }

    public function getLogsList($filter, $page = 1, $pageSize = 20, $orderBy = ['created' => 'DESC'])
    {
        // FIXME: check performance
        $operatorsService = new OperatorsService();
        $employeeService = new EmployeeService();
        $merchantService = new MerchantService();
        $result = $this->operatorLogsRepository->lists($filter, $page, $pageSize, $orderBy);
        foreach ($result['list'] as &$v) {
            $operator = $operatorsService->getInfo(['operator_id' => $v['operator_id']]);
            if (!$operator) {
                continue;
            }
            if ($operator['operator_type'] == 'admin') {
                $v['username'] = '超级管理员';
            } elseif ($operator['operator_type'] == 'staff') {
                $employee = $employeeService->getInfoStaff($v['operator_id'], $v['company_id']);
                $v['username'] = $employee['username'];
            } elseif ($operator['operator_type'] == 'merchant') {
                if (!empty($filter['merchant_id'])) {
                    $v['username'] = '超级管理员';
                } else {
                    $merchantInfo = $merchantService->getInfo(['id' => $operator['merchant_id']]);
                    $v['username'] = $merchantInfo['merchant_name'];
                }
            } else {
                $v['username'] = $operator['username'];
            }
        }
        return $result;
    }

    public function deleteLogs($filter)
    {
        return $this->operatorLogsRepository->deleteBy($filter);
    }

    /**
     * Dynamically call the MysqlService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // FIXME: check performance
        return $this->operatorLogsRepository->$method(...$parameters);
    }
}
