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

namespace ReservationBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use ReservationBundle\Entities\ReservationSetting;

class SettingRepository extends EntityRepository
{
    public $table = "reservation_setting";

    /**
     * 新增保存预约配置
     */
    public function saveData($filter, $paramsData)
    {
        $setting = $this->findOneBy($filter);
        if (!$setting) {
            $setting = new ReservationSetting();
        }
        $setting->setCompanyId($paramsData['companyId']);
        $setting->setTimeInterval($paramsData['interval']);
        $setting->setResourceName($paramsData['resourceName']);
        $setting->setMaxLimitDay($paramsData['maxLimitDay']);
        $setting->setMinLimitHour($paramsData['minLimitHour']);
        $setting->setReservationCondition($paramsData['condition']);
        $setting->setReservationMode($paramsData['reservationMode']);
        $setting->setCancelMinute($paramsData['cancelMinute']);
        if (isset($paramsData['sms_delay'])) {
            $setting->setSmsDelay($paramsData['sms_delay']);
        }
        if (isset($paramsData['reservationNumLimit'])) {
            $limit = serialize($paramsData['reservationNumLimit']);
            $setting->setReservationNumLimit($limit);
        }

        $em = $this->getEntityManager();
        $em->persist($setting);
        $em->flush();

        $result = [
            'id' => $setting->getId(),
            'company_id' => $setting->getCompanyId(),
            'time_interval' => $setting->getTimeInterval(),
            'resource_name' => $setting->getResourceName(),
            'max_limit_day' => $setting->getMaxLimitDay(),
        ];
        return $result;
    }

    /**
     * 获取指定company_id的预约配置
     */
    public function getData($filter)
    {
        $setting = $this->findOneBy($filter);
        if (!$setting) {
            return array();
        }

        $result = normalize($setting);
        return $result;
    }
}
