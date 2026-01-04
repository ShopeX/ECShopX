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
