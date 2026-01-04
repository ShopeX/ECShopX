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

use SystemLinkBundle\Services\JushuitanSettingService;
use SystemLinkBundle\Services\Jushuitan\OrderService;
use SystemLinkBundle\Services\Jushuitan\Request;
use SystemLinkBundle\Events\Jushuitan\TradeFinishEvent;

use OrdersBundle\Traits\GetOrderServiceTrait;
use PromotionsBundle\Services\PromotionGroupsTeamMemberService;
use OrdersBundle\Entities\OrdersRelJushuitan;
use DistributionBundle\Services\DistributorService;

class TradeFinishSendJushuitan extends BaseListeners implements ShouldQueue {
// class TradeFinishSendJushuitan extends BaseListeners {

    use GetOrderServiceTrait;

    protected $queue = 'default';

    /**
     * Handle the event.
     *
     * @param  TradeFinishEvent  $event
     * @return void
     */
    public function handle(TradeFinishEvent $event)
    {
        //清空缓存，防止数据不一致
        $em = app('registry')->getManager('default');
        $em->clear();
        
        app('log')->debug('TradeFinishSendJushuitan_event:'.var_export($event,1));
        $companyId = $event->entities->getCompanyId();
        $orderId = $event->entities->getOrderId();
        $distributorId = $event->entities->getDistributorId();

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

        $orderService = new OrderService();

        $sourceType = $event->entities->getTradeSourceType();
        switch ($sourceType)
        {
            case 'normal_pointsmall':
            case 'normal_seckill':
            case 'normal_normal':
            case 'normal_shopguide':  // 导购订单               
            case 'normal':

                $orderStruct = $orderService->getOrderStruct($companyId, $orderId, $shopId, $sourceType);
                if (!$orderStruct )
                {
                    app('log')->debug('获取订单信息失败:companyId:'.$companyId.",orderId:".$orderId.",sourceType:".$sourceType);
                    return true;
                }

                self::jushutanRequest($orderStruct, $companyId);
                break;
            case 'normal_groups':
            case 'groups':
                $promotionGroupsTeamMemberService = new PromotionGroupsTeamMemberService();

                //获取当前订单的team_id
                $filter = ['order_id'=>$orderId, 'company_id'=>$companyId, 'member_id'=>$event->entities->getUserId()];
                $teamInfo = $promotionGroupsTeamMemberService->getInfo($filter);
                app('log')->debug('TradeFinishSendJushuitan_event:'.var_export($teamInfo,1));

                //获取成团的已支付的订单列表
                $filter = ['m.team_id' => $teamInfo['team_id'], 'o.order_status' => 'PAYED'];

                $orderData = $promotionGroupsTeamMemberService->getList($filter, 1, 10000);
                app('log')->debug('TradeFinishSendJushuitan_event:'.var_export($orderData,1));

                if (!$orderData)    return true;

                foreach ((array)$orderData['list'] as $value)
                {
                    if (!$value) continue;
                    $orderStruct = $orderService->getOrderStruct($value['company_id'], $value['order_id'], $shopId, $value['group_goods_type']);

                    if (!$orderStruct )
                    {
                        app('log')->debug('获取团购订单信息失败:companyId:'.$value['company_id'].",orderId:".$value['order_id'].",sourceType:".$value['group_goods_type']);
                        continue;
                    }
                    $result = self::jushutanRequest($orderStruct, $companyId);
                    if (!$result) {
                        app('log')->debug('团购订单请求失败:companyId:'.$value['company_id'].",orderId:".$value['order_id'].",sourceType:".$value['group_goods_type']);
                        return false;
                    }
                }
                break;
        }

        return true;
    }

    static function jushutanRequest($orderStruct=[], $companyId=null)
    {
            $jushuitanRequest = new Request($companyId);

            $method = 'order_add';

            $result = $jushuitanRequest->call($method, [$orderStruct]);
            app('log')->debug($method.'=>orderStruct:'.json_encode($orderStruct)."=>result:". var_export($result, true));
            if ($result['code'] == 0) {
                self::saveOrdersRelJushuitan($companyId, $result['data']['datas'][0]);
            }else{
                app('log')->debug('聚水潭请求失败:'. $result['msg'].'=>method:'.$method.'=>orderStruct:'.json_encode($orderStruct)."=>result:". json_encode($result));
                return false;
            }
        // try {
        // } catch ( \Exception $e){
        // app('log')->debug('聚水潭请求失败:'. $e->getMessage().'=>method:'.$method.'=>orderStruct:'.json_encode($orderStruct)."=>result:". json_encode($result));
        // throw $e;
        // }

        return $result;
    }

    static function saveOrdersRelJushuitan($companyId, $data)
    {
        $ordersRelJushuitanRepository = app('registry')->getManager('default')->getRepository(OrdersRelJushuitan::class);
        $insertData = [
            'company_id' => $companyId,
            'order_id' => $data['so_id'],
            'o_id' => $data['o_id'],
        ];
        return $ordersRelJushuitanRepository->create($insertData);
    }
}
