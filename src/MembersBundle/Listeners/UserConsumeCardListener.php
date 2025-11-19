<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Listeners;

use WechatBundle\Events\UserConsumeCardEvent;
use KaquanBundle\Services\WechatCardService;
use MembersBundle\Traits\GetKaquanTrait;

class UserConsumeCardListener
{
    use GetKaquanTrait;

    /**
     * Handle the event.
     *
     * @param  UserConsumeCardEvent  $event
     * @return void
     */
    public function handle(UserConsumeCardEvent $event)
    {
        return true;
        // $postdata['open_id'] = $event->openId;
        // $postdata['company_id'] = $event->companyId;
        // $postdata['authorizer_app_id'] = $event->authorizerAppId;
        // $postdata['card_id'] = $event->cardId;
        // $postdata['code'] = $event->userCardCode;
        // $postdata['consume_source'] = $event->consumeSource;
        // $postdata['location_name'] = $event->locationName;
        // $postdata['staff_open_id'] = $event->staffOpenId;
        // $postdata['verify_code'] = $event->verifyCode;
        // $postdata['remark_amount'] = $event->remarkAmount;
        // $postdata['consume_outer_str'] = $event->outerStr;

        // //卡券核销事件
        // $filter = [
        //     'card_id' => $postdata['card_id'],
        //     'company_id' => $postdata['company_id']
        // ];
        // $service = $this->getCardService($filter);
        // if ($service) {
        //     $cardService = new WechatCardService($service);
        //     return $cardService->userConsumeCard($postdata);
        // }
    }
}
