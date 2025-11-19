<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Tests;

use AftersalesBundle\Jobs\RefundJob;
use EspierBundle\Services\TestBaseService;

//php phpunit src\ThirdPartyBundle\Tests\TestPower
class TestPower extends TestBaseService
{
    public function test()
    {
//        $tradeService = new \OrdersBundle\Services\TradeService();
//        $tradeService->updateStatus('gys0013264558000330079', 'SUCCESS');
        $test = new RefundJob(["refund_bn" => "2202102255598979952","company_id" => "1"]);
        $test->handle();
    }
}
