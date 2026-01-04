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

namespace HfPayBundle\Listeners;

use DistributionBundle\Services\DistributorService;
use HfPayBundle\Services\HfpayEnterapplyService;

class HfEnterapplyInit
{
    /**
     * 店铺创建
     */
    public function add($event)
    {
        $data = $event->entities;

        $companyId = $data['company_id'];
        $distributorId = $data['distributor_id'];
        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
        ];
        $distributorService = new DistributorService();
        $result = $distributorService->getInfo($filter);
        if ($result['is_open'] != 'true') {
            return true;
        }
        $applyService = new HfpayEnterapplyService();
        $enterapplyData = $applyService->getEnterapply($filter);
        if (empty($enterapplyData)) {
            $applyService->createInitApply($companyId, $distributorId);
        }

        return true;
    }

    /**
     * 店铺编辑
     */
    public function edit($event)
    {
        $data = $event->entities;

        $companyId = $data['company_id'];
        $distributorId = $data['distributor_id'];
        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
        ];
        $distributorService = new DistributorService();
        $result = $distributorService->getInfo($filter);
        if ($result['is_open'] != 'true') {
            return true;
        }
        $applyService = new HfpayEnterapplyService();
        $enterapplyData = $applyService->getEnterapply($filter);
        if (empty($enterapplyData)) {
            $applyService->createInitApply($companyId, $distributorId);
        }

        return true;
    }


    /**
     * 为订阅者注册监听器
     */
    public function subscribe($events)
    {
        //店铺创建
        $events->listen(
            'DistributionBundle\Events\DistributionAddEvent',
            'HfPayBundle\Listeners\HfEnterapplyInit@add'
        );

        //店铺编辑
        $events->listen(
            'DistributionBundle\Events\DistributionEditEvent',
            'HfPayBundle\Listeners\HfEnterapplyInit@edit'
        );
    }
}
