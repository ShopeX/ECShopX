<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
