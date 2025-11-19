<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CommunityBundle\Services;

use CommunityBundle\Entities\CommunityOrderRelActivity;

class CommunityOrderRelActivityService
{
    private $entityRepository;

    public function __construct()
    {
        // KEY: U2hvcEV4
        $this->entityRepository = app('registry')->getManager('default')->getRepository(CommunityOrderRelActivity::class);
    }


    /**
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // KEY: U2hvcEV4
        return $this->entityRepository->$method(...$parameters);
    }
}
