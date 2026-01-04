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

namespace CommunityBundle\Services;

use CommunityBundle\Entities\CommunityChief;
use CommunityBundle\Entities\CommunityChiefZiti;
use CommunityBundle\Repositories\CommunityChiefRepository;
use CommunityBundle\Repositories\CommunityChiefZitiRepository;
use Dingo\Api\Exception\ResourceException;

class CommunityChiefZitiService
{
    /**
     * @var CommunityChiefZitiRepository
     */
    private $entityRepository;
    /**
     * @var CommunityChiefRepository
     */
    private $entityChiefRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(CommunityChiefZiti::class);
        $this->entityChiefRepository = app('registry')->getManager('default')->getRepository(CommunityChief::class);
    }

    /**
     * 获取用户的自提列表
     */
    public function getChiefZitiList($chief_id)
    {
        return $this->entityRepository->getLists(['chief_id' => $chief_id]);
    }

    /**
     * 添加团长自提点
     * @param $user_id
     * @param $params
     * @return array
     */
    public function createChiefZiti($chief_id, $params)
    {
        $params['chief_id'] = $chief_id;
        $result = $this->entityRepository->create($params);

        return $result;
    }

    /**
     * 修改自提点
     * @param $user_id
     * @param $ziti_id
     * @param $params
     * @return array
     */
    public function updateChiefZiti($ziti_id, $params)
    {
        return $this->entityRepository->updateOneBy(['ziti_id' => $ziti_id], $params);
    }

    /**
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
