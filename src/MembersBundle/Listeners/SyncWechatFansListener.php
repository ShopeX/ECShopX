<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
