<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ReservationBundle\Listeners;

use ReservationBundle\Events\ReservationFinishEvent;
use ReservationBundle\Services\WorkShiftManageService;
use ReservationBundle\Services\WorkShift\WorkShiftService;
use ReservationBundle\Services\WorkShift\DefaultService;

class ReservationFinishWorkShiftAdd
{
    public function handle(ReservationFinishEvent $event)
    {
        try {
            $data = $event->entities;
            $postdata = $data['postdata'];
            $result = $data['result'];
            $params = array_merge($postdata, $result);

            $filter['company_id'] = $params['company_id'];
            $filter['shop_id'] = $params['shop_id'];
            $filter['work_date'] = $params['date_day'];
            $filter['resource_level_id'] = $params['resource_level_id'];

            $WorkShiftService = new WorkShiftManageService(new WorkShiftService());
            $levelData = $WorkShiftService->getLevelWork($filter);
            if (!$levelData) {
                $WorkShiftDefaultService = new WorkShiftManageService(new DefaultService());
                $defaultFilter['company_id'] = $params['company_id'];
                $defaultFilter['shop_id'] = $params['shop_id'];
                $defaultData = $WorkShiftDefaultService->get($defaultFilter);
                $weekday = strtolower(date('l', strtotime($params['date_day'])));
                if (!$defaultData || !isset($defaultData[$weekday])) {
                    return;
                }
                $postdata['companyId'] = $params['company_id'];
                $postdata['shopId'] = $params['shop_id'];
                $postdata['resourceLevelId'] = $params['resource_level_id'];
                $postdata['dateDay'] = strtotime($params['date_day']);
                $postdata['shiftTypeId'] = $defaultData[$weekday]['typeId'];
                $WorkShiftService->createData($postdata);
            }
        } catch (\Exception $e) {
            app('log')->debug('预约成功后增加排班出错' . $e->getMessage());
        }
    }
}
