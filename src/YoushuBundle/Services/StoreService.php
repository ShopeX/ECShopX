<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
