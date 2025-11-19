<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
