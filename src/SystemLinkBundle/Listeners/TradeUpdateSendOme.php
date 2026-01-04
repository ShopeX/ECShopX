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

use SystemLinkBundle\Events\TradeUpdateEvent;

// use OrdersBundle\Events\TradeFinishEvent;

use Illuminate\Contracts\Queue\ShouldQueue;

use EspierBundle\Listeners\BaseListeners;

use OrdersBundle\Traits\GetOrderServiceTrait;

use SystemLinkBundle\Services\ShopexErp\OrderService;

use SystemLinkBundle\Services\ShopexErp\Request;

use PromotionsBundle\Services\PromotionGroupsTeamMemberService;


use SystemLinkBundle\Services\ThirdSettingService;

class TradeUpdateSendOme extends BaseListeners implements ShouldQueue
{
    // class TradeUpdateSendOme extends BaseListeners {

    use GetOrderServiceTrait;

    protected $queue = 'default';

    /**
     * Handle the event.
     *
     * @param  TradeUpdateEvent  $event
     * @return void
     */
    public function handle(TradeUpdateEvent $event)
    {
        //清空缓存，防止数据不一致
        $em = app('registry')->getManager('default');
        $em->clear();

        app('log')->debug('TradeUpdateSendOme_event:'.var_export($event, 1));
        // 判断是否开启OME
        $companyId = $event->entities['company_id'];
        $orderId = $event->entities['order_id'];

        // 判断是否开启OME
        $service = new ThirdSettingService();
        $data = $service->getShopexErpSetting($companyId);
        if (!isset($data) || $data['is_open'] == false) {
            app('log')->debug('companyId:'.$companyId.",orderId:".$orderId.",msg:未开启OME");
            return true;
        }

        $orderService = new OrderService();

        // $sourceType = $event['order_class'];
        $sourceType = $event->entities['order_class'];
        switch ($sourceType) {
            case 'normal_seckill':
            case 'normal_normal':
            case 'normal':

                $orderStruct = $orderService->getOrderStruct($companyId, $orderId, $sourceType);
                if (!$orderStruct) {
                    app('log')->debug('获取订单信息失败:companyId:'.$companyId.",orderId:".$orderId.",sourceType:".$sourceType);
                    return true;
                }

                self::omeRequest($orderStruct, $companyId);
                break;
            case 'normal_groups':
            case 'groups':
                $promotionGroupsTeamMemberService = new PromotionGroupsTeamMemberService();

                //获取当前订单的team_id
                $filter = ['order_id' => $orderId, 'company_id' => $companyId, 'member_id' => $event->entities['user_id']];
                $teamInfo = $promotionGroupsTeamMemberService->getInfo($filter);
                app('log')->debug('TradeUpdateSendOme_event:'.var_export($teamInfo, 1));

                //获取成团的已支付的订单列表
                $filter = ['m.team_id' => $teamInfo['team_id'], 'o.order_status' => 'PAYED'];

                $orderData = $promotionGroupsTeamMemberService->getList($companyId, $filter, 1, 10000);
                app('log')->debug('TradeUpdateSendOme_event:'.var_export($orderData, 1));

                if (!$orderData) {
                    return true;
                }

                foreach ((array)$orderData['list'] as $value) {
                    if (!$value) {
                        continue;
                    }
                    $orderStruct = $orderService->getOrderStruct($value['company_id'], $value['order_id'], $value['group_goods_type']);

                    if (!$orderStruct) {
                        app('log')->debug('获取团购订单信息失败:companyId:'.$value['company_id'].",orderId:".$value['order_id'].",sourceType:".$value['group_goods_type']);
                        continue;
                    }
                    self::omeRequest($orderStruct, $companyId);
                }
                break;
        }

        return true;
    }

    public static function omeRequest($orderStruct = [], $companyId = null)
    {
        try {
            $omeRequest = new Request($companyId);

            $method = 'ome.order.add';

            $result = $omeRequest->call($method, $orderStruct);

            app('log')->debug($method.'=>orderStruct:'.json_encode($orderStruct, 256)."=>result:". json_encode($result, 256));
        } catch (\Exception $e) {
            app('log')->debug('OME请求失败:'. $e->getMessage().'=>method:'.$method.'=>orderStruct:'.json_encode($orderStruct, 256));
        }

        return $result;
    }
}
