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

use DistributionBundle\Entities\BasicConfig;

class BasicConfigService
{
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(BasicConfig::class);
    }

    /**
     * 保存分销基础配置
     */
    public function saveBasicConfig($companyId, $params)
    {
        $info = $this->entityRepository->getInfoById($companyId);

        $data = [
            'is_buy' => true,//(isset($params['is_buy']) && $params['is_buy'] == "true") ? true : false,
            'limit_rebate' => $params['limit_rebate'] ? bcmul($params['limit_rebate'], 100) : 0,
            'limit_time' => $params['limit_time'] ?: 0,
            'return_name' => $params['return_name'],
            'return_address' => $params['return_address'],
            'return_phone' => $params['return_phone'],
            'is_income_tax' => (isset($params['is_income_tax']) && $params['is_income_tax'] == "true") ? true : false,
        ];

        if ($info) {
            $return = $this->entityRepository->updateOneBy(['company_id' => $companyId], $data);
        } else {
            $data['company_id'] = $companyId;
            $return = $this->entityRepository->create($data);
        }

        return $return;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
