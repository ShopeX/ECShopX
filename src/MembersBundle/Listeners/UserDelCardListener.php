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
