<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Listeners;

use MembersBundle\Events\CreateMemberSuccessEvent;
use PromotionsBundle\Services\DistributorPromotionService;
use EspierBundle\Listeners\BaseListeners;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateMemberSuccessPromotions extends BaseListeners implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  TradeFinishEvent  $event
     * @return void
     */
    public function handle(CreateMemberSuccessEvent $event)
    {
        if (!$event->ifRegisterPromotion) {
            return;
        }

        $filter = [
            'company_id' => $event->companyId,
            'distributor_id' => $event->distributorId,
        ];
        $distributorPromotionsService = new DistributorPromotionService();
        $distributorPromotionsService->executionMarketing($filter, $event->userId, $event->mobile);
    }
}
