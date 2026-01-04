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

namespace ThirdPartyBundle\Listeners;

// use OrdersBundle\Events\TradeFinishEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use EspierBundle\Listeners\BaseListeners;

use ThirdPartyBundle\Events\TradeRefundCancelEvent;

use ThirdPartyBundle\Services\SaasErpCentre\Request;
use ThirdPartyBundle\Services\SaasCertCentre\CertService;
use ThirdPartyBundle\Services\SaasErpCentre\OrderService;

class TradeRefundCancelSendSaasErp extends BaseListeners implements ShouldQueue
{
    protected $queue = 'default';

    /**
     * Handle the event.
     *
     * @param  TradeAftersalesCancelEvent  $event
     * @return void
     */
    public function handle(TradeRefundCancelEvent $event)
    {
        //清空缓存，防止数据不一致
        $em = app('registry')->getManager('default');
        $em->clear();

        app('log')->debug('saaserp TradeRefundCancelSendSaasErp event=>:'.var_export($event->entities, 1)."\n");

        $companyId = $event->entities['company_id'];

        // 判断是否绑定了erp
        $certService = new CertService(false, $companyId);
        $erp_node_id = $certService->getErpBindNode();
        if (!$erp_node_id) {
            app('log')->debug('saaserp TradeRefundCancelSendSaasErp companyId:'.$companyId.",msg:未开启SaasErp\n");
            return true;
        }

        $orderId = $event->entities['order_id'];
        $refundBn = $event->entities['refund_bn'];

        $orderService = new OrderService();

        try {
            //取消订单，拒绝退款
            $cancelData = $orderService->cancelSaasErpRefund($companyId, $orderId, $refundBn);
            $method = 'store.trade.refund.add';

            $omeRequest = new Request($companyId);
            $result = $omeRequest->call($method, $cancelData);

            app('log')->debug("saaserp TradeRefundCancelSendSaasErp method=>".$method.',订单号:'.$orderId."\n=>cancelData:". json_encode($cancelData)."==>result:\r\n".var_export($result, 1)."\n");
        } catch (\Exception $e) {
            $errorMsg = "saaserp TradeRefundCancelSendSaasErp method=>".$method." Error on line ".$e->getLine()." in ".$e->getFile().": <b>".$e->getMessage()."\n";
            app('log')->debug('saaserp 请求失败:'. $errorMsg);
        }

        return true;
    }
}
