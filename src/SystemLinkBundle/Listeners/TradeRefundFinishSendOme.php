<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemLinkBundle\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;

use EspierBundle\Listeners\BaseListeners;

use SystemLinkBundle\Events\TradeRefundFinishEvent;

use SystemLinkBundle\Services\ShopexErp\OrderRefundService;

use SystemLinkBundle\Services\ShopexErp\Request as OmeRequest;

class TradeRefundFinishSendOme extends BaseListeners implements ShouldQueue
{
    protected $queue = 'default';

    /**
     * Handle the event.
     *
     * @param  TradeRefundFinishEvent  $event
     * @return void
     */
    public function handle(TradeRefundFinishEvent $event)
    {
        $method = 'ome.refund.add';

        //退款状态回打OMS
        $orderRefundService = new OrderRefundService();
        $omeRefundData = $orderRefundService->refundSendOme($event->entities);

        $omeRequest = new OmeRequest($event->entities['company_id']);
        $result = $omeRequest->call($method, $omeRefundData);
        app('log')->debug($method."\n =>omeRefundFinishData:".var_export($omeRefundData, 1)."\n =>result:". var_export($result, 1));
        return true;
    }
}
