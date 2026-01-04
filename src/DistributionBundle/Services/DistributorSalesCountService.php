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

use AftersalesBundle\Services\AftersalesService;
use DistributionBundle\Entities\DistributorSalesCount;
use EspierBundle\Services\BaseService;
use OrdersBundle\Services\Orders\AbstractNormalOrder;

class DistributorSalesCountService extends BaseService
{
    public function getEntityClass(): string
    {
        return DistributorSalesCount::class;
    }

    /**
     * 获取总的销售数额
     * @param int $companyId 公司id
     * @param bool $sortDesc 是否是降序【true 降序】【false 升序】
     * @return array
     */
    public function getTotalSalesCount(int $companyId, bool $sortDesc = true): array
    {
        $distributorService = new DistributorService();
        $data = $distributorService->getLists(["company_id" => $companyId], "distributor_id");
        if (empty($data)) {
            return [];
        }
        // 获取所有的店铺id
        $distributorIds = (array)array_column($data, "distributor_id");

        // 获取订单的销售数量
        $distributorItemOrderSalesCountList = (new AbstractNormalOrder())
            ->getDoneOrderTotalSalesCountByDistributorIds($companyId, $distributorIds);
        $distributorItemOrderSalesCountList = (array)array_column($distributorItemOrderSalesCountList, "sales_count", "distributor_id");

        // 获取售后单的销售数量
        $distributorItemAftersalesSalesCountList = (new AftersalesService())
            ->getDoneAftersalesTotalSalesCountByDistributorIds($companyId, $distributorIds);
        $distributorItemAftersalesSalesCountList = (array)array_column($distributorItemAftersalesSalesCountList, "sales_count", "distributor_id");

        $distributorResult = [];
        foreach ($distributorIds as $distributorId) {
            $orderCount = (int)($distributorItemOrderSalesCountList[$distributorId] ?? 0);
            $aftersalesCount = (int)($distributorItemAftersalesSalesCountList[$distributorId] ?? 0);
            // 获取店铺的销售数量
            $distributorResult[$distributorId] = (int)bcsub($orderCount, $aftersalesCount, 0);
        }
        if ($sortDesc) {
            arsort($distributorResult);
        } else {
            asort($distributorResult);
        }
        return $distributorResult;
    }

    public function async(int $page = 1, int $pageSize = 50)
    {
//        $distributorService = new DistributorService;
//
//        $data = $distributorService->lists([], ["distributor_id" => "ASC"], 50, $page, true, "company_id,distributor_id");
//        if (empty($data['list'])) {
//            return;
//        }
//        foreach ($data["list"] as $distributor) {
//            if (empty($distributor["company_id"]) || empty($distributor["distributor_id"])) {
//                continue;
//            }
//
//            $companyId = (int)$distributor["company_id"];
//            $distributorId = (int)$distributor["distributor_id"];
//
//            $distributorItemOrderSalesCountList = (new AbstractNormalOrder)
//                ->getDoneOrderTotalSalesCountByDistributorIds($companyId, [$distributorId]);
//            $distributorItemOrderSalesCountList = (array)array_column($distributorItemOrderSalesCountList, "sales_count", "distributor_id");
//
//            $distributorItemAftersalesSalesCountList = (new AftersalesService)
//                ->getDoneAftersalesTotalSalesCountByDistributorIds($companyId, [$distributorId]);
//            $distributorItemAftersalesSalesCountList = (array)array_column($distributorItemAftersalesSalesCountList, "sales_count", "distributor_id");
//
//
//        }
    }
}
