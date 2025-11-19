<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
