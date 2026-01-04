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

namespace ReservationBundle\Services\WorkShift;

use Dingo\Api\Exception\ResourceException;
use ReservationBundle\Entities\WorkShift;
use ReservationBundle\Entities\WorkShiftType;
use ReservationBundle\Entities\DefaultWorkShift;
use ReservationBundle\Interfaces\WorkShiftInterface;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;
use ReservationBundle\Services\ResourceLevelManagementService as ResourceLevelService;
use ReservationBundle\Services\SettingService;

class WorkShiftService implements WorkShiftInterface
{
    public $settingData;
    public function __construct($companyId = '')
    {
        if ($companyId) {
            $SettingService = new SettingService();
            $settingFilter['company_id'] = $companyId;
            $this->settingData = $SettingService->get($settingFilter);
        }
    }

    public function createData(array $paramsData)
    {
        //获取门店营业时间
        $shopsService = new ShopsService(new WxShopsService());
        $storeData = $shopsService->getShopsDetail($paramsData['shopId']);
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
        $typeData = $shiftType->getData($paramsData['shiftTypeId']);
        $begin = strtotime($date.$typeData['beginTime']);
        $end = strtotime($date.$typeData['endTime']);

        if ($paramsData['shiftTypeId'] != '-1' && ($beginTime > $begin || $endTime < $end)) {
            throw new ResourceException('排班时间超出门店开店时间:'.$storeOpenTime);
        }
        $workShift = app('registry')->getManager('default')->getRepository(WorkShift::class);
        return $workShift->createShift($paramsData);
    }


    public function updateData(array $filter, array $paramsData)
    {
        //获取门店营业时间
        $shopsService = new ShopsService(new WxShopsService());
        $storeData = $shopsService->getShopsDetail($filter['shop_id']);
        $expiredAt = $storeData['expired_at'];
        if (!$expiredAt || $expiredAt <= time()) {
            throw new ResourceException('店铺已经过期，无法修改排班');
        }

        $storeOpenTime = $storeData['hour'];
        list($beginTime, $endTime) = explode('-', $storeOpenTime);
        $date = date('Y-m-d');
        $beginTime = strtotime($date.$beginTime);
        $endTime = strtotime($date.$endTime);
        $shiftType = app('registry')->getManager('default')->getRepository(WorkShiftType::class);
        $typeData = $shiftType->getData($paramsData['shiftTypeId']);
        $begin = strtotime($date.$typeData['beginTime']);
        $end = strtotime($date.$typeData['endTime']);

        if ($paramsData['shiftTypeId'] != '-1' && ($beginTime > $begin || $endTime < $end)) {
            throw new ResourceException('排班时间超出门店开店时间:'.$storeOpenTime);
        }
        $workShift = app('registry')->getManager('default')->getRepository(WorkShift::class);
        return $workShift->updateShift($filter, $paramsData);
    }

    public function deleteData(array $filter)
    {
        $workShift = app('registry')->getManager('default')->getRepository(WorkShift::class);
        return $workShift->deleteShift($filter);
    }

    /**
     * 资源排班列表
     */
    public function getList(array $filter, $page = 1, $limit = 10, $orderBy = '')
    {
        $result = [];
        $resourceFilter['company_id'] = $filter['company_id'];
        $resourceFilter['status'] = 'active';

        if (isset($filter['shop_id']) && $filter['shop_id']) {
            $resourceFilter['shop_id'] = $filter['shop_id'];
        }

        if (isset($filter['resource_level_id']) && $filter['resource_level_id']) {
            $resourceFilter['resource_level_id'] = $filter['resource_level_id'];
        }
        $resourceService = new ResourceLevelService();
        $resourceLists = $resourceService->getListResourceLevel($resourceFilter, false);
        if (isset($resourceLists['list'])) {
            $lists = $resourceLists['list'];
            foreach ($lists as $list) {
                //获取排班信息
                $shiftFilter['resource_level_id'] = $list['resourceLevelId'];
                if (isset($filter['begin_date'])) {
                    $shiftFilter['begin_date'] = $filter['begin_date'];
                }
                if (isset($filter['end_date'])) {
                    $shiftFilter['end_date'] = $filter['end_date'];
                }
                if (isset($filter['work_date'])) {
                    $shiftFilter['work_date'] = $filter['work_date'];
                }
                $shiftFilter['company_id'] = $filter['company_id'];
                if (isset($filter['shop_id'])) {
                    $shiftFilter['shop_id'] = $filter['shop_id'];
                }
                $newList = $this->getWorkShift($shiftFilter);
                $result[$list['resourceLevelId']] = array_merge($list, $newList);
            }
        }
        return $result;
    }

    public function get(array $filter)
    {
        if (isset($filter['work_date'])) {
            $weekName = strtolower(date('l', $filter['work_date']));
            return $this->getWorkShift($filter)[$weekName];
        }

        return [];
    }

    /**
     * 获取指定资源位指定日期区间的排班信息
     */
    private function getWorkShift($filter)
    {
        $workShiftList = [];
        $result = [];
        $workShift = app('registry')->getManager('default')->getRepository(WorkShift::class);
        $listDatas = $workShift->getList($filter);
        if ($listDatas) {
            foreach ($listDatas as $list) {
                $weekName = strtolower(date('l', $list['workDate']));
                $workShiftList[$weekName] = $list;
            }
        }

        $default = [];
        if (isset($filter['shop_id'])) {
            $default = $this->getDefaultWorkShift($filter['company_id'], $filter['shop_id']);
        }

        if ($default) {
            foreach ($default as $weekday => $typeId) {
                $data = [];
                if (isset($workShiftList[$weekday])) {
                    $data = $workShiftList[$weekday];
                } else {
                    $data = [
                        'id' => 0,
                        'companyId' => $filter['company_id'],
                        'shopId' => $filter['shop_id'],
                        'resourceLevelId' => $filter['resource_level_id'],
                        'shiftTypeId' => $typeId['typeId'],
                    ];
                }

                if (isset($data['shiftTypeId']) && $data['shiftTypeId'] != '-1') {
                    $dataType = $this->getWorkShiftTypeInfo($data['shiftTypeId']);
                } else {
                    $dataType['typeName'] = "休息";
                    $dataType['beginTime'] = "00:00";
                    $dataType['endTime'] = "23:59";
                    $dataType['typeId'] = "-1";
                }
                $result[$weekday] = array_merge($data, $dataType);
            }
        } elseif ($workShiftList) {
            foreach ($workShiftList as $weekday => $data) {
                if (isset($data['shiftTypeId']) && $data['shiftTypeId'] != '-1') {
                    $dataType = $this->getWorkShiftTypeInfo($data['shiftTypeId']);
                } else {
                    $dataType['typeName'] = "休息";
                    $dataType['beginTime'] = "00:00";
                    $dataType['endTime'] = "23:59";
                    $dataType['typeId'] = "-1";
                }
                $result[$weekday] = array_merge($data, $dataType);
            }
        }
        return $result;
    }

    private function getDefaultWorkShift($companyId, $shopId)
    {
        $defaultWorkShift = app('registry')->getManager('default')->getRepository(DefaultWorkShift::class);
        $filter['company_id'] = $companyId;
        $filter['shop_id'] = $shopId;
        $data = $defaultWorkShift->get($filter);
        return $data;
    }

    /**
     * 获取排班类型单条数据
     */
    private function getWorkShiftTypeInfo(int $typeId)
    {
        $shiftType = app('registry')->getManager('default')->getRepository(WorkShiftType::class);
        $data = $shiftType->getData($typeId);
        if ($this->settingData) {
            $date = date('Y-m-d');
            $disc = strtotime($date.$data['endTime']) - strtotime($date.$data['beginTime']);
            $interval = $this->settingData['timeInterval'];
            $data['total_num'] = $disc / ($interval * 60);
        }
        return $data;
    }

    public function getLevelWork($filter)
    {
        $workShift = app('registry')->getManager('default')->getRepository(WorkShift::class);
        $listDatas = $workShift->getList($filter);
        return $listDatas;
    }

    public function getCount($filter)
    {
        $workShift = app('registry')->getManager('default')->getRepository(WorkShift::class);
        return  $workShift->getCount($filter);
    }
}
