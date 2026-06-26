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

namespace EspierBundle\Commands;

use Illuminate\Console\Command;
use ThirdPartyBundle\Events\TradeAftersalesEvent;
use ThirdPartyBundle\Listeners\TradeAftersalesSendSaasErp;

class SendAftersalesToSaasOmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oms:send_aftersales_saas {company_id} {order_id} {aftersales_bn}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '推送售后单到 OMS（矩阵 OMS，store.trade.aftersale.add，同步直调与 TradeAftersalesSendSaasErp 一致）';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $companyId = $this->argument('company_id');
        $orderId = $this->argument('order_id');
        $aftersalesBn = $this->argument('aftersales_bn');

        if (!$companyId || !$orderId || !$aftersalesBn) {
            $this->error('参数 company_id、order_id、aftersales_bn 均必填。');

            return 1;
        }

        $eventData = [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'aftersales_bn' => $aftersalesBn,
        ];

        $listener = new TradeAftersalesSendSaasErp();
        $listener->handle(new TradeAftersalesEvent($eventData));
        $this->info('已执行 TradeAftersalesSendSaasErp，请查看日志与 OMS 结果。');

        return 0;
    }
}
