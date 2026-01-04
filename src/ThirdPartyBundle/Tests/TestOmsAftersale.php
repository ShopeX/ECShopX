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
