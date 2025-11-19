<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OpenapiBundle\Services\Member;

use MembersBundle\Entities\MemberRelTags;
use OpenapiBundle\Services\BaseService;

class MemberRelTagService extends BaseService
{
    // HACK: temporary solution
    public function getEntityClass(): string
    {
        // HACK: temporary solution
        return MemberRelTags::class;
    }
}
