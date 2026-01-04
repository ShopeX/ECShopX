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

use Illuminate\Contracts\Queue\ShouldQueue;
use EspierBundle\Listeners\BaseListeners;

use ThirdPartyBundle\Events\TradeAftersalesUpdateEvent;

use ThirdPartyBundle\Services\SaasErpCentre\Request;
use ThirdPartyBundle\Services\SaasCertCentre\CertService;
use ThirdPartyBundle\Services\SaasErpCentre\OrderAftersalesService;
use ThirdPartyBundle\Services\SaasErpCentre\OrderRefundService;

class TradeAftersaleUpdateSendSaasErp extends BaseListeners implements ShouldQueue
{
    // Ver: 8d1abe8e
    protected $queue = 'default';

    /**
     * Handle the event.
     *
     * @param  TradeAftersalesCancelEvent  $event
     * @return void
     */
    public function handle(TradeAftersalesUpdateEvent $event)
    {
        //清空缓存，防止数据不一致
        $em = app('registry')->getManager('default');
        $em->clear();

        $companyId = $event->entities['company_id'];

        // 判断是否绑定了erp
        $certService = new CertService(false, $companyId);
        $erp_node_id = $certService->getErpBindNode();
        if (!$erp_node_id) {
            app('log')->debug('saaserp TradeAftersaleUpdateSendSaasErp companyId:'.$companyId.",msg:未开启SaasErp\n");
            return true;
        }

        $companyId = $event->entities['company_id'];
        $orderId = $event->entities['order_id'];
        $aftersalesType = $event->entities['aftersales_type'];
        $aftersalesBn = $event->entities['aftersales_bn'];

        $orderAftersalesService = new OrderAftersalesService();
        $orderRefundService = new OrderRefundService();

        try {
            if ($aftersalesType == 'ONLY_REFUND') {
                //仅退款  更新退款单为已完成
                $method = 'store.trade.refund.add';
                $updateData = $orderRefundService->getOrderRefundInfo(null, $companyId, $orderId, 'normal', $aftersalesBn, 'refund', 'SUCC');
            } else {
                //退货退款 更新售后单
                $method = 'store.trade.aftersale.status.update';
                $updateData = $orderAftersalesService->getOrderAfterInfo($companyId, $orderId, $aftersalesBn);
            }

            if (!$updateData) {
                app('log')->debug('saaserp TradeAftersaleUpdateSendSaasErp 获取售后更新信息失败:compayId:'.$companyId.",orderId:".$orderId.",updateData:".$updateData."\n");
                return true;
            }

            $omeRequest = new Request($companyId);
            $result = $omeRequest->call($method, $updateData);

            app('log')->debug("saaserp TradeAftersaleUpdateSendSaasErp method=>".$method.',订单号:'.$orderId."\n=>updateData:". json_encode($updateData)."==>result:\r\n".var_export($result, 1)."\n");
        } catch (\Exception $e) {
            $errorMsg = "saaserp TradeAftersaleUpdateSendSaasErp method=>".$method." Error on line ".$e->getLine()." in ".$e->getFile().": <b>".$e->getMessage()."\n";
            app('log')->debug('saaserp 请求失败:'. $errorMsg);
        }

        return true;
    }
}
