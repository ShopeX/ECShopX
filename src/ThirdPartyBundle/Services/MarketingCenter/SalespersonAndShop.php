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

namespace ThirdPartyBundle\Services\MarketingCenter;

use SalespersonBundle\Entities\ShopSalesperson;
use DistributionBundle\Entities\Distributor;
use SalespersonBundle\Entities\ShopsRelSalesperson;
use OrdersBundle\Entities\NormalOrders;

class SalespersonAndShop
{
    public function formatSalesData($company_id, $orderInfo, $input)
    {
        $guideIds[] = $orderInfo['salesman_id'];
        empty($orderInfo['bind_salesman_id']) ?: $guideIds[] = $orderInfo['bind_salesman_id'];
        $shopsales = app('registry')->getManager('default')->getRepository(ShopSalesperson::class);

        $lists = $shopsales->getLists(['company_id' => $company_id, 'salesperson_id' => $guideIds], ['salesperson_id', 'work_userid']);
        $lists = array_column($lists, 'work_userid', 'salesperson_id');

        $input['sale_salesperson_id'] = $lists[$orderInfo['salesman_id']] ?? '0';
        $input['bind_salesperson_id'] = $lists[$orderInfo['bind_salesman_id']] ?? '0';

        $distributor = app('registry')->getManager('default')->getRepository(Distributor::class);
        $relShopsales = app('registry')->getManager('default')->getRepository(ShopsRelSalesperson::class);

        $relLists = $relShopsales->getLists(['company_id' => $company_id,'salesperson_id' => $guideIds, 'store_type' => 'distributor'], ['shop_id', 'salesperson_id']);
        $newRelLists = [];
        foreach ($relLists as $value) {
            $newRelLists[$value['salesperson_id']][] = $value['shop_id'];
        }
        $selShopIds = [];
        $isUpdate = [];

        if (!isset($newRelLists[$orderInfo['salesman_id']])) {
            return false;
        }

        if (!in_array($orderInfo['sale_salesman_distributor_id'], $newRelLists[$orderInfo['salesman_id']])) {
            $info = reset($newRelLists[$orderInfo['salesman_id']]);
            $isUpdate['sale_salesman_distributor_id'] = $orderInfo['sale_salesman_distributor_id'] = $info ?: '';
        }

        if (isset($newRelLists[$orderInfo['bind_salesman_id']])
            && !in_array(intval($orderInfo['bind_salesman_distributor_id']), $newRelLists[$orderInfo['bind_salesman_id']])) {
            $info = reset($newRelLists[$orderInfo['bind_salesman_id']]);
            $isUpdate['bind_salesman_distributor_id'] = $orderInfo['bind_salesman_distributor_id'] = $info ?: '';
        }

        $selShopIds[] = $orderInfo['sale_salesman_distributor_id'];
        empty($orderInfo['bind_salesman_distributor_id']) ?: $selShopIds[] = $orderInfo['bind_salesman_distributor_id'];
        $lists = $distributor->getLists(['company_id' => $company_id, 'distributor_id' => $selShopIds]);
        $lists = array_column($lists, 'shop_code', 'distributor_id');

        $input['sale_store_bn'] = $lists[$orderInfo['sale_salesman_distributor_id']] ?? '';
        $input['bind_store_bn'] = $lists[$orderInfo['bind_salesman_distributor_id']] ?? '';
        if (empty($isUpdate)) {
            return $input;
        }

        //销售导购或绑定导购门店不一致，更新订单数据
        if ($isUpdate) {
            $normalOrderRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
            $result = $normalOrderRepository->update(['company_id' => $company_id, 'order_id' => $orderInfo['order_id']], $isUpdate);
            if ($result) {
                return $input;
            }
        }
        return false;
    }
}
