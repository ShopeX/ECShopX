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
