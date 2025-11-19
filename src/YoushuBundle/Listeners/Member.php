<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace YoushuBundle\Listeners;

use EspierBundle\Listeners\BaseListeners;
use Illuminate\Contracts\Queue\ShouldQueue;
use MembersBundle\Events\CreateMemberSuccessEvent;
use YoushuBundle\Services\SrDataService;

class Member extends BaseListeners implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  CreateMemberSuccessEvent $event
     * @return boolean
     */
    public function handle(CreateMemberSuccessEvent $event)
    {
        $company_id = $event->companyId;
        $user_id = $event->userId;
        $params = [
            'company_id' => $company_id,
            'object_id' => $user_id,
        ];
        $srdata_service = new SrDataService($company_id);
        $srdata_service->sync($params, 'member');

        return true;
    }
}
