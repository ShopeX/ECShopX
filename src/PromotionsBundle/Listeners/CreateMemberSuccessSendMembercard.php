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
