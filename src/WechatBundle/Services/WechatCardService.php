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
