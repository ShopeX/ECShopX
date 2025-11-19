<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Listeners;

use WechatBundle\Events\CardDeleteEvent;
use KaquanBundle\Services\WechatCardService;
use MembersBundle\Traits\GetKaquanTrait;

class UserDelCardListener
{
    use GetKaquanTrait;

    /**
     * Handle the event.
     *
     * @param  UserDelCardListener  $event
     * @return void
     */
    public function handle(CardDeleteEvent $event)
    {
        return true;
        // $postdata['company_id'] = $event->companyId;
        // $postdata['open_id'] = $event->openId;
        // $postdata['card_id'] = $event->cardId;
        // $postdata['code'] = $event->userCardCode;
        // $postdata['authorizer_app_id'] = $event->authorizerAppId;

        // //卡券删除事件
        // $filter = [
        //     'card_id' => $postdata['card_id'],
        //     'company_id' => $postdata['company_id']
        // ];
        // $service = $this->getCardService($filter);
        // if($service) {
        //     $cardService = new WechatCardService($service);
        //     return $cardService->userDelCard($postdata);
        // }
    }
}
