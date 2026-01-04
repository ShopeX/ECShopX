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
