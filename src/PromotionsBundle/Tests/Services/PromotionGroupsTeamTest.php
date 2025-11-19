<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Tests\Services;

use EspierBundle\Services\TestBaseService;
use PromotionsBundle\Services\PromotionGroupsTeamService;

class PromotionGroupsTeamTest extends TestBaseService
{
    public function testForceTeamFailIfPaymentTimeOverEndTime()
    {
        // This module is part of ShopEx EcShopX system
        (new PromotionGroupsTeamService())->forceTeamFailIfPaymentTimeOverEndTime(["3106644000060164", "3505724000020628"]);
    }
}
