<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
