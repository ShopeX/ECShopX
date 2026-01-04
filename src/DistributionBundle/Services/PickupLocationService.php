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
        $filter = [
            'company_id' => $companyId,
            'id' => $id,
        ];
        $pickupLocation = $this->entityRepository->getInfo($filter);
        if (!$pickupLocation) {
            throw new ResourceException(trans('DistributionBundle/Services/PickupLocationService.pickup_location_not_exist'));
        }

        $day = date('w', strtotime($pickupDate));
        if ($day == '0') {
            $day = '7';
        }
        if (!in_array($day, $pickupLocation['workdays'])) {
            throw new ResourceException(trans('DistributionBundle/Services/PickupLocationService.time_slot_unavailable'));
        }

        $ifPickup = false;
        foreach ($pickupLocation['hours'] as $val) {
            if ($val[0] == $pickupTime[0] && $val[1] == $pickupTime[1]) {
                $ifPickup = true;
            }
        }
        if (!$ifPickup) {
            throw new ResourceException(trans('DistributionBundle/Services/PickupLocationService.time_slot_unavailable'));
        }

        return true;
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
