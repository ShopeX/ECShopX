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

namespace ReservationBundle\Services;

use ReservationBundle\Entities\ReservationSetting;

class SettingService
{
    public function create($params)
    {
        $filter = array();
        if (isset($params['companyId']) && $params['companyId']) {
            $filter['company_id'] = $params['companyId'];
        }
        if (!$filter) {
            return false;
        }

        $settingRepository = app('registry')->getManager('default')->getRepository(ReservationSetting::class);
        return $settingRepository->saveData($filter, $params);
    }

    public function get($filter)
    {
        $result = [];

        if (!$filter) {
            return $result;
        }

        $settingRepository = app('registry')->getManager('default')->getRepository(ReservationSetting::class);
        $result = $settingRepository->getData($filter);
        if (isset($result['reservationNumLimit']) && $result['reservationNumLimit']) {
            $limit = unserialize($result['reservationNumLimit']);
            if ($limit) {
                $result['limitType'] = $limit['limit_type'];
                $result['limit'] = $limit[$limit['limit_type']];
            }
        }
        return $result;
    }
}
