<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Services;

// use MembersBundle\Services\WechatDiscountCardService;

class WechatCardService
{
    /**
     * WechatCardService 构造函数.
     */
    public function __construct()
    {
        // $this->wechatDiscountCard = new WechatDiscountCardService();
    }

    /**
     * 会员领取卡券
     */
    public function userGetCard($eventData)
    {
//        $cardType = $cardRel->getCardType();
//        if($cardType == "MEMBER_CARD") {
//            return true;
//        } elseif (in_array($cardType,['gift','cash','discount','groupon','general_coupon'])) {
//            return $this->wechatDiscountCard->userGetCard($eventData);
//        }
    }

    /**
     * 会员转赠卡券
     */
    public function userGiftingCard($eventData)
    {
//        $cardType = $cardRel->getCardType();
//        if($cardType == "MEMBER_CARD") {
//            return true;
//        } elseif (in_array($cardType,['gift','cash','discount','groupon','general_coupon'])) {
//            return $this->wechatDiscountCard->userGiftingCard($eventData);
//        }
    }

    /**
     * 会员删除卡券
     */
    public function userDelCard($eventData)
    {
//        $cardType = $cardRel->getCardType();
//        if($cardType == "MEMBER_CARD") {
//            return true;
//        } elseif (in_array($cardType,['gift','cash','discount','groupon','general_coupon'])) {
//            return $this->wechatDiscountCard->userDelCard($eventData);
//        }
    }

    /**
     *  会员核销卡券
     */
    public function userConsumeCard($eventData)
    {
//        $cardType = $cardRel->getCardType();
//        if($cardType == "MEMBER_CARD") {
//            return true;
//        } elseif (in_array($cardType,['gift','cash','discount','groupon','general_coupon'])) {
//            return $this->wechatDiscountCard->userConsumeCard($eventData);
//        }
    }

    /**
     * 会员卡券买单
     */
    public function userPayFromPayCell($eventData)
    {
//        $cardType = $cardRel->getCardType();
//        if($cardType == "MEMBER_CARD") {
//            return true;
//        } elseif (in_array($cardType,['gift','cash','discount','groupon','general_coupon'])) {
//            return $this->wechatDiscountCard->userPayFromPayCell($eventData);
//        }
    }
}
