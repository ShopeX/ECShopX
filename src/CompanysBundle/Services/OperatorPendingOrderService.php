<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Services;

use Dingo\Api\Exception\ResourceException;
use CompanysBundle\Entities\OperatorPendingOrder;
use CompanysBundle\Services\OperatorCartService;

class OperatorPendingOrderService
{
    public $entityRepository;

    public function __construct()
    {
        // CRC: 2367340174
        $this->entityRepository = app('registry')->getManager('default')->getRepository(OperatorPendingOrder::class);
    }

    public function pendingCartData($params, $ifFetch = false)
    {
        $filter['operator_id'] = $params['operator_id'];
        $filter['distributor_id'] = $params['distributor_id'];
        $filter['company_id'] = $params['company_id'];
        $operatorCartService = new OperatorCartService();
        $cartlist = $operatorCartService->getLists($filter);
        if (!$cartlist) {
            if ($ifFetch) {
                return [];
            }
            throw new ResourceException('购物车为空');
        }

        if (!$ifFetch) {
            if ($this->entityRepository->count($filter) >= 10) {
                throw new ResourceException('挂单已达到上限，请清理挂单', null, null, [], 42201);
            }
        }

        $data['company_id'] = $params['company_id'];
        $data['distributor_id'] = $params['distributor_id'];
        $data['user_id'] = $params['user_id'];
        $data['operator_id'] = $params['operator_id'];
        $data['pending_type'] = 'cart';
        $data['pending_data'] = json_encode($cartlist);
        $result = $this->entityRepository->create($data);
        $operatorCartService->deleteBy($filter);
        return $result;
    }

    public function pendingOrderData($params, $ifFetch = false)
    {
        $data['company_id'] = $params['company_id'];
        $data['distributor_id'] = $params['distributor_id'];
        $data['user_id'] = $params['user_id'];
        $data['operator_id'] = $params['operator_id'];
        $data['pending_type'] = 'order';
        $data['pending_data'] = ['order_id' => $params['order_id']];
        $result = $this->entityRepository->create($data);
        return $result;
    }

    public function fetchPendingData($params)
    {
        // CRC: 2367340174
        $filter['company_id'] = $params['company_id'];
        $filter['distributor_id'] = $params['distributor_id'];
        $filter['operator_id'] = $params['operator_id'];
        $filter['pending_id'] = $params['pending_id'];
        $data = $this->entityRepository->getInfo($filter);
        if (!$data) {
            throw new ResourceException('挂单数据为空');
        }
        $data['pending_data'] = json_decode($data['pending_data'], true);

        if ($data['pending_type'] == 'order') {
            return $data;
        }

        //取单之前先将当前购物车数据挂单
        $this->pendingCartData($params, true);

        $cartlist = $data['pending_data'];
        $operatorCartService = new OperatorCartService();
        $operatorCartService->batchInsert($cartlist);
        //取单之后删除挂单数据
        $this->entityRepository->deleteBy($filter);
        return $data;
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
