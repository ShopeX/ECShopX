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

namespace SystemLinkBundle\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use EspierBundle\Listeners\BaseListeners;

use OrdersBundle\Traits\GetOrderServiceTrait;
use SystemLinkBundle\Events\WdtErp\TradeAfterSaleEvent;
use SystemLinkBundle\Services\WdtErp\Client\WdtErpClient;
use SystemLinkBundle\Services\WdtErp\OrderAfterSaleService;
use SystemLinkBundle\Services\WdtErpSettingService;
use DistributionBundle\Services\DistributorService;

class TradeAfterSaleSendWdtErp extends BaseListeners implements ShouldQueue {

    use GetOrderServiceTrait;

    protected $queue = 'default';

    /**
     * Handle the event.
     *
     * @param  TradeAfterSaleEvent  $event
     * @return bool
     */
    public function handle(TradeAfterSaleEvent $event)
    {
        //清空缓存，防止数据不一致
        $em = app('registry')->getManager('default');
        $em->clear();

        app('log')->debug('TradeAfterSaleSendWdtErp_event=>:'.var_export($event->entities,1));

        $companyId = $event->entities['company_id'];
        $distributorId = $event->entities['distributor_id'];

        // 判断是否开启旺店通ERP
        $service = new WdtErpSettingService();
        $setting = $service->getWdtErpSetting($companyId);
        if (!isset($setting) || !$setting['is_open']) {
            app('log')->debug('companyId:'.$companyId.",msg:未开启旺店通ERP");
            return true;
        }

        $shopNo = $setting['shop_no'];
        if ($distributorId > 0) {
            $distributorService = new DistributorService();
            $distributorInfo = $distributorService->getInfoSimple(['company_id' => $companyId, 'distributor_id' => $distributorId]);
            if (!$distributorInfo || !$distributorInfo['wdt_shop_no']) {
                app('log')->debug('companyId:'.$companyId.",msg:店铺没有绑定旺店通ERP门店");
                return true;
            }

            $shopNo = $distributorInfo['wdt_shop_no'];
        }

        $aftersalesBn = $event->entities['aftersales_bn'];
        $companyId = $event->entities['company_id'];
        $orderId = $event->entities['order_id'];

        $wdtErpClient = new WdtErpClient(config('wdterp.api_base_url'), $setting['sid'], $setting['app_key'], $setting['app_secret']);
        $orderAfterSaleService = new OrderAfterSaleService();
        $afterStruct = $orderAfterSaleService->getAfterSaleStruct($companyId, $aftersalesBn);

        app('log')->debug('TradeAfterSaleSendWdtErp_afterStruct=>:'.var_export($afterStruct,1));
        if (!$afterStruct) {
            app('log')->debug('获取订单售后信息失败:companyId:'.$companyId.",orderId:".$orderId);
            return true;
        }

        try {
            $method = config('wdterp.methods.after_sale_add');
            app('log')->debug('TradeAfterSaleSendWdtErp=>method:'.$method.",request:\r\n". var_export(['shop_no' => $shopNo, 'order_list' => [$afterStruct]], 1));
            $result = $wdtErpClient->call($method, $shopNo, [$afterStruct]);
            app('log')->debug('TradeAfterSaleSendWdtErp=>method:'.$method.",result:\r\n". var_export($result, 1));
        } catch ( \Exception $e){
            app('log')->debug('旺店通请求失败:'. $e->getMessage());
        }

        return true;
    }
}
