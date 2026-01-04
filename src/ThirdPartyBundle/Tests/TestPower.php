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
