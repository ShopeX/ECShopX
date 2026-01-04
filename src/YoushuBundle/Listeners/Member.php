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
