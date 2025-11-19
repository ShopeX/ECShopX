<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Traits;

use MembersBundle\Services\MemberService;

trait GetUserIdByMobileTrait
{
    public function checkMobile($filter)
    {
        $memberService = new MemberService();
        if (isset($filter['mobile']) && $filter['mobile'] && $filter['company_id']) {
            $userId = $memberService->getUserIdByMobile($filter['mobile'], $filter['company_id']);
            if ($userId) {
                $filter['user_id'] = $userId;
                unset($filter['mobile']);
            }
        }
        return $filter;
    }
}
