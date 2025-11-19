<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemLinkBundle\Listeners;

// use OrdersBundle\Events\TradeFinishEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use EspierBundle\Listeners\BaseListeners;

use SystemLinkBundle\Events\TradeRefundEvent;

use OrdersBundle\Traits\GetOrderServiceTrait;

use SystemLinkBundle\Services\ShopexErp\OrderRefundService;

use SystemLinkBundle\Services\ShopexErp\Request;

use SystemLinkBundle\Services\ThirdSettingService;

class TradeRefundSendOme extends BaseListeners implements ShouldQueue
{
    // class TradeRefundSendOme extends BaseListeners {

    use GetOrderServiceTrait;

    protected $queue = 'default';

    /**
     * Handle the event.
     *
     * @param  TradeRefundEvent $event
     * @return void
     */
    public function handle(TradeRefundEvent $event)
    {
        //清空缓存，防止数据不一致
        $em = app('registry')->getManager('default');
        $em->clear();

        app('log')->debug('trade_after_refund_event=>:' . var_export($event->entities, 1));

        //$aftersalesBn = $event->entities['aftersales_bn'] ?? '';
        $companyId = $event->entities['company_id'];
        $orderId = $event->entities['order_id'];
        $refundBn = $event->entities['refund_bn'] ?? '';
        $userId = $event->entities['user_id'] ?? '';

        // 判断是否开启OME
        $service = new ThirdSettingService();
        $data = $service->getShopexErpSetting($companyId);
        if (!isset($data) || $data['is_open'] == false) {
            app('log')->debug('companyId:' . $companyId . ":未开启OME");
            return true;
        }

        $orderRefundService = new OrderRefundService();

        try {
            $orderStruct = $orderRefundService->getOrderRefundInfo($companyId, $orderId, $refundBn, $userId);
            if (!$orderStruct) {
                app('log')->debug('获取订单退款信息失败:refundBn:' . $refundBn);
                return true;
            }

            $omeRequest = new Request($companyId);

            $method = 'ome.refund.add';

            $result = $omeRequest->call($method, $orderStruct);
            // dd($method,$orderStruct,$result);exit;
            app('log')->debug($method . '订单号:' . $orderId . "=>result:\r\n" . var_export($result, 1));
        } catch (\Exception $e) {
            app('log')->debug('OME请求失败:' . $e->getMessage());
        }

        return true;
    }
}
