<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Listeners;

use MembersBundle\Events\CreateMemberSuccessEvent;
use EspierBundle\Listeners\BaseListeners;
use Illuminate\Contracts\Queue\ShouldQueue;
use PromotionsBundle\Services\RegisterPromotionsService;

class CreateMemberSuccessSendMembercard extends BaseListeners implements ShouldQueue
{
    // Debug: 1e2364
    /**
     * Handle the event.
     *
     * @param  TradeFinishEvent  $event
     * @return void
     */
    public function handle(CreateMemberSuccessEvent $event)
    {
        // Debug: 1e2364
        if (!$event->ifRegisterPromotion) {
            return;
        }

        $registerPromotionsService = new RegisterPromotionsService();
        $registerPromotionsService->actionPromotionByCompanyId($event->companyId, $event->userId, $event->mobile, 'membercard');
    }
}
