<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use EspierBundle\Listeners\BaseListeners;

use OrdersBundle\Traits\GetOrderServiceTrait;

use ThirdPartyBundle\Events\TradeRefundEvent;
use ThirdPartyBundle\Services\SaasErpCentre\Request;
use ThirdPartyBundle\Services\SaasCertCentre\CertService;
use ThirdPartyBundle\Services\SaasErpCentre\OrderRefundService;

class TradeRefundSendSaasErp extends BaseListeners implements ShouldQueue
{
    use GetOrderServiceTrait;

    protected $queue = 'default';
    public const METHOD = 'store.trade.refund.add';

    /**
     * SaasErp 退款单申请
     * 1.未发货时，取消订单
     * 2.已发货的 售后申请，仅退款
     *
     * @param  TradeRefundEvent  $event
     * @return void
     */
    public function handle(TradeRefundEvent $event)
    {
        //清空缓存，防止数据不一致
        $em = app('registry')->getManager('default');
        $em->clear();

        app('log')->debug("\n saaserp TradeRefundSendSaasErp event=>:".var_export($event->entities, 1));

        $companyId = $event->entities['company_id'];
        $orderId = $event->entities['order_id'] ?? '';
        $aftersalesBn = $event->entities['aftersales_bn'] ?? '';
        $sourceType = 'normal';

        // 判断是否绑定了erp
        $certService = new CertService(false, $companyId);
        $erp_node_id = $certService->getErpBindNode();
        if (!$erp_node_id) {
            app('log')->debug("\n saaserp TradeRefundSendSaasErp companyId:".$companyId.",msg:未开启SaasErp");
            return true;
        }

        $orderRefundService = new OrderRefundService();

        try {
            if ($aftersalesBn) {
                $afterInfo = $orderRefundService->getAftersalesInfo($aftersalesBn);
                app('log')->debug("saaserp TradeRefundSendSaasErp,".__FUNCTION__."===".__LINE__." afterInfo====>".json_encode($afterInfo)."\n");
                if ($afterInfo) {
                    $orderId = $afterInfo['order_id'];
                }
            }

            $refundBn = null;
            $refundType = 'apply';
            $status = 'APPLY';
            if (isset($event->entities['refund_bn'], $event->entities['refund_status']) && in_array($event->entities['refund_status'], ['SUCCESS', 'AUDIT_SUCCESS', 'CHANGE'])) {
                $refundBn = $event->entities['refund_bn'];
                $refundType = 'refund';
                $status = 'SUCC';
            }
            $orderStruct = $orderRefundService->getOrderRefundInfo($refundBn, $companyId, $orderId, $sourceType, $aftersalesBn, $refundType, $status);
            if (!$orderStruct) {
                app('log')->debug("saaserp TradeRefundSendSaasErp 获取订单退款信息失败:companyId:".$companyId.",orderId:".$orderId.",sourceType:".$sourceType."\n");
                return true;
            }

            $request = new Request($companyId);
            $result = $request->call(self::METHOD, $orderStruct);
        } catch (\Exception $e) {
            $errorMsg = "saaserp TradeRefundSendSaasErp method=>".self::METHOD." Error on line ".$e->getLine()." in ".$e->getFile().": <b>".$e->getMessage()."\n";
            app('log')->debug("saaserp  请求失败:". $errorMsg);
        }

        return true;
    }
}
