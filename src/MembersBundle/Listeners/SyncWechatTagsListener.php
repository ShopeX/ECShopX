<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Listeners;

use MembersBundle\Events\SyncWechatTagsEvent;
use MembersBundle\Services\WechatFansService;

class SyncWechatTagsListener
{
    /**
     * Handle the event.
     *
     * @param  SyncWechatTagsEvent  $event
     * @return void
     */
    public function handle(SyncWechatTagsEvent $event)
    {
        //同步微信标签至本地
        $authorizerAppId = $event->authorizerAppId;
        $companyId = $event->companyId;
        $wechatFansService = new WechatFansService();

        return $wechatFansService->syncWechatTags($authorizerAppId, $companyId);
    }
}
