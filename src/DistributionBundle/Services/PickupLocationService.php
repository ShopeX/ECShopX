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

namespace DistributionBundle\Services;

use DistributionBundle\Entities\PickupLocation;
use Dingo\Api\Exception\ResourceException;
use ThirdPartyBundle\Services\Map\MapService;

class PickupLocationService
{
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(PickupLocation::class);
    }

    public function checkPickupTime($companyId, $id, $pickupDate, $pickupTime) {
        $pickupLocation = null;
        
        // 判断是否是店铺地址（负数ID）
        if ($id < 0) {
            // 从店铺表获取信息
            $distributorId = abs($id);
            $distributorService = new DistributorService();
            $distributor = $distributorService->getInfoSimple([
                'company_id' => $companyId,
                'distributor_id' => $distributorId
            ]);
            
            if (!$distributor) {
                throw new ResourceException(trans('DistributionBundle/Services/PickupLocationService.pickup_location_not_exist'));
            }
            
            // 转换店铺营业时间格式
            $hours = $this->formatDistributorHour($distributor['hour'] ?? '');
            // 从营业时间中提取工作日
            $workdays = $this->extractWorkdaysFromHours($hours);
            
            // 构造自提点格式的数据
            $pickupLocation = [
                'hours' => $hours,
                'workdays' => $workdays,
            ];
        } else {
            // 原有逻辑：从自提点表获取
            $filter = [
                'company_id' => $companyId,
                'id' => $id,
            ];
            $pickupLocation = $this->entityRepository->getInfo($filter);
            if (!$pickupLocation) {
                throw new ResourceException(trans('DistributionBundle/Services/PickupLocationService.pickup_location_not_exist'));
            }
        }

        // 验证工作日
        $day = date('w', strtotime($pickupDate));
        if ($day == '0') {
            $day = '7';
        }
        
        // workdays 可能是字符串（逗号分隔）或数组
        $workdaysArray = is_array($pickupLocation['workdays']) 
            ? $pickupLocation['workdays'] 
            : explode(',', trim($pickupLocation['workdays'], ','));
        
        if (!in_array($day, $workdaysArray)) {
            throw new ResourceException(trans('DistributionBundle/Services/PickupLocationService.time_slot_unavailable'));
        }

        // 验证营业时间
        $ifPickup = false;
        if (!empty($pickupLocation['hours']) && is_array($pickupLocation['hours'])) {
            foreach ($pickupLocation['hours'] as $val) {
                if (is_array($val)) {
                    // 兼容两种格式：
                    // 1. 4元素数组 [工作日开始, 工作日结束, 时间开始, 时间结束]
                    // 2. 2元素数组 [时间开始, 时间结束]（新格式，与自提点一致）
                    if (count($val) == 4) {
                        // 旧格式：检查时间部分（索引2和3）
                        if ($val[2] == $pickupTime[0] && $val[3] == $pickupTime[1]) {
                            $ifPickup = true;
                            break;
                        }
                    } elseif (count($val) == 2) {
                        // 新格式：直接检查时间（索引0和1）
                        if ($val[0] == $pickupTime[0] && $val[1] == $pickupTime[1]) {
                            $ifPickup = true;
                            break;
                        }
                    }
                }
            }
        }
        
        if (!$ifPickup) {
            throw new ResourceException(trans('DistributionBundle/Services/PickupLocationService.time_slot_unavailable'));
        }

        return true;
    }
    
    /**
     * 转换店铺营业时间格式为自提点格式
     * @param string $hour 店铺营业时间（可能是JSON或字符串格式）
     * @return array 自提点格式的营业时间 [["08:00", "23:30"]]，与自提点格式保持一致（只有时间，没有工作日）
     */
    public function formatDistributorHour($hour)
    {
        if (!$hour) {
            return [];
        }
        
        // 尝试解析为JSON
        $decoded = json_decode($hour, true);
        if ($decoded && is_array($decoded)) {
            // 如果已经是数组格式，检查是否包含工作日信息
            $result = [];
            foreach ($decoded as $item) {
                if (is_array($item)) {
                    // 如果是4元素数组 [工作日开始, 工作日结束, 时间开始, 时间结束]
                    if (count($item) == 4) {
                        $result[] = [$item[2], $item[3]]; // 只取时间部分
                    } 
                    // 如果是2元素数组 [时间开始, 时间结束]
                    elseif (count($item) == 2) {
                        $result[] = $item;
                    }
                }
            }
            if (!empty($result)) {
                return $result;
            }
        }
        
        // 如果是字符串格式 "08:00-23:30"，转换为数组格式（只有时间）
        if (strpos($hour, '-') !== false) {
            $hourParts = explode('-', $hour);
            if (count($hourParts) == 2) {
                return [
                    [trim($hourParts[0]), trim($hourParts[1])]
                ];
            }
        }
        
        return [];
    }
    
    /**
     * 从营业时间中提取工作日
     * @param array $hours 营业时间数组（现在只有时间，没有工作日信息）
     * @return string 工作日字符串，如 "1,2,3,4,5,6,7"
     * 
     * 注意：由于 hours 格式已改为只有时间（与自提点格式一致），无法从中提取工作日
     * 店铺地址默认每天可用，返回所有工作日
     */
    public function extractWorkdaysFromHours($hours)
    {
        // 由于 hours 格式已改为只有时间（与自提点格式一致），无法从中提取工作日
        // 店铺地址默认每天可用
        return '1,2,3,4,5,6,7';
    }

    public function savePickupLocation($params) {
        // 判断营业时间不能重复
        $hoursMap = [];
        foreach ($params['hours'] as $val) {
            list($h, $m) = explode(':', $val[0]);
            $key = intval($h) * 60 + intval($m);
            if (isset($hoursMap[$key])) {
                throw new ResourceException(trans('DistributionBundle/Services/PickupLocationService.business_hours_duplicate'));
            }
            $hoursMap[$key] = $val;
        }
        ksort($hoursMap);
        $minutes = -1;
        foreach ($hoursMap as $val) {
            list($h, $m) = explode(':', $val[0]);
            $start = intval($h) * 60 + intval($m);
            list($h, $m) = explode(':', $val[1]);
            $end = intval($h) * 60 + intval($m);
            if ($start <= $minutes) {
                throw new ResourceException(trans('DistributionBundle/Services/PickupLocationService.business_hours_duplicate'));
            }
            $minutes = $end;
        }

        if (isset($params['area_code']) && $params['area_code']) {
            $params['contract_phone'] = $params['area_code'].'-'.$params['contract_phone'];
        }

        $params['workdays'] = array_filter($params['workdays'], function($val) {
            return $val == 1 || $val == 2 || $val == 3 || $val == 4 || $val == 5 || $val == 6 || $val == 7;
        });

        // 获取经纬度
        $location = MapService::make($params['company_id'])->getLatAndLng($params['city'], $params['address']);
        if (empty($location->getLng()) || empty($location->getLat())) {
            throw new ResourceException(trans('DistributionBundle/Services/PickupLocationService.address_recognition_error'));
        }
        $params['lng'] = $location->getLng();
        $params['lat'] = $location->getLat();

        if (isset($params['id'])) {
            $filter = [
                'company_id' => $params['company_id'],
                'distributor_id' => $params['distributor_id'],
                'id' => $params['id'],
            ];
            return $this->entityRepository->updateOneBy($filter, $params);
        } else {
            return $this->entityRepository->create($params);
        }
    }

    public function relDistributor($companyId, $distributorId, $id, $relDistributorId)
    {
        // ID: 53686f704578
        if ($distributorId > 0 && $relDistributorId > 0 && $distributorId != $relDistributorId) {
            throw new ResourceException(trans('DistributionBundle/Services/PickupLocationService.only_current_store_relation'));
        }

        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
            'id' => $id,
        ];
        $data = $this->entityRepository->getInfo($filter);
        if (!$data) {
            throw new ResourceException(trans('DistributionBundle/Services/PickupLocationService.pickup_location_not_exist'));
        }

        if ($relDistributorId > 0) {
            // if ($data['rel_distributor_id'] && $data['rel_distributor_id'] != $relDistributorId) {
            //     throw new ResourceException('自提点【'.$data['name'].'】已关联其他店铺');
            // }

            $distributorFilter = [
                'company_id' => $companyId,
                'distributor_id' => $relDistributorId,
            ];
            $distributorService = new DistributorService();
            $distributor = $distributorService->getInfoSimple($distributorFilter);
            if (!$distributor || $distributor['is_valid'] == 'delete') {
                throw new ResourceException(trans('DistributionBundle/Services/PickupLocationService.related_store_not_exist'));
            }
        }

        return $this->entityRepository->updateOneBy($filter, ['rel_distributor_id' => $relDistributorId]);
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
