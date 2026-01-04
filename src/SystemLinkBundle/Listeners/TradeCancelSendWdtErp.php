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

use PromotionsBundle\Services\PromotionGroupsTeamMemberService;
use SystemLinkBundle\Events\WdtErp\TradeCancelEvent;
use SystemLinkBundle\Services\WdtErp\Client\WdtErpClient;
use SystemLinkBundle\Services\WdtErp\OrderService;
use SystemLinkBundle\Services\WdtErpSettingService;
use OrdersBundle\Entities\Trade;
use DistributionBundle\Services\DistributorService;

class TradeCancelSendWdtErp extends BaseListeners {

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

        app('log')->debug('TradeCancelSendWdtErp_event=>:'.var_export($event->entities,1));

        $companyId = $event->entities['company_id'];
        $orderId = $event->entities['order_id'];
        $distributorId = $event->entities['distributor_id'];

        // 判断是否开启聚水潭ERP
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

        $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
        $tradeInfo = $tradeRepository->getInfo(['company_id' => $companyId, 'order_id' => $orderId]);
        if (empty($tradeInfo)) {
            app('log')->debug("TradeCancelSendWdtErp_event=>:获取交易单失败");
            return true;
        }

        $orderService = new OrderService();
        $sourceType = $tradeInfo['trade_source_type'];

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
                app('log')->debug('TradeCancelSendWdtErp_event=>:'.var_export($teamInfo,1));

                //获取成团的已支付的订单列表
                $filter = ['m.team_id' => $teamInfo['team_id'], 'o.order_status' => 'PAYED'];
                $orderData = $promotionGroupsTeamMemberService->getList($companyId, $filter, 1, 10000);
                app('log')->debug('TradeCancelSendWdtErp_event:'.var_export($orderData,1));
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
            app('log')->debug('TradeCancelSendWdtErp=>method:'.$method.",request:\r\n". var_export($orderStruct, 1));
            $result = $wdtErpClient->call($method, $shopNo, [$orderStruct['rawTrade']], $orderStruct['tradeOderList']);
            app('log')->debug('TradeCancelSendWdtErp=>method:'.$method.",result:\r\n". var_export($result, 1));
        } catch ( \Exception $e){
            app('log')->debug('旺店通请求失败:'. $e->getMessage());
        }
    }
}
