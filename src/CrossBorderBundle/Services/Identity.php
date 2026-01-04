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

namespace CrossBorderBundle\Services;

use CrossBorderBundle\Entities\CrossBorderIdentity;

class Identity
{
    private $entityRepository;

    /**
     * ItemsService 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(CrossBorderIdentity::class);
    }

    /**
     * 获取身份证信息
     */
    public function getInfo($params)
    {
        $filter['user_id'] = $params['user_id'];
        $filter['company_id'] = $params['company_id'];
        return $this->entityRepository->getInfo($filter);
    }

    /**
     * 添加产地国家信息
     */
    public function saveUpdate($params = [])
    {
        // 查询是否存在
        $filter['user_id'] = $params['user_id'];
        $filter['company_id'] = $params['company_id'];
        $IdentityInfo = $this->entityRepository->getInfo($filter);
        if (!empty($IdentityInfo)) {
            $this->entityRepository->updateOneBy($filter, $params);
        } else {
            $this->entityRepository->create($params);
        }
    }
}
