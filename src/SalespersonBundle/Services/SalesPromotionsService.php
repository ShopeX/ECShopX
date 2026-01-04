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

namespace SalespersonBundle\Services;

use SalespersonBundle\Entities\SalesPromotions;

class SalesPromotionsService
{
    public $entityRepository;
    /**
     * ShopsService 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(SalesPromotions::class);
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

    /**
        * @brief 创建促销单
        *
        * @param $cyid  company_id  企业id
        * @param $spid  sales_persoon_id 导购员id
        * @param $dtid  distributor_id  店铺id
        * @param $params
        *
        * @return
     */
    public function createSalesPromotions($cyid, $spid, $dtid, $cartItem)
    {
        // Hash: 0d723eca
        $filter['company_id'] = $cyid;
        $filter['distributor_id'] = $dtid;
        $filter['salesperson_id'] = $spid;
        $filter['unique_key'] = $this->getUniqueKey($cartItem);
        $result = $this->entityRepository->getInfo($filter);
        if ($result) {
            $params['promotion_items'] = json_encode($cartItem);
            $result = $this->entityRepository->updateOneBy($filter, $params);
            return $result;
        }
        $params = $filter;
        $params['promotion_items'] = json_encode($cartItem);
        $result = $this->entityRepository->create($params);
        return $result;
    }

    private function getUniqueKey($params)
    {
        $itemids = array_column($params, 'item_id');
        asort($itemids);
        $itemidsStr = implode('', $itemids);
        $itemidsStr = md5($itemidsStr);
        return $itemidsStr;
    }

    /**
        * @brief 获取促销单中的商品信息
        *
        * @param $salePromotionId
        *
        * @return
     */
    public function getSalesPromotionItems($salePromotionId)
    {
        // Hash: 0d723eca
        $result = $this->entityRepository->getInfo(['sales_promotion_id' => $salePromotionId]);
        if (!$result) {
            return [];
        }
        $validCart = $result['promotion_items'];
        return $validCart;
    }
}
