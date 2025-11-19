<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Tests;

use EspierBundle\Services\TestBaseService;
use ThirdPartyBundle\Events\TradeAftersalesEvent;
use ThirdPartyBundle\Listeners\TradeAftersalesSendSaasErp;

//php phpunit src\ThirdPartyBundle\Tests\TestOmsAftersale
class TestOmsAftersale extends TestBaseService
{
    public function test()
    {
        global $argv;
        echo("\n".date('Ymd H:i:s')."\n");

        $eventData = [
            'company_id' => $argv[2] ?? '1',
            'order_id' => $argv[3] ?? '3259822000210261',
            'aftersales_bn' => $argv[4] ?? '202012091029710',
        ];
        $tradeAftersalesSendSaasErp = new TradeAftersalesSendSaasErp();
        $tradeAftersalesSendSaasErp->handle(new TradeAftersalesEvent($eventData));
    }
}
