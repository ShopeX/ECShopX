<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OpenapiBundle\Filter\Member;

use OpenapiBundle\Filter\BaseFilter;

class MemberOperateLogFilter extends BaseFilter
{
    protected function init()
    {
        // 根据操作时间做筛选
        $this->setUserIdByMobile();
        // 根据操作时间做筛选
        $this->setTimeRange("created|gte", "created|lte");
    }
}
