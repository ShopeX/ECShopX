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

namespace YoushuBundle\Services;

use DistributionBundle\Entities\Distributor;

class StoreService
{
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
    }

    /**
     * @param array $params
     * @return array
     *
     * 添加/更新门店仓库
     */
    public function getData($params)
    {
        // ShopEx EcShopX Service Component
        $distributor_id = $params['object_id'];
        $filter = [
            'distributor_id' => $distributor_id
        ];
        $result = $this->entityRepository->getInfo($filter);
        if (empty($result)) {
            return [];
        }

        $location_info = [
            'country_name' => '中国',
            'province_name' => $result['province'],
            'city_name' => $result['city'],
            'district_name' => $result['area'],
            'address' => $result['address']
        ];
        $geo_info = [
            'type' => '1',
            'latitude' => $result['lat'],
            'longitude' => $result['lng'],
        ];
        $basic_props = [
            'name' => $result['name']
        ];
        $operating_time = [
            'date_zone' => '周一至周日',
            'time_zone' => $result['hour'],
        ];
        $geo_group = [
            'geos' => [
                'type' => '1',
                'latitude' => $result['lat'],
                'longitude' => $result['lng']
            ]
        ];
        $stores[] = [
            'external_store_id' => $result['distributor_id'],
            'type' => 3,
            'business_type' => 99,
            'operation_status' => $result['is_valid'] == true ? 1 : 2,
            'phone_numbers' => [$result['mobile']],
            'location_info' => $location_info,
            'geo_info' => $geo_info,
            'basic_props' => $basic_props,
            'operating_time' => $operating_time,
            'geo_group' => $geo_group
        ];

        return $stores;
    }
}
