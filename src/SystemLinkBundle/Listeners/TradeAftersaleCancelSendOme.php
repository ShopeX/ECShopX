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

use SystemLinkBundle\Events\TradeAftersalesCancelEvent;

use OrdersBundle\Traits\GetOrderServiceTrait;

use SystemLinkBundle\Services\ShopexErp\OrderAftersalesService;

use SystemLinkBundle\Services\ShopexErp\Request;

use SystemLinkBundle\Services\ThirdSettingService;

use AftersalesBundle\Services\AftersalesService;

class TradeAftersaleCancelSendOme extends BaseListeners implements ShouldQueue
{
    // class TradeAftersaleCancelSendOme extends BaseListeners {

    use GetOrderServiceTrait;

    protected $queue = 'default';

    /**
     * Handle the event.
     *
     * @param  TradeAftersalesCancelEvent  $event
     * @return void
     */
    public function handle(TradeAftersalesCancelEvent $event)
    {
        //清空缓存，防止数据不一致
        $em = app('registry')->getManager('default');
        $em->clear();

        app('log')->debug('trade_after_cancel_event=>:'.var_export($event->entities, 1));

        $companyId = $event->entities['company_id'];
        $aftersalesType = $event->entities['aftersales_type'];
        $aftersalesBn = $event->entities['aftersales_bn'];

        // 判断是否开启OME
        $service = new ThirdSettingService();
        $data = $service->getShopexErpSetting($companyId);
        if (!isset($data) || $data['is_open'] == false) {
            return true;
        }

        //编辑售后
        $aftersalesService = new AftersalesService();
        $afterInfo = $aftersalesService->aftersalesRepository->get(['aftersales_bn' => $aftersalesBn, 'company_id' => $companyId]);
        app('log')->debug('trade_after_afterDetail=>:'.var_export($afterInfo, 1));
        if (!$afterInfo) {
            return false;
        }

        $orderId = $afterInfo['order_id'];

        $orderAftersalesService = new OrderAftersalesService();
        try {
            if ($aftersalesType == 'ONLY_REFUND') {
                //仅退款  撤销退款申请单
                $cancelData = $orderAftersalesService->cancelOmeRefund($companyId, $orderId, $aftersalesBn);
                $method = 'ome.refund.add';
            } else {
                //退货退款 撤销售后单
                $cancelData = $orderAftersalesService->getOrderAfterInfo($companyId, $orderId, $aftersalesBn);
                $cancelData['status'] = 5;
                $method = 'ome.aftersale.add';
            }

            app('log')->debug('trade_after_cancel_method=>:'.var_export($method, 1));
            app('log')->debug('trade_after_cancel_cancelData=>:'.var_export($cancelData, 1));

            if (!$cancelData) {
                app('log')->debug('获取售后撤销信息:compayId:'.$companyId.",orderId:".$orderId.",cancelData:".$cancelData);
                return true;
            }

            $omeRequest = new Request($companyId);

            $result = $omeRequest->call($method, $cancelData);

            app('log')->debug($method.'订单号:'.$orderId."=>cancelData:". json_encode($cancelData)."==>result:\r\n".var_export($result, 1));
        } catch (\Exception $e) {
            app('log')->debug('OME请求失败:'. $e->getMessage());
        }

        return true;
    }
}
