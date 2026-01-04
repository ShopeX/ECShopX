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

namespace OrdersBundle\Services;

use OrdersBundle\Entities\OrderProcessLog;
use MembersBundle\Services\MemberService;
use SalespersonBundle\Services\SalespersonService;
use CompanysBundle\Services\OperatorsService;

class OrderProcessLogService
{
    /**
     * @var \OrdersBundle\Repositories\OrderProcessLogRepository
     */
    public $orderProcessLogRepository;

    public function __construct()
    {
        $this->orderProcessLogRepository = app('registry')->getManager('default')->getRepository(OrderProcessLog::class);
    }

    /**
     * 创建订单流程日志
     *
     * @param array $params
     * @return void
     */
    public function createOrderProcessLog($params)
    {
        $params['operator_name'] = $this->getOperatorName($params['operator_type'], $params['operator_id']);
        $result = $this->orderProcessLogRepository->create($params);
        return $result;
    }

    /**
     * 获取订单流程操作人
     *
     * @param [type] $operatorType
     * @param [type] $operatorId
     * @return string
     */
    public function getOperatorName($operatorType, $operatorId)
    {
        switch ($operatorType) {
            case 'system':
                $operatorName = '系统';
                break;
            case 'user':
                $memberService = new MemberService();
                $memberInfo = $memberService->getMemberInfo(['user_id' => $operatorId]);
                $operatorName = $memberInfo['mobile'] ?? '';
                break;
            case 'salesperson':
                $salespersonService = new SalespersonService();
                $salespersonInfo = $salespersonService->salesperson->getInfo(['salesperson_id' => $operatorId]);
                $operatorName = $salespersonInfo['name'] ?? '';
                break;
            case 'openapi':
                $operatorName = '开放接口';
                break;
            case 'staff':
            case 'admin':
            case 'distributor':
                $operatorsService = new OperatorsService();
                $operator = $operatorsService->getInfo(['operator_id' => $operatorId]);
                $operatorName = $operator['mobile'] ?? '未知';
                break;
            default:
                $operatorName = '未知';
                break;
        }
        return $operatorName;
    }

    /**
     * Dynamically call the OrderProcessLogService instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->orderProcessLogRepository->$method(...$parameters);
    }
}
