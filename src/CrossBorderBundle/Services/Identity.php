<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
