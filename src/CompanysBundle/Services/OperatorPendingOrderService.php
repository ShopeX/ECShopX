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
