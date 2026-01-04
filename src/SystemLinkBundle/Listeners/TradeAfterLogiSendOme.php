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

// use OrdersBundle\Events\TradeFinishEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use EspierBundle\Listeners\BaseListeners;

use SystemLinkBundle\Events\TradeAftersalesLogiEvent;

use OrdersBundle\Traits\GetOrderServiceTrait;

use SystemLinkBundle\Services\ShopexErp\OrderAftersalesService;

use SystemLinkBundle\Services\ShopexErp\Request;

use SystemLinkBundle\Services\ThirdSettingService;

class TradeAfterLogiSendOme extends BaseListeners implements ShouldQueue
{
    // class TradeAfterLogiSendOme extends BaseListeners {

    use GetOrderServiceTrait;

    protected $queue = 'default';

    /**
     * Handle the event.
     *
     * @param  TradeFinishEvent  $event
     * @return void
     */
    public function handle(TradeAftersalesLogiEvent $event)
    {
        //清空缓存，防止数据不一致
        $em = app('registry')->getManager('default');
        $em->clear();

        app('log')->debug('TradeAfterLogiSendOme_event=>:'.var_export($event, 1));

        $companyId = $event->entities['company_id'];

        // 判断是否开启OME
        $service = new ThirdSettingService();
        $data = $service->getShopexErpSetting($companyId);
        if (!isset($data) || $data['is_open'] == false) {
            app('log')->debug('companyId:'.$companyId.' msg:未开启OME');
            return true;
        }

        $orderAftersalesService = new OrderAftersalesService();

        try {
            $afterLogistics = $orderAftersalesService->getAfterLogistics($event->entities);

            app('log')->debug('TradeAfterLogiSendOme_afterLogistics=>:'.var_export($afterLogistics, 1));

            if (!$afterLogistics) {
                app('log')->debug('获取售后物流信息失败');
                return true;
            }

            $omeRequest = new Request($companyId);

            $method = 'ome.aftersale.logistics_update';

            $result = $omeRequest->call($method, $afterLogistics);

            app('log')->debug($method."=>". var_export($result, 1));
        } catch (\Exception $e) {
            app('log')->debug('OME物流请求失败:'. $e->getMessage());
        }

        return true;
    }
}
