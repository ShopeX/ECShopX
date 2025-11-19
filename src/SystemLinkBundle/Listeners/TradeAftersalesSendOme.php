<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemLinkBundle\Listeners;

// use OrdersBundle\Events\TradeFinishEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use EspierBundle\Listeners\BaseListeners;

use SystemLinkBundle\Events\TradeAftersalesEvent;

use OrdersBundle\Traits\GetOrderServiceTrait;

use SystemLinkBundle\Services\ShopexErp\OrderAftersalesService;

use SystemLinkBundle\Services\ShopexErp\Request;

use SystemLinkBundle\Services\ThirdSettingService;

class TradeAftersalesSendOme extends BaseListeners implements ShouldQueue
{
    // class TradeAftersalesSendOme extends BaseListeners {

    use GetOrderServiceTrait;

    protected $queue = 'default';

    /**
     * Handle the event.
     *
     * @param  TradeAftersalesEvent  $event
     * @return bool
     */
    public function handle(TradeAftersalesEvent $event)
    {
        //清空缓存，防止数据不一致
        $em = app('registry')->getManager('default');
        $em->clear();

        $orderAftersalesService = new OrderAftersalesService();

        $companyId = $event->entities['company_id'];
        $orderId = $event->entities['order_id'];
        $aftersalesBn = $event->entities['aftersales_bn'];

        app('log')->debug('trade_after_event=>:'.var_export($event->entities, 1));

        // 判断是否开启OME
        $service = new ThirdSettingService();
        $data = $service->getShopexErpSetting($companyId);
        app('log')->debug('trade_after_data=>:'.var_export($data, 1));
        if (!isset($data) || $data['is_open'] == false) {
            app('log')->debug('companyId:'.$companyId.",orderId:".$orderId.",msg:未开启OME");
            return true;
        }

        try {
            $orderStruct = $orderAftersalesService->getOrderAfterInfo($companyId, $orderId, $aftersalesBn);
            app('log')->debug('trade_after_orderStruct=>:'.var_export($orderStruct, 1));
            if (!$orderStruct) {
                app('log')->debug('获取订单售后信息失败:companyId:'.$companyId.",orderId:".$orderId);
                return true;
            }

            $omeRequest = new Request($companyId);
            $method = 'ome.aftersale.add';
            $result = $omeRequest->call($method, $orderStruct);

            app('log')->debug($method.'订单号:'.$orderId."=>". json_encode($result));
        } catch (\Exception $e) {
            app('log')->debug('OME请求失败:'. $e->getMessage());
        }

        return true;
    }
}
