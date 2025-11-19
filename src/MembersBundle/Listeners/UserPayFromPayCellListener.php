<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Listeners;

use WechatBundle\Events\UserPayFromPayCellEvent;
use KaquanBundle\Services\WechatCardService;
use MembersBundle\Traits\GetKaquanTrait;

class UserPayFromPayCellListener
{
    use GetKaquanTrait;

    /**
     * Handle the event.
     *
     * @param  UserPayFromPayCellEvent  $event
     * @return void
     */
    public function handle(UserPayFromPayCellEvent $event)
    {
        return true;
        // $postdata['open_id'] = $event->openId;
        // $postdata['company_id'] = $event->companyId;
        // $postdata['authorizer_app_id'] = $event->authorizerAppId;
        // $postdata['card_id'] = $event->cardId;
        // $postdata['code'] = $event->userCardCode;
        // $postdata['trans_id'] = $event->transId;
        // $postdata['location_id'] = $event->LocationId;
        // $postdata['fee'] = $event->fee;
        // $postdata['original_fee'] = $event->originalFee;

        // //卡券买单事件
        // $filter = [
        //     'card_id' => $postdata['card_id'],
        //     'company_id' => $postdata['company_id']
        // ];
        // $service = $this->getCardService($filter);
        // if($service) {
        //     $cardService = new WechatCardService($service);
        //     return $cardService->userPayFromPayCell($postdata);
        // }
    }
}
