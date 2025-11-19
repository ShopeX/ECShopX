<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OpenapiBundle\Filter\Member;

use OpenapiBundle\Filter\BaseFilter;

class MemberPointFilter extends BaseFilter
{
    protected function init()
    {
        $this->setUserIdByMobile();
        $this->setTimeRange("created|gte", "created|lte");
    }
}
