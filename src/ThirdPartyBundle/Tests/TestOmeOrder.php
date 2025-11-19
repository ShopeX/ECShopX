<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Tests;

use EspierBundle\Services\TestBaseService;
use OrdersBundle\Events\TradeFinishEvent;
use ThirdPartyBundle\Listeners\TradeFinishSendSaasErp;

use OrdersBundle\Entities\Trade;

//php phpunit src\ThirdPartyBundle\Tests\TestOmeOrder
class TestOmeOrder extends TestBaseService
{
    public function test()
    {
        global $argv;
        echo("\n".date('Ymd H:i:s')."\n");

        $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);

        $companyId = $argv[2] ?? '1';
        $tradeId = $argv[3] ?? '88888883285659000170000';
        $filter = [
            'trade_id' => $tradeId,
        ];
        $eventData = $tradeRepository->findOneBy($filter);

        $tradeFinishSendSaasErp = new TradeFinishSendSaasErp();
        $tradeFinishSendSaasErp->handle(new TradeFinishEvent($eventData));
    }
}
