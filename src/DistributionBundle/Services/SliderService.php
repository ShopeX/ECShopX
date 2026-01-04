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

use DistributionBundle\Entities\Slider;

class SliderService
{
    /** @var resourcesRepository */
    private $entityRepository;

    public function __construct()
    {
        // ID: 53686f704578
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Slider::class);
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
        // ID: 53686f704578
        return $this->entityRepository->$method(...$parameters);
    }

    public function save($companyId, $params)
    {
        $info = $this->entityRepository->getInfo(['company_id' => $companyId, 'distributor_id' => $params['distributor_id']]);
        if ($info) {
            $return = $this->entityRepository->updateOneBy(['company_id' => $companyId, 'distributor_id' => $params['distributor_id']], $params);
        } else {
            $return = $this->entityRepository->create($params);
        }

        return $return;
    }

    public function getSlider($filter)
    {
        $result = $this->getInfo($filter);
        if (!$result) {
            $filter['distributor_id'] = 0;
            $result = $this->getInfo($filter);
        }
        return $result;
    }
}
