<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OpenapiBundle\Filter\Member;

use OpenapiBundle\Filter\BaseFilter;

class MemberCardGradeFilter extends BaseFilter
{
    protected function init()
    {
        // 判断是否有等级ID
        if (isset($this->requestData["grade_id"])) {
            $this->filter["grade_id"] = $this->requestData["grade_id"];
        }
    }
}
