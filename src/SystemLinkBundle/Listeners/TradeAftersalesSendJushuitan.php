<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemLinkBundle\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use EspierBundle\Listeners\BaseListeners;

use SystemLinkBundle\Events\Jushuitan\TradeAftersalesEvent;
use SystemLinkBundle\Services\Jushuitan\OrderAftersalesService;
use SystemLinkBundle\Services\Jushuitan\Request;
use SystemLinkBundle\Services\JushuitanSettingService;

use OrdersBundle\Traits\GetOrderServiceTrait;
use AftersalesBundle\Services\AftersalesService;
use DistributionBundle\Services\DistributorService;

class TradeAftersalesSendJushuitan extends BaseListeners implements ShouldQueue {

    use GetOrderServiceTrait;

    protected $queue = 'default';

    /**
     * Handle the event.
     *
     * @param  TradeFinishEvent  $event
     * @return void
     */
    public function handle(TradeAftersalesEvent $event)
    {
        //清空缓存，防止数据不一致
        $em = app('registry')->getManager('default');
        $em->clear();
        
        app('log')->debug('TradeAftersalesSendJushuitan_event=>:'.var_export($event->entities,1));

        $aftersalesBn = $event->entities['aftersales_bn'];
        $companyId = $event->entities['company_id'];
        $orderId = $event->entities['order_id'];
        $distributorId = $event->entities['distributor_id'];
        // $itemId = $event->entities['item_id'];

        // 判断是否开启聚水潭ERP
        $service = new JushuitanSettingService();
        $setting = $service->getJushuitanSetting($companyId);
        if (!isset($setting) || $setting['is_open']==false)
        {
            app('log')->debug('companyId:'.$companyId.",msg:未开启聚水潭ERP");
            return true;
        }
        $shopId = $setting['shop_id'];
        if ($distributorId > 0) {
            $distributorService = new DistributorService();
            $distributorInfo = $distributorService->getInfoSimple(['company_id' => $companyId, 'distributor_id' => $distributorId]);
            if (!$distributorInfo || !$distributorInfo['jst_shop_id']) {
                app('log')->debug('companyId:'.$companyId.",msg:店铺没有绑定聚水潭ERP门店");
                return true;
            }

            $shopId = $distributorInfo['jst_shop_id'];
        }

        try {

            $orderAftersalesService = new OrderAftersalesService();
            $afterStruct = $orderAftersalesService->getOrderAfterInfo($companyId, $orderId, $aftersalesBn, $shopId);
            app('log')->debug('TradeAftersalesSendJushuitan_afterStruct=>:'.var_export($afterStruct,1));
            if (!$afterStruct )
            {
                app('log')->debug('获取订单售后信息失败:companyId:'.$companyId.",orderId:".$orderId.",sourceType:".$sourceType);
                return true;
            }

            $jushuitanRequest = new Request($companyId);
            $method = 'aftersale_add';
            $result = $jushuitanRequest->call($method, [$afterStruct]);

            app('log')->debug($method.'订单号:'.$orderId."=>". json_encode($result));
        } catch ( \Exception $e){
            app('log')->debug('聚水潭请求失败:'. $e->getMessage());
        }

        return true;

    }
}
