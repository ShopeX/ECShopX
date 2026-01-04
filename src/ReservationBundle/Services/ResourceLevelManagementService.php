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

use ReservationBundle\Entities\ResourceLevel;
use ReservationBundle\Entities\ResourceLevelRelService;
use ReservationBundle\Services\WorkShift\WorkShiftService;
use ReservationBundle\Entities\ReservationRecord;
use Dingo\Api\Exception\ResourceException;

use GoodsBundle\Services\ServiceLabelsService;

class ResourceLevelManagementService
{
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(ResourceLevel::class);
    }

    public function createResourceLevel($postParams, $serviceIds)
    {
        $resourceLevel = app('registry')->getManager('default')->getRepository(ResourceLevel::class);
        return $resourceLevel->create($postParams, $serviceIds);
    }

    public function updateResourceLevel($filter, $postParams, $serviceIds)
    {
        $resourceLevel = app('registry')->getManager('default')->getRepository(ResourceLevel::class);
        return $resourceLevel->update($filter, $postParams, $serviceIds);
    }

    public function deleteResourceLevel($filter)
    {
        //检测是否有排班
        $WorkShift = new WorkShiftManageService(new WorkShiftService());
        $workShiftCount = $WorkShift->getCount($filter);
        if ($workShiftCount) {
            throw new ResourceException('该资源位已有排班，不可删除');
        }
        //检测是否有预约
        $ReservationRecord = app('registry')->getManager('default')->getRepository(ReservationRecord::class);
        $recordCount = $ReservationRecord->getCount($filter);
        if ($recordCount) {
            throw new ResourceException('该资源位已有预约记录，不可删除');
        }

        $resourceLevel = app('registry')->getManager('default')->getRepository(ResourceLevel::class);
        return $resourceLevel->remove($filter);
    }

    public function getResourceLevel($filter, $ifGetRel = true)
    {
        $resourceLevel = app('registry')->getManager('default')->getRepository(ResourceLevel::class);
        $data = $resourceLevel->get($filter);
        if (!$data) {
            return array();
        }
        if ($ifGetRel) {
            $relFilter = [
                'resource_level_id' => [$data['resourceLevelId']],
                'company_id' => [$data['companyId']],
            ];
            $relService = $this->__getListRelService($relFilter);
            if (!$relService) {
                return array();
            }
            $data['materialIds'] = $relService[$data['resourceLevelId']];
        }
        return $data;
    }

    public function getListResourceLevel($filter, $ifGetRel = true)
    {
        $result = [
            'total_count' => 0,
            'list' => [],
        ];
        $resourceLevel = app('registry')->getManager('default')->getRepository(ResourceLevel::class);
        $countData = $resourceLevel->getCount($filter);
        if (!$countData) {
            return $result;
        }
        $dataLists = $resourceLevel->getList($filter);

        if ($ifGetRel) {
            foreach ($dataLists as $list) {
                $relFilter['resource_level_id'][] = $list['resourceLevelId'];
                $relFilter['company_id'][] = $list['companyId'];
            }

            $relService = $this->__getListRelService($relFilter);

            foreach ($dataLists as &$listdata) {
                $listdata['materialIds'] = $relService[$listdata['resourceLevelId']];
            }
        }
        $result['total_count'] = $countData;
        $result['list'] = $dataLists;
        return $result;
    }

    private function __getListRelService($filter)
    {
        if (isset($filter['resource_level_id'])) {
            $filter['resource_level_id'] = array_unique($filter['resource_level_id']);
        }
        if (isset($filter['company_id'])) {
            $filter['company_id'] = array_unique($filter['company_id']);
        }
        $LevelRelService = app('registry')->getManager('default')->getRepository(ResourceLevelRelService::class);
        $lists = $LevelRelService->getList($filter);
        $relServiceList = [];
        foreach ($lists as $list) {
            $relServiceList[$list['resourceLevelId']][] = intval($list['materialId']);
        }
        return $relServiceList;
    }

    /**
     * 根据服务获取资源位
     *
     * @Param company_id int
     * @param server_goods_id array
     * @param shop_id int
     */
    public function getListByMaterial($companyId, $materialId, $shopId)
    {
        $relFilter['material_id'] = $materialId;
        $relFilter['company_id'] = $companyId;
        $LevelRelService = app('registry')->getManager('default')->getRepository(ResourceLevelRelService::class);
        $lists = $LevelRelService->getList($relFilter);
        foreach ($lists as $value) {
            $levelRelS[$value['resourceLevelId']] = $value['materialId'];
        }
        $resourceLevelId = array_unique(array_column($lists, 'resourceLevelId'));

        $filter['company_id'] = $companyId;
        $filter['shop_id'] = $shopId;
        $filter['resource_level_id'] = $resourceLevelId;
        $resourceLevel = app('registry')->getManager('default')->getRepository(ResourceLevel::class);
        $dataLists = $resourceLevel->getList($filter);
        foreach ($dataLists as &$list) {
            $list['materialId'] = $levelRelS[$list['resourceLevelId']];
        }
        return $dataLists;
    }

    /**
     * 获取有效的资源位（指定门店、指定服务、正常排班）
     */
    public function getListMaterial($filter)
    {
        //获取服务关联的资源位
        $newRelService = [];
        $levelRelService = app('registry')->getManager('default')->getRepository(ResourceLevelRelService::class);
        $levelRelServiceLists = $levelRelService->getList($filter);
        $labelIds = array_column($levelRelServiceLists, 'materialId');
        $serviceData = $this->getLabelData($filter['company_id'], $labelIds);
        foreach ($levelRelServiceLists as $relSe) {
            if (isset($serviceData[$relSe['materialId']]) && $serviceData[$relSe['materialId']]) {
                $newRelService[$relSe['resourceLevelId']][] = [
                    'labelName' => $serviceData[$relSe['materialId']]['labelName'],
                    'labelId' => $serviceData[$relSe['materialId']]['labelId'],
                ];
            }
        }
        return $newRelService;
    }

    private function getLabelData($companyId, $labelIds)
    {
        $newResult = [];
        $serviceLabelsService = new ServiceLabelsService();
        $params['company_id'] = $companyId;
        $params['label_id'] = $labelIds;
        $result = $serviceLabelsService->getServiceLabelsList($params, 1, 100);
        if (isset($result['list']) && $result['list']) {
            foreach ($result['list'] as $value) {
                $newResult[$value['labelId']] = $value;
            }
        }
        return $newResult;
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
