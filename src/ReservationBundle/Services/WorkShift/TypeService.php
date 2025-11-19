<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ReservationBundle\Services\WorkShift;

use ReservationBundle\Entities\WorkShiftType;
use ReservationBundle\Entities\WorkShift;
use ReservationBundle\Interfaces\WorkShiftInterface;

class TypeService implements WorkShiftInterface
{
    public function createData(array $paramsData)
    {
        $shiftType = app('registry')->getManager('default')->getRepository(WorkShiftType::class);
        return $shiftType->createData($paramsData);
    }


    public function updateData(array $filter, array $paramsData)
    {
        $shiftType = app('registry')->getManager('default')->getRepository(WorkShiftType::class);
        return $shiftType->updateData($filter, $paramsData);
    }

    public function deleteData(array $filter)
    {
        $shiftType = app('registry')->getManager('default')->getRepository(WorkShiftType::class);
        $workShift = app('registry')->getManager('default')->getRepository(WorkShift::class);

        $status = "delete";

        $shiftFiler['shift_type_id'] = $filter['type_id'];
        $shiftFiler['company_id'] = $filter['company_id'];
        $shiftFiler['begin_date'] = time();
        $workShiftData = $workShift->getList($shiftFiler);
        if ($workShiftData) {
            $shiftIds = array_column($workShiftData, 'id');
            return $shiftType->DeleteDataAndRel($filter, $shiftIds);
        }
        unset($shiftFiler['begin_date']);

        $shiftFiler['end_date'] = time();
        $workShiftData = $workShift->getList($shiftFiler);
        if ($workShiftData) {
            $status = "invalid";
        }

        return $shiftType->DeleteData($filter, $status);
    }

    public function getList(array $filter, $page = 1, $pageLimit = 10, $orderBy = ['type_id' => 'DESC'])
    {
        $shiftType = app('registry')->getManager('default')->getRepository(WorkShiftType::class);
        $filter['status'] = 'valid';
        $dataType = [
            [
                'typeName' => '休息',
                'beginTime' => '00:00',
                'endTime' => '23:59',
                'typeId' => '-1',
            ],
        ];
        $list = $shiftType->getList($filter, $pageLimit, $page, $orderBy);
        $result['list'] = array_merge($dataType, $list);
        $result['total_count'] = count($result['list']);
        return $result;
    }

    public function get(array $filter)
    {
        $shiftType = app('registry')->getManager('default')->getRepository(WorkShiftType::class);
        return [];
    }
}
