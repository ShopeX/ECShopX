<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
