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

namespace EspierBundle\Services;

use EspierBundle\Entities\Subdistrict;

class SubdistrictService
{

    public $subdistrictRepository;

    public function __construct()
    {
        // Built with ShopEx Framework
        $this->subdistrictRepository = app('registry')->getManager('default')->getRepository(Subdistrict::class);
    }

    public function getSubdistrict($companyId, $distributorId = null, $regions = [])
    {
        $filter = [
            'company_id' => $companyId,
            'parent_id' => 0,
        ];

        if (isset($distributorId) && is_array($distributorId)) {
            foreach ($distributorId as $did) {
                $filter['distributor_id|contains'][] = ','.$did.',';
            }
        } elseif (isset($distributorId) && $distributorId >= 0) {
            $filter['distributor_id|contains'] = ','.$distributorId.',';
        }
        if ($regions) {
            $filter['province|contains'] = $regions['province'] ?? '';
            $filter['city|contains'] = $regions['city'] ?? '';
            $filter['area|contains'] = str_replace('区', '', $regions['area'] ?? '');
        }

        $subdistrictList = $this->subdistrictRepository->lists($filter, 1, -1, ['label' => 'ASC']);
        $subdistrict = $subdistrictList['list'];

        $cFilter = ['company_id' => $companyId];
        if (isset($distributorId) && is_array($distributorId)) {
            foreach ($distributorId as $did) {
                $cFilter['distributor_id|contains'][] = ','.$did.',';
            }
        } elseif (isset($distributorId) && $distributorId >= 0) {
            $cFilter['distributor_id|contains'] = ','.$distributorId.',';
        }
        foreach ($subdistrict as $k => $v) {
            $cFilter['parent_id'] = $v['id'];
            $a = $this->subdistrictRepository->lists($cFilter, 1, -1, ['label' => 'ASC']);
            $subdistrict[$k]['children'] = $a['list'];
        }

        return $subdistrict;
    }

    /**
     * Dynamically call the SubdistrictService instance.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->subdistrictRepository->$method(...$parameters);
    }
}