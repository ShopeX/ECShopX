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

use EspierBundle\Listeners\BaseListeners;
use Illuminate\Contracts\Queue\ShouldQueue;
use OrdersBundle\Traits\GetOrderServiceTrait;
use SystemLinkBundle\Events\WdtErp\TradeFinishEvent;
use SystemLinkBundle\Services\WdtErp\Client\WdtErpClient;
use SystemLinkBundle\Services\WdtErp\OrderService;
use PromotionsBundle\Services\PromotionGroupsTeamMemberService;
use SystemLinkBundle\Services\WdtErpSettingService;
use DistributionBundle\Services\DistributorService;

class TradeFinishSendWdtErp extends BaseListeners implements ShouldQueue
{
    use GetOrderServiceTrait;

    protected $queue = 'default';

    public function handle(TradeFinishEvent $event)
    {
        //清空缓存，防止数据不一致
        $em = app('registry')->getManager('default');
        $em->clear();

        app('log')->debug('TradeFinishSendWdtErp_event:'.var_export($event,1));
        $companyId = $event->entities->getCompanyId();
        $orderId = $event->entities->getOrderId();
        $distributorId = $event->entities->getDistributorId();

        // 判断是否开启旺店通ERP
        $wdtErpSettingService = new WdtErpSettingService();
        $setting = $wdtErpSettingService->getWdtErpSetting($companyId);
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

        $orderService = new OrderService();
        $sourceType = $event->entities->getTradeSourceType();

        switch ($sourceType) {
            case 'normal_seckill':
            case 'normal_normal':
            case 'normal':
                $orderStruct = $orderService->getOrderStruct($companyId, $orderId, $sourceType);
                if (!$orderStruct) {
                    app('log')->debug('获取订单信息失败:companyId:'.$companyId.",orderId:".$orderId.",sourceType:".$sourceType);
                    return true;
                }

                self::wdtRequest($setting, $orderStruct, $shopNo);
                break;
            case 'normal_groups':
            case 'groups':
                $promotionGroupsTeamMemberService = new PromotionGroupsTeamMemberService();
                $filter = [
                    'order_id' => $orderId,
                    'company_id' => $companyId,
                    'member_id' => $event->entities->getUserId()
                ];
                $teamInfo = $promotionGroupsTeamMemberService->getInfo($filter);
                app('log')->debug('TradeFinishSendWdtErp_event:'.var_export($teamInfo,1));

                //获取成团的已支付的订单列表
                $filter = ['m.team_id' => $teamInfo['team_id'], 'o.order_status' => 'PAYED'];
                $orderData = $promotionGroupsTeamMemberService->getList($companyId, $filter, 1, 10000);
                app('log')->debug('TradeFinishSendWdtErp_event:'.var_export($orderData,1));
                if (empty($orderData)) {
                    return true;
                }

                foreach ((array)$orderData['list'] as $value) {
                    if (!$value) {
                        continue;
                    }
                    $orderStruct = $orderService->getOrderStruct($value['company_id'], $value['order_id'], $sourceType);
                    if (!$orderStruct) {
                        app('log')->debug('获取团购订单信息失败:companyId:'.$value['company_id'].",orderId:".$value['order_id'].",sourceType:".$value['group_goods_type']);
                        continue;
                    }
                    self::wdtRequest($setting, $orderStruct, $shopNo);
                }
                break;
        }

        return true;
    }

    /**
     * @param $setting
     * @param $orderStruct
     * @return void
     */
    static function wdtRequest($setting, $orderStruct, $shopNo)
    {
        $wdtErpClient = new WdtErpClient(config('wdterp.api_base_url'), $setting['sid'], $setting['app_key'], $setting['app_secret']);
        $method = config('wdterp.methods.order_add');
        try {
            app('log')->debug('TradeFinishSendWdtErp=>method:'.$method.",request:\r\n". var_export($orderStruct, 1));
            $result = $wdtErpClient->call($method, $shopNo, [$orderStruct['rawTrade']], $orderStruct['tradeOderList']);
            app('log')->debug('TradeFinishSendWdtErp=>method:'.$method.",result:\r\n". var_export($result, 1));
        } catch ( \Exception $e){
            app('log')->debug('旺店通请求失败:'. $e->getMessage());
        }
    }
}

