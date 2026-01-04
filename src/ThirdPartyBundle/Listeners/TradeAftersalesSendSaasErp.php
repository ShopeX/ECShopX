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

use OrdersBundle\Traits\GetOrderServiceTrait;

use ThirdPartyBundle\Events\TradeAftersalesEvent;
use ThirdPartyBundle\Services\SaasErpCentre\OrderAftersalesService;
use ThirdPartyBundle\Services\SaasErpCentre\Request;
use ThirdPartyBundle\Services\SaasCertCentre\CertService;

class TradeAftersalesSendSaasErp extends BaseListeners implements ShouldQueue
{
    use GetOrderServiceTrait;

    protected $queue = 'default';
    public const METHOD = 'store.trade.aftersale.add';

    /**
     * 售后申请 退款退货
     *
     * @param  TradeAftersalesEvent  $event
     * @return bool
     */
    public function handle(TradeAftersalesEvent $event)
    {
        //清空缓存，防止数据不一致
        $em = app('registry')->getManager('default');
        $em->clear();

        $companyId = $event->entities['company_id'];
        $orderId = $event->entities['order_id'];
        $aftersalesBn = $event->entities['aftersales_bn'];

        $orderAftersalesService = new OrderAftersalesService();
        app('log')->debug("\n saaserp TradeAftersalesSendSaasErp event=>:".var_export($event->entities, 1));

        // 判断是否绑定了erp
        $certService = new CertService(false, $companyId);
        $erp_node_id = $certService->getErpBindNode();
        if (!$erp_node_id) {
            app('log')->debug("\n saaserp TradeAftersalesSendSaasErp trade_after_event companyId:".$companyId.",orderId:".$orderId.",msg:未开启SaasErp\n");
            return true;
        }

        try {
            $orderStruct = $orderAftersalesService->getOrderAfterInfo($companyId, $orderId, $aftersalesBn);
            app('log')->debug("\n saaserp TradeAftersalesSendSaasErp trade_after_orderStruct=>:".var_export($orderStruct, 1)."\n");
            if (!$orderStruct) {
                app('log')->debug("\n saaserp 获取订单售后信息失败:companyId:".$companyId.",orderId:".$orderId.",aftersalesType:");
                return true;
            }

            $status = $orderStruct['status'];
            $orderStruct['status'] = 1; //第一次提交强制待处理的状态

            $request = new Request($companyId);
            $result = $request->call(self::METHOD, $orderStruct);

            // 不是待处理的售后申请再更新下售后状态，需要分两步，要不然oms是不完整的流程
            if ($result['rsp'] == 'succ' && $status != 1) {
                $updateData = $orderAftersalesService->updateSaasErpAftersalesStatus($companyId, $aftersalesBn);
                $result = $request->call('ome.aftersale.status_update', $updateData);
            }
        } catch (\Exception $e) {
            $errorMsg = "\nsaaserp TradeAftersalesSendSaasErp method=>".self::METHOD." Error on line ".$e->getLine()." in ".$e->getFile().": <b>".$e->getMessage();
            app('log')->debug('saaserp TradeAftersalesSendSaasErp 请求失败:'. $errorMsg);
        }

        return true;
    }
}
