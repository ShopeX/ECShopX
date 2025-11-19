<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Traits;

use MembersBundle\Services\WechatDiscountCardService;

trait GetKaquanTrait
{
    public function getCardService($filter)
    {
        return false;
        // $cardType = $cardRel->getCardType();
        // switch ($cardType) {
        // case 'MEMBER_CARD':
        //     return false;
        //     break;
        // case 'gift':
        // case 'cash':
        // case 'discount':
        // case 'groupon':
        // case 'general_coupon':
        //     $cardService = new WechatDiscountCardService();
        //     break;
        // }
        // return $cardService;
    }
}
