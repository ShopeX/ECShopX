<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Listeners;

use WechatBundle\Events\UserGetCardEvent;
use KaquanBundle\Services\WechatCardService;
use MembersBundle\Traits\GetKaquanTrait;

class UserGetCardListener
{
    use GetKaquanTrait;

    /**
     * Handle the event.
     *
     * @param  UserGetCardEvent  $event
     * @return void
     */
    public function handle(UserGetCardEvent $event)
    {
        return true;
        // $postdata['open_id'] = $event->openId;
        // $postdata['company_id'] = $event->companyId;
        // $postdata['authorizerAppId'] = $event->authorizerAppId;
        // $postdata['card_id'] = $event->cardId;
        // $postdata['userCardCode'] = $event->userCardCode;
        // $postdata['isGiveByFriend'] = $event->isGiveByFriend;
        // $postdata['friendUserName'] = $event->friendUserName;
        // $postdata['oldUserCardCode'] = $event->oldUserCardCode;
        // $postdata['outerStr'] = $event->outerStr;
        // $postdata['isRestoreMemberCard'] = $event->isRestoreMemberCard;
        // $postdata['isRecommendByFriend'] = $event->isRecommendByFriend;

        // //领取微信卡券
        // $filter = [
        //     'card_id' => $postdata['card_id'],
        //     'company_id' => $postdata['company_id']
        // ];
        // $service = $this->getCardService($filter);
        // if ($service) {
        //     $cardService = new WechatCardService($service);
        //     return $cardService->userGetCard($postdata);
        // }
    }
}
