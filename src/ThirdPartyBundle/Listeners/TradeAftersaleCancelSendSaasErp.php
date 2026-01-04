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

use ThirdPartyBundle\Events\TradeAftersalesCancelEvent;

use OrdersBundle\Traits\GetOrderServiceTrait;

use ThirdPartyBundle\Services\SaasErpCentre\Request;
use ThirdPartyBundle\Services\SaasCertCentre\CertService;
use ThirdPartyBundle\Services\SaasErpCentre\OrderAftersalesService;

use AftersalesBundle\Services\AftersalesService;

class TradeAftersaleCancelSendSaasErp extends BaseListeners implements ShouldQueue
{
    use GetOrderServiceTrait;

    protected $queue = 'default';

    /**
     * Handle the event.
     *
     * @param  TradeAftersalesCancelEvent  $event
     * @return void
     */
    public function handle(TradeAftersalesCancelEvent $event)
    {
        //清空缓存，防止数据不一致
        $em = app('registry')->getManager('default');
        $em->clear();

        app('log')->debug('saaserp TradeAftersaleCancelSendSaasErp event=>:'.var_export($event->entities, 1)."\n");

        $companyId = $event->entities['company_id'];

        // 判断是否绑定了erp
        $certService = new CertService(false, $companyId);
        $erp_node_id = $certService->getErpBindNode();
        if (!$erp_node_id) {
            app('log')->debug('saaserp TradeAftersaleCancelSendSaasErp companyId:'.$companyId.",msg:未开启SaasErp\n");
            return true;
        }

        //编辑售后
        $aftersalesService = new AftersalesService();
        $afterInfo = $aftersalesService->aftersalesRepository->get(['aftersales_bn' => $event->entities['aftersales_bn'], 'company_id' => $event->entities['company_id']]);
        app('log')->debug('saaserp TradeAftersaleCancelSendSaasErp trade_after_afterDetail=>:'.var_export($afterInfo, 1)."\n");
        if (!$afterInfo) {
            return false;
        }

        $companyId = $afterInfo['company_id'];
        $orderId = $afterInfo['order_id'];
        //$itemId = $afterInfo['item_id'];
        $aftersalesType = $event->entities['aftersales_type'];
        $aftersalesBn = $event->entities['aftersales_bn'];

        $orderAftersalesService = new OrderAftersalesService();

        try {
            if ($aftersalesType == 'ONLY_REFUND') {
                //仅退款  撤销退款申请单 更新退款单 apply FAIL
                $cancelData = $orderAftersalesService->cancelSaasErpRefund($companyId, $orderId, $aftersalesBn);
                $method = 'store.trade.refund.add';
            } else {
                //退货退款 撤销售后单
                $cancelData = $orderAftersalesService->getOrderAfterInfo($companyId, $orderId, $aftersalesBn);
                $cancelData['status'] = 5;
                $method = 'store.trade.aftersale.status.update';
            }

            app('log')->debug('saaserp TradeAftersaleCancelSendSaasErp trade_after_cancel_method=>:'.var_export($method, 1)."\n");
            app('log')->debug('saaserp TradeAftersaleCancelSendSaasErp trade_after_cancel_cancelData=>:'.var_export($cancelData, 1)."\n");

            if (!$cancelData) {
                app('log')->debug('saaserp TradeAftersaleCancelSendSaasErp 获取售后撤销信息失败:compayId:'.$companyId.",orderId:".$orderId.",cancelData:".$cancelData."\n");
                return true;
            }

            $omeRequest = new Request($companyId);
            $result = $omeRequest->call($method, $cancelData);

            app('log')->debug("saaserp TradeAftersaleCancelSendSaasErp method=>".$method.',订单号:'.$orderId."\n=>cancelData:". json_encode($cancelData)."==>result:\r\n".var_export($result, 1)."\n");
        } catch (\Exception $e) {
            $errorMsg = "saaserp TradeAftersaleCancelSendSaasErp method=>".$method." Error on line ".$e->getLine()." in ".$e->getFile().": <b>".$e->getMessage()."\n";
            app('log')->debug('saaserp 请求失败:'. $errorMsg);
        }

        return true;
    }
}
