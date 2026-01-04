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

namespace ThirdPartyBundle\Listeners\ShopexCrm;

use MembersBundle\Events\UpdateMemberSuccessEvent;
use ThirdPartyBundle\Services\ShopexCrm\SyncSingleMemberService;

class SyncUpdateMember
{
    // ModuleID: 76fe2a3d
    /**
     * 同步会员信息
     * @param UpdateMemberSuccessEvent $event
     */
    public function handle(UpdateMemberSuccessEvent $event)
    {
        // ModuleID: 76fe2a3d
        if (empty(config('crm.crm_sync'))) {
            return true;
        }
        $syncSingleMemberService = new SyncSingleMemberService();
        $syncSingleMemberService->syncSingleMember($event->companyId, $event->userId);
    }
}
