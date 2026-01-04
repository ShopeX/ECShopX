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

use SystemLinkBundle\Events\Jushuitan\TradeCancelEvent;
use SystemLinkBundle\Services\JushuitanSettingService;
use OrdersBundle\Entities\OrdersRelJushuitan;

use SystemLinkBundle\Services\Jushuitan\OrderCancelService;
use SystemLinkBundle\Services\Jushuitan\OrderService;
use SystemLinkBundle\Services\Jushuitan\Request;
use OrdersBundle\Traits\OrderSettingTrait;


class TradeCancelSendJushuitan extends BaseListeners {

    use OrderSettingTrait;

    protected $queue = 'default';

    /**
     * Handle the event.
     *
     * @param  TradeCancelEvent  $event
     * @return void
     */
    public function handle(TradeCancelEvent $event)
    {
        //清空缓存，防止数据不一致
        $em = app('registry')->getManager('default');
        $em->clear();

        app('log')->debug('TradeCancelSendJushuitan_event=>:'.json_encode($event->entities));

        $companyId = $event->entities['company_id'];
        $orderId = $event->entities['order_id'];
        $cancelReason = $event->entities['cancel_reason'];
        $action = $event->entities['action'] ?? '';

        // 判断是否开启聚水潭ERP
        $service = new JushuitanSettingService();
        $setting = $service->getJushuitanSetting($companyId);
        if (!isset($setting) || $setting['is_open']==false)
        {
            app('log')->debug('companyId:'.$companyId.",msg:未开启聚水潭ERP");
            return true;
        }
        $ordersRelJushuitanRepository = app('registry')->getManager('default')->getRepository(OrdersRelJushuitan::class);
        $relData = $ordersRelJushuitanRepository->getInfo(['company_id' => $companyId, 'order_id' => $orderId]);
        if (!$relData) {
            app('log')->debug('companyId:'.$companyId.",msg:orderId:".$orderId.",订单未关联聚水潭ERP");
            return true;
        }

        $orderSetting = $this->getOrdersSetting($companyId);
        $orderSetting['auto_aftersales'] = $orderSetting['auto_aftersales'] ?? false;
        app('log')->info('orderSetting===>'.json_encode($orderSetting));
        if ($orderSetting['auto_aftersales'] == false && $action != 'pass_refund') {
            app('log')->debug('companyId:'.$companyId.",msg:交易设置未开启自动审批同意");
            return true;
        }
        if ($orderSetting['auto_aftersales'] == true && $action != 'cancel_order') {
            app('log')->debug('companyId:'.$companyId.",msg:交易设置开启自动审批同意,不是订单取消操作");
            return true;
        }
        try {
            $orderCancelService = new OrderCancelService();
            $orderStruct = $orderCancelService->getOrderInfo($companyId, $orderId, $cancelReason);
            if (!$orderStruct )
            {
                app('log')->debug('获取订单信息失败:companyId:'.$companyId.",orderId:".$orderId);
                return true;
            }

            $jushutanRequest = new Request($companyId);
            $method = 'order_cancel';
            $result = $jushutanRequest->call($method, $orderStruct);
            app('log')->debug($method.':订单号:'.$orderId."=>result:". json_encode($result));

            if (isset($result['code']) && strval($result['code']) === '0') {
                if ($action == 'cancel_order') {
                    $orderCancelService->confirmCancelOrder($companyId, $orderId);
                }
            } else {
                throw new \Exception('订单取消失败');
            }

        } catch ( \Exception $e){
            $error = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'msg' => $e->getMessage(),
            ];
            app('log')->debug('聚水潭请求失败:'. json_encode($error));
            throw $e;
        }

        return true;
    }
}
