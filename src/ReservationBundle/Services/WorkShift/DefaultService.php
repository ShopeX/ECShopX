<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ReservationBundle\Services\WorkShift;

use Dingo\Api\Exception\ResourceException;
use ReservationBundle\Entities\DefaultWorkShift;
use ReservationBundle\Entities\WorkShiftType;
use ReservationBundle\Interfaces\WorkShiftInterface;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;

class DefaultService implements WorkShiftInterface
{
    public $weekday = [
        'monday' => '周一',
        'tuesday' => '周二',
        'wednesday' => '周三',
        'thursday' => '周四',
        'friday' => '周五',
        'saturday' => '周六',
        'sunday' => '周日',
    ];

    public function createData(array $paramsdata)
    {
        return [];
    }


    public function updateData(array $filter, array $workShiftData)
    {
        //获取门店营业时间
        $shopsService = new ShopsService(new WxShopsService());
        $storeData = $shopsService->getShopsDetail($filter['shop_id']);
        $expiredAt = $storeData['expired_at'];
        if (!$expiredAt || $expiredAt <= time()) {
            throw new ResourceException('店铺已经过期，无法完成排班');
        }

        $storeOpenTime = $storeData['hour'];
        list($beginTime, $endTime) = explode('-', $storeOpenTime);
        $date = date('Y-m-d');
        $beginTime = strtotime($date.$beginTime);
        $endTime = strtotime($date.$endTime);
        $shiftType = app('registry')->getManager('default')->getRepository(WorkShiftType::class);

        foreach ($workShiftData as $key => $value) {
            $typeData = $shiftType->getData($value['typeId']);
            $begin = strtotime($date.$typeData['beginTime']);
            $end = strtotime($date.$typeData['endTime']);

            if ($value['typeId'] != '-1' && ($beginTime > $begin || $endTime < $end)) {
                throw new ResourceException($this->weekday[$key].'的排班时间超出门店开店时间:'.$storeOpenTime);
            }
        }
        $paramsdata['work_shift_data'] = $workShiftData;
        $defaultWorkShift = app('registry')->getManager('default')->getRepository(DefaultWorkShift::class);
        return $defaultWorkShift->update($filter, $paramsdata);
    }

    public function deleteData(array $filter)
    {
        //		$filter = [
//            'company_id' => $companyId,
//            'shop_id' => $shopId,
//        ];
        $defaultWorkShift = app('registry')->getManager('default')->getRepository(DefaultWorkShift::class);
        return $defaultWorkShift->delete($filter);
    }

    public function getList(array $filter, $page = 1, $limit = 10, $orderBy = '')
    {
        return [];
    }

    public function get(array $filter)
    {
        $defaultWorkShift = app('registry')->getManager('default')->getRepository(DefaultWorkShift::class);
        return $defaultWorkShift->get($filter);
    }
}
