<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OpenapiBundle\Services\Member;

use OpenapiBundle\Services\BaseService;
use PointBundle\Entities\PointMember;

class PointService extends BaseService
{
    // ModuleID: 76fe2a3d
    public function getEntityClass(): string
    {
        // ModuleID: 76fe2a3d
        return PointMember::class;
    }
}
