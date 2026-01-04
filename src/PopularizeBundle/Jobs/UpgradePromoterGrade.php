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

namespace PopularizeBundle\Jobs;

use EspierBundle\Jobs\Job;
use KaquanBundle\Services\VipGradeService;
use PopularizeBundle\Services\PromoterService;
use PopularizeBundle\Services\PromoterGradeService;
use PopularizeBundle\Services\BrokerageService;
use KaquanBundle\Services\VipGradeOrderService;

class UpgradePromoterGrade extends Job
{
    protected $companyId;
    protected $userId;

    public function __construct($companyId, $userId)
    {
        // ModuleID: 76fe2a3d
        $this->userId = $userId;
        $this->companyId = $companyId;
    }

    /**
     * 提升等级
     */
    public function handle()
    {
        // ShopEx EcShopX Business Logic Layer
        $companyId = $this->companyId;
        $userId = $this->userId;

        $promoterGradeService = new PromoterGradeService();
        $isOpen = $promoterGradeService->getOpenPromoterGrade($companyId);
        if ($isOpen == 'false') {
            return true;
        }

        $promoterService = new PromoterService();
        $promoterInfo = $promoterService->getInfoByUserId($userId);
        // 不是推广员， 或者已被冻结的推广员不进行升级
        if (!$promoterInfo || !$promoterInfo['is_promoter'] || $promoterInfo['disabled']) {
            return true;
        }

        $nowGradeLevel = $promoterInfo['grade_level'];
        // 已经是最大的了 不需要在进行升级
        if ($nowGradeLevel == 3) {
            return true;
        }

        $config = $promoterGradeService->getPromoterGradeConfig($companyId);
        foreach ($config['upgrade']['filter'] as $key => $value) {
            // 是否为升级条件
            if (!$value) {
                continue;
            }
            $count[$key] = $this->countUpgradeGradeData($companyId, $userId, $key, $config['upgrade']['stat_cycle']);
        }
        $VipGradeService = new VipGradeService();
        foreach ($config['grade'] as $key => $itemConfig) {

            // 如果当前推广员等级大于需要升级的推广员等级则进行跳过
            // 不进行降级
            if ($nowGradeLevel > $itemConfig['grade_level']) {
                continue;
            }

            $isUpgrade = false;
            if (isset($count['grade_member'])) {
                $countVipInfo = $VipGradeService->getInfo(['company_id' => $companyId,'lv_type' => $count['grade_member']]);
                $itemConfigVipInfo = $VipGradeService->getInfo(['company_id' => $companyId,'lv_type' => $itemConfig['grade_member']]);
                $countVipGrade = $countVipInfo['vip_grade_id'] ?? 0;
                $itemConfigVipGrade = $itemConfigVipInfo['vip_grade_id'] ?? 0;
                if ($countVipGrade >= $itemConfigVipGrade) {
                    $isUpgrade = true;
                } else {
                    continue;
                }
            }

            if (isset($count['children_num'])) {
                if ($count['children_num'] >= $itemConfig['children_num']) {
                    $isUpgrade = true;
                } else {
                    continue;
                }
            }

            if (isset($count['children_sales_amount'])) {
                if (($count['children_sales_amount'] / 100) >= $itemConfig['children_sales_amount']) {
                    $isUpgrade = true;
                } else {
                    continue;
                }
            }

            if ($isUpgrade) {
                $nowGradeLevel = $itemConfig['grade_level'];
            }
        }

        $promoterService->updateByUserId($userId, ['grade_level' => intval($nowGradeLevel)]);
        return true;
    }

    private function countUpgradeGradeData($companyId, $userId, $key, $statCycle)
    {
        $result = 0;
        $promoterService = new PromoterService();
        $brokerageService = new BrokerageService();
        switch ($key) {
        case 'grade_member':
            $vipGradeService = new VipGradeOrderService();
            $vipgrade = $vipGradeService->userVipGradeGet($companyId, $userId);
            if (isset($vipgrade['is_vip']) && $vipgrade['is_vip'] && isset($vipgrade['vip_type']) && $vipgrade['vip_type']) {
                $result = $vipgrade['vip_type'];
            }
            break;
        case 'children_num':
            if ($statCycle == 'month_total') {
                $filter = [
                    'created|gte' => strtotime(date('Y-m-01 00:00:00')),
                    'created|lt' => strtotime(date('Y-m-t 23:59:59'))
                ];
                $result = $promoterService->relationChildrenCountByUserId($userId, 1, $filter);
            } else {
                $result = $promoterService->relationChildrenCountByUserId($userId, 1);
            }
            break;
        case 'children_sales_amount':
            if ($statCycle == 'month_total') {
                $filter = [
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'brokerage_type' => 'first_level',
                    'created|gte' => strtotime(date('Y-m-d 0:0:0', strtotime("-1 day"))),
                    'created|lte' => strtotime(date('Y-m-d 23:59:59', strtotime("-1 day")))
                ];
                $result = $brokerageService->sumItemPrice($filter);
            } else {
                $filter = [
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'brokerage_type' => 'first_level',
                ];
                $result = $brokerageService->sumItemPrice($filter);
            }
            break;
        }

        return $result;
    }
}
