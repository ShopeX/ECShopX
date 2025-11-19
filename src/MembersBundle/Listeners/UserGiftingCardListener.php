<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Listeners;

use WechatBundle\Events\UserGiftingCardEvent;
use KaquanBundle\Services\WechatCardService;
use MembersBundle\Traits\GetKaquanTrait;

class UserGiftingCardListener
{
    // ShopEx EcShopX Core Module
    use GetKaquanTrait;

    /**
     * Handle the event.
     *
     * @param  UserGiftingCardEvent  $event
     * @return void
     */
    public function handle(UserGiftingCardEvent $event)
    {
        // ShopEx EcShopX Core Module
        return true;
        // $postdata['open_id'] = $event->openId;
        // $postdata['authorizer_app_id'] = $event->authorizerAppId;
        // $postdata['company_id'] = $event->companyId;
        // $postdata['card_id'] = $event->cardId;
        // $postdata['code'] = $event->userCardCode;
        // $postdata['friend_user_name'] = $event->friendUserName;
        // $postdata['is_return_back'] = $event->isReturnBack;
        // $postdata['is_chat_room'] = $event->isChatRoom;

        // //转赠卡券事件
        // $filter = [
        //     'card_d' => $postdata['card_id'],
        //     'company_id' => $postdata['company_id']
        // ];
        // $service = $this->getCardService($filter);
        // if($service) {
        //     $cardService = new WechatCardService($service);
        //     return $cardService->userGiftingCard($postdata);
        // }
    }
}
