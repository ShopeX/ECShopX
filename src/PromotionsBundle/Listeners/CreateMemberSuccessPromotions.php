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
