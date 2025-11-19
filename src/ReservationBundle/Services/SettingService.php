<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
