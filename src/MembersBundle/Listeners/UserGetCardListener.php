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
