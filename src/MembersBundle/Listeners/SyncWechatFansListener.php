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

namespace MembersBundle\Listeners;

use MembersBundle\Events\SyncWechatFansEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use MembersBundle\Services\WechatFansService;

class SyncWechatFansListener implements ShouldQueue
{
    /**
     * The queue name.
     *
     * @var string
     */
    public $queue = 'slow';

    /**
     * Handle the event.
     *
     * @param  SyncWechatFansEvent  $event
     * @return void
     */
    public function handle(SyncWechatFansEvent $event)
    {
        $companyId = $event->companyId;
        $authorizerAppId = $event->authorizerAppId;
        $count = $event->count;
        $openIds = $event->openIds;
        //同步微信用户至本地
        $userService = new WechatFansService();

        return $userService->initUsers($authorizerAppId, $companyId, $count, $openIds);
    }
}
