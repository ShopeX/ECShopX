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

use OrdersBundle\Entities\UserOrderInvoice;
use OrdersBundle\Entities\NormalOrders;
use ThirdPartyBundle\Services\FapiaoCentre\HangxinService;

class UserOrderInvoiceService
{
    /** @var entityRepository */
    public $entityRepository;

    /**
     * ShopsService 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(UserOrderInvoice::class);
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // ShopEx EcShopX Service Component
        return $this->entityRepository->$method(...$parameters);
    }

    public function saveData($orderId, $invoice)
    {
        $params['order_id'] = $orderId;
        $infodata = $this->entityRepository->getInfo($params);
        if (!$infodata) {
            $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
            $orderInfo = $normalOrdersRepository->getInfo($params);
            if ($orderInfo && $orderInfo['invoice']) {
                $params['user_id'] = $orderInfo['user_id'];
                $params['company_id'] = $orderInfo['company_id'];
                $params['status'] = 1;
                $params['invoice'] = $invoice;
                if (in_array($orderInfo['order_type'], ['normal', 'service']) && $orderInfo['order_class'] != $orderInfo['order_type'] && !in_array($orderInfo['order_class'], ['normal', 'service'])) {
                    $orderType = $orderInfo['order_type'].'_'.$orderInfo['order_class'];
                } else {
                    $orderType = $orderInfo['order_type'];
                }
                $params['order_type'] = $orderType;
                return $this->entityRepository->create($params);
            }
        } else {
            return $this->entityRepository->updateOneBy($params, ['invoice' => $invoice]);
        }
    }

    public function getDataList($filter, $page = 1, $pageSize = 100, $orderBy = ['id' => 'desc'])
    {
        $listdata = $this->entityRepository->lists($filter, $page, $pageSize, $orderBy);
        $orderIds = array_column($listdata['list'], 'order_id');
        $orderType = array_column($listdata['list'], 'order_type');
        foreach ($orderType as $type) {
            $orderService = $this->getOrderService($type);
            $orderFilter['order_id'] = $orderIds;
            $orderFilter['company_id'] = $filter['company_id'];
            $result = $orderService->getOrderList($orderFilter);
            if (!$result['list']) {
                continue;
            }
            $orderList = array_column($result['list'], null, 'order_id');
            foreach ($listdata['list'] as $key => $data) {
                if (!isset($orderList[$data['order_id']])) {
                    continue;
                }
                $listdata['list'][$key] = $orderList[$data['order_id']];
                $listdata['list'][$key]['invoice_url'] = $data['invoice'];
            }
        }
        return $listdata;
    }


    public function updateDate($filler, $data)
    {
        //更新ID检查
        if (!isset($filler['id']) || !isset($data) || !$data) {
            return false;
        }

        //update
        $this->entityRepository->updateBy($filler, $data);
        return true;
    }


    /**
     * 创建发票
     */
    public function createFapiao($data)
    {
        $HangxinService = new HangxinService();
        $rt_fapiao = $HangxinService->createFapiao($data);

        return $rt_fapiao;
    }

    /**
     * 创建发票
     */
    public function getFapiao($data)
    {
        $HangxinService = new HangxinService();
        $rt_fapiao = $HangxinService->getFapiao($data);

        return $rt_fapiao;
    }
}
