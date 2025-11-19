<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
