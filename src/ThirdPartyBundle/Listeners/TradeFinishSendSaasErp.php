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

namespace ThirdPartyBundle\Listeners;

use OrdersBundle\Events\TradeFinishEvent;

use Illuminate\Contracts\Queue\ShouldQueue;

use EspierBundle\Listeners\BaseListeners;

use OrdersBundle\Traits\GetOrderServiceTrait;

use ThirdPartyBundle\Services\SaasErpCentre\Request;
use ThirdPartyBundle\Services\SaasCertCentre\CertService;
use ThirdPartyBundle\Services\SaasErpCentre\OrderService;

use PromotionsBundle\Services\PromotionGroupsTeamMemberService;

class TradeFinishSendSaasErp extends BaseListeners implements ShouldQueue
{
    // class TradeFinishSendOme extends BaseListeners {

    use GetOrderServiceTrait;

    protected $queue = 'default';
    public const METHOD = 'store.trade.update';


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

        app('log')->debug('saaserp TradeFinishSendSaasErp event:'.var_export($event, 1));
        // 判断是否开启OME
        $companyId = $event->entities->getCompanyId();
        $orderId = $event->entities->getOrderId();

        // 判断是否绑定了erp
        $certService = new CertService(false, $companyId);
        $erp_node_id = $certService->getErpBindNode();
        if (!$erp_node_id) {
            app('log')->debug('saaserp TradeFinishSendSaasErp companyId:'.$companyId.",orderId:".$orderId.",msg:未开启SaasErp\n");
            return true;
        }

        $orderService = new OrderService();

        $sourceType = $event->entities->getTradeSourceType();
        switch ($sourceType) {
            case 'normal_shopguide':
            case 'normal_shopadmin':
            case 'normal_seckill':
            case 'normal_normal':
            case 'normal':
                $orderStruct = $orderService->getOrderStruct($companyId, $orderId, $sourceType);
                if (!$orderStruct) {
                    app('log')->debug('saaserp TradeFinishSendSaasErp 获取订单信息失败:companyId:'.$companyId.",orderId:".$orderId.",sourceType:".$sourceType);
                    return true;
                }

                self::request($orderStruct, $companyId);
                break;
            case 'normal_groups':
            case 'groups':
                // $teamId = $event->entities->getTeamId();

                $promotionGroupsTeamMemberService = new PromotionGroupsTeamMemberService();

                //获取当前订单的team_id
                $filter = ['order_id' => $orderId, 'company_id' => $companyId, 'member_id' => $event->entities->getUserId()];
                $teamInfo = $promotionGroupsTeamMemberService->getInfo($filter);
                app('log')->debug("\n saaserp TradeFinishSendSaasErp,".__LINE__.", teamInfo:".var_export($teamInfo, 1));

                //获取成团的已支付的订单列表
                $filter = ['m.team_id' => $teamInfo['team_id'], 'o.order_status' => 'PAYED'];

                $orderData = $promotionGroupsTeamMemberService->getList($companyId, $filter, 1, 10000);
                app('log')->debug("\n saaserp TradeFinishSendSaasErp，".__LINE__."，orderData:".var_export($orderData, 1));

                if (!$orderData) {
                    return true;
                }

                foreach ((array)$orderData['list'] as $value) {
                    if (!$value) {
                        continue;
                    }
                    $orderStruct = $orderService->getOrderStruct($value['company_id'], $value['order_id'], $value['group_goods_type']);

                    if (!$orderStruct) {
                        app('log')->debug("\n saaserp TradeFinishSendSaasErp，".__LINE__.",获取团购订单信息失败:companyId:".$value['company_id'].",orderId:".$value['order_id'].",sourceType:".$value['group_goods_type']);
                        continue;
                    }

                    self::request($orderStruct, $companyId);
                }
                break;
        }

        return true;
    }

    public static function request($orderStruct = [], $companyId = null)
    {
        try {
            $request = new Request($companyId);
            $result = $request->call(self::METHOD, $orderStruct);
        } catch (\Exception $e) {
            $errorMsg = "saaserp TradeFinishSendSaasErp method=>".self::METHOD." Error on line ".$e->getLine()." in ".$e->getFile().": <b>".$e->getMessage()."\n";
            app('log')->debug('saaserp 请求失败:'. $errorMsg);
        }
        return $result;
    }
}
