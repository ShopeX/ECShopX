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

namespace EspierBundle\Commands;

use Illuminate\Console\Command;

use PromotionsBundle\Services\PromotionGroupsTeamMemberService;
use SystemLinkBundle\Services\ShopexErp\OrderService;
use SystemLinkBundle\Services\ShopexErp\Request;
use SystemLinkBundle\Services\ThirdSettingService;

class SendOrderToOmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oms:send_order {company_id} {order_id} {order_class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '推送订单到oms，直连oms';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $company_id = $this->argument('company_id');//获取命令参数
        $order_id = $this->argument('order_id');//获取命令参数
        $order_class = $this->argument('order_class');//获取命令参数

        if (!$company_id) {
            exit('company_id required');
        }
        if (!$order_id) {
            exit('order_id required');
        }
        if (!$order_class) {
            $order_class = 'normal';
        }

        $params = [
            'company_id' => $company_id,
            'order_id' => $order_id,
            'order_class' => $order_class,
            'user_id' => 0,
        ];
        $this->send($params);
    }

    public function send($params)
    {
        //清空缓存，防止数据不一致
        $em = app('registry')->getManager('default');
        $em->clear();

        echo('TradeUpdateSendOme_event:'.var_export($params, 1));

        // 判断是否开启OME
        $companyId = $params['company_id'];
        $orderId = $params['order_id'];

        // 判断是否开启OME
        $service = new ThirdSettingService();
        $data = $service->getShopexErpSetting($companyId);
        if (!isset($data) || $data['is_open'] == false) {
            echo('companyId:'.$companyId.",orderId:".$orderId.", msg:未开启OME");
            return true;
        }

        $orderService = new OrderService();

        // $sourceType = $event['order_class'];
        $sourceType = $params['order_class'];
        switch ($sourceType) {
            case 'normal_seckill':
            case 'normal_normal':
            case 'normal':

                $orderStruct = $orderService->getOrderStruct($companyId, $orderId, $sourceType);
                if (!$orderStruct) {
                    echo('获取订单信息失败:companyId:'.$companyId.",orderId:".$orderId.",sourceType:".$sourceType);
                    return true;
                }

                self::omeRequest($orderStruct, $companyId);
                break;
            case 'normal_groups':
            case 'groups':
                $promotionGroupsTeamMemberService = new PromotionGroupsTeamMemberService();

                //获取当前订单的team_id
                $filter = ['order_id' => $orderId, 'company_id' => $companyId, 'member_id' => $params['user_id']];
                $teamInfo = $promotionGroupsTeamMemberService->getInfo($filter);
                echo('TradeUpdateSendOme_event:'.var_export($teamInfo, 1));

                //获取成团的已支付的订单列表
                $filter = ['m.team_id' => $teamInfo['team_id'], 'o.order_status' => 'PAYED'];

                $orderData = $promotionGroupsTeamMemberService->getList($companyId, $filter, 1, 10000);
                echo('TradeUpdateSendOme_event:'.var_export($orderData, 1));

                if (!$orderData) {
                    return true;
                }

                foreach ((array)$orderData['list'] as $value) {
                    if (!$value) {
                        continue;
                    }
                    $orderStruct = $orderService->getOrderStruct($value['company_id'], $value['order_id'], $value['group_goods_type']);

                    if (!$orderStruct) {
                        echo('获取团购订单信息失败:companyId:'.$value['company_id'].",orderId:".$value['order_id'].",sourceType:".$value['group_goods_type']);
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

            echo($method."=>orderStruct:\n".json_encode($orderStruct, 256)."=>result:\n". json_encode($result, 256));
        } catch (\Exception $e) {
            echo('OME请求失败:'. $e->getMessage().'=>method:'.$method."=>orderStruct:\n".json_encode($orderStruct, 256)."=>result:\n". json_encode($result, 256));
        }

        return $result;
    }
}
