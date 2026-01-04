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
