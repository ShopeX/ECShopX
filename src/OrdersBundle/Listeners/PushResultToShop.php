<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
