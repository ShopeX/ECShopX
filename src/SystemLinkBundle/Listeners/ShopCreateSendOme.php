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

use DistributionBundle\Events\DistributorCreateEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use EspierBundle\Listeners\BaseListeners;
use SystemLinkBundle\Services\ShopexErp\OpenApi\Request;
use SystemLinkBundle\Services\ThirdSettingService;
use SystemLinkBundle\Services\ShopexErp\ShopService;

// class ShopCreateSendOme extends BaseListeners implements ShouldQueue {
class ShopCreateSendOme extends BaseListeners
{
    protected $queue = 'default';

    /**
     * Handle the event.
     *
     * @param  DistributorCreateEvent  $event
     * @return void
     */
    public function handle(DistributorCreateEvent $event)
    {
        app('log')->debug('DistributorCreateEvent_event:'.var_export($event, 1));
        // 判断是否开启OME
        $companyId = $event->entities['company_id'];
        $distributorId = $event->entities['distributor_id'];

        // 判断是否开启OME
        $service = new ThirdSettingService();
        $data = $service->getShopexErpSetting($companyId);
        if (!isset($data) || ($data['is_openapi_open'] ?? false) == false) {
            app('log')->debug('companyId:'.$companyId.",distributorId:".$distributorId.",msg:未开启OME开放数据接口");
            return true;
        }

        $shopService = new ShopService();

        try {
            $shopStruct = $shopService->getShopStruct($companyId, $distributorId);

            app('log')->debug('ShopCreateSendOme_shopStruct=>:'.var_export($shopStruct, 1));

            if (!$shopStruct) {
                app('log')->debug('获取店铺流信息失败');
                return true;
            }

            $omeRequest = new Request($companyId);
            $method = 'shop.add';
            $result = $omeRequest->call($method, $shopStruct);

            app('log')->debug($method."=>". var_export($result, 1));
        } catch (\Exception $e) {
            app('log')->debug('OME门店同步请求失败:'. $e->getMessage());
        }

        return true;
    }
}
