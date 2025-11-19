<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Listeners\ShopexCrm;

use MembersBundle\Events\CreateMemberSuccessEvent;
use ThirdPartyBundle\Services\ShopexCrm\SyncSingleMemberService;
use EspierBundle\Listeners\BaseListeners;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyncAddMember extends BaseListeners implements ShouldQueue
{
    /**
     * 同步会员信息
     * @param CreateMemberSuccessEvent $event
     */
    public function handle(CreateMemberSuccessEvent $event)
    {
        if (empty(config('crm.crm_sync'))) {
            return true;
        }
        $syncSingleMemberService = new SyncSingleMemberService();
        $syncSingleMemberService->syncSingleMember($event->companyId, $event->userId);
    }
}
