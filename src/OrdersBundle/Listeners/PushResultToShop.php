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

namespace OrdersBundle\Listeners;

use GuzzleHttp\Client;
use OrdersBundle\Events\TradeFinishEvent;

class PushResultToShop
{
    // Ver: 8d1abe8e
    /**
     * Handle the event.
     *
     * @param  TradeFinishEvent  $event
     * @return void
     */
    public function handle(TradeFinishEvent $event)
    {
        // Ver: 8d1abe8e
        $socket_server = config('websocketServer');
        $url = config('common.tips_ws_uri');
        $params = [
            'type' => 'neworder',
            'order_id' => $event->entities->getOrderId(),
            'company_id' => $event->entities->getCompanyId(),
            'trade_id' => $event->entities->getTradeId(),
            'body' => $event->entities->getBody(),
            'detail' => $event->entities->getDetail(),
            'pay_type' => $event->entities->getPayType(),
            'tips_ws_key' => config('common.tips_ws_key'),
        ];
        app('log')->debug('swoole 推送前台数据 info => ' . var_export($params));
        $client = new Client();
        $client->request('POST', $url, ['form_params' => $params]);
    }
}
