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

use ThirdPartyBundle\Events\TradeAftersalesLogiEvent;

use ThirdPartyBundle\Services\SaasErpCentre\Request;
use ThirdPartyBundle\Services\SaasCertCentre\CertService;
use ThirdPartyBundle\Services\SaasErpCentre\OrderAftersalesService;

use OrdersBundle\Traits\GetOrderServiceTrait;

class TradeAfterLogiSendSaasErp extends BaseListeners implements ShouldQueue
{
    use GetOrderServiceTrait;

    protected $queue = 'default';
    public const METHOD = 'store.trade.aftersale.logistics.update';

    /**
     * Handle the event.
     *
     * @param  TradeAftersalesLogiEvent  $event
     * @return void
     */
    public function handle(TradeAftersalesLogiEvent $event)
    {
        //清空缓存，防止数据不一致
        $em = app('registry')->getManager('default');
        $em->clear();

        app('log')->debug("\n saaserp TradeAfterLogiSendSaasErp event=>:".var_export($event, 1)."\n");
        $companyId = $event->entities['company_id'];

        // 判断是否绑定了erp
        $certService = new CertService(false, $companyId);
        $erp_node_id = $certService->getErpBindNode();
        if (!$erp_node_id) {
            app('log')->debug("\n saaserp TradeAfterLogiSendSaasErp companyId:".$companyId.",msg:未开启SaasErp");
            return true;
        }

        $orderAftersalesService = new OrderAftersalesService();

        try {
            $afterLogistics = $orderAftersalesService->getAfterLogistics($event->entities);

            app('log')->debug("\n saaserp TradeAfterLogiSendSaasErp afterLogistics=>:".var_export($afterLogistics, 1)."\n");

            if (!$afterLogistics) {
                app('log')->debug('获取售后物流信息失败');
                return true;
            }

            $request = new Request($companyId);
            $result = $request->call(self::METHOD, $afterLogistics);

            app('log')->debug(self::METHOD." TradeAfterLogiSendSaasErp result=>". var_export($result, 1)."\n");
        } catch (\Exception $e) {
            $errorMsg = "\n TradeAfterLogiSendSaasErp saaserp method=>".self::METHOD." Error on line ".$e->getLine()." in ".$e->getFile().": <b>".$e->getMessage();
            app('log')->debug('saaserp 请求失败:'. $errorMsg);
        }

        return true;
    }
}
