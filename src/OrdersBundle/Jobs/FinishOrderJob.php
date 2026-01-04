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

namespace OrdersBundle\Jobs;

// use PaymentBundle\Services\Payments\AdaPaymentService;
use EspierBundle\Jobs\Job;

use OrdersBundle\Services\OrderProfitService;
use OrdersBundle\Services\OrdersRelChinaumspayDivisionService;
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\NormalOrdersItems;
use OrdersBundle\Traits\OrderSettingTrait;
use PromotionsBundle\Services\TurntableService;
use PointBundle\Services\PointMemberService;
use MembersBundle\Services\MemberService;
use OrdersBundle\Entities\Trade;
use OrdersBundle\Events\OrderProcessLogEvent;

class FinishOrderJob extends Job
{
    use OrderSettingTrait;
    use GetOrderServiceTrait;
    public $orderType = 'normal';
    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        $orderService = $this->getOrderService($this->orderType);

        $aftersalesStatus = ['WAIT_SELLER_AGREE','WAIT_BUYER_RETURN_GOODS','WAIT_SELLER_CONFIRM_GOODS'];
        $pageSize = 20;
        $time = time() + 60;
        $filter = [
            'auto_finish_time|lt' => $time,
            'order_status' => 'WAIT_BUYER_CONFIRM',
            'delivery_status' => 'DONE',
            'cancel_status|in' => ['NO_APPLY_CANCEL', 'FAILS'],
            'order_class' => ['normal', 'seckill', 'groups', 'shopguide', 'employee_purchase'],
            'receipt_type|notIn' => ['dada'],
        ];
        $totalCount = $normalOrdersRepository->count($filter);
        $totalPage = ceil($totalCount / $pageSize);
        $success_message = date('Y-m-d').":";
        $fail_message = date('Y-m-d').":";
        for ($i = 0; $i < $totalPage; $i++) {
            $result = $normalOrdersRepository->getList($filter, $i, $pageSize);
            $orderIds = array_column($result, 'order_id');
            $itemFilter = [
                'order_id' => $orderIds,
            ];
            $aftersalesItem = $normalOrdersItemsRepository->getList($itemFilter);
            $orderItemsList = [];
            foreach ($aftersalesItem['list'] as $val) {
                $orderItemsList[$val['order_id']][] = $val;
            }

            foreach ($result as $order) {
                if ($order['order_status'] != 'WAIT_BUYER_CONFIRM') {
                    $fail_message .= $order['order_id']."未发货；";
                    continue;
                }
                if ($order['delivery_status'] != "DONE") {
                    $fail_message .= $order['order_id']."未发货；";
                    continue;
                }
                if (!in_array($order['cancel_status'], ['NO_APPLY_CANCEL', 'FAILS'])) {
                    $fail_message .= $order['order_id']."已申请取消";
                    continue;
                }

                $haveAftersales = false;
                if ($orderItemsList[$order['order_id']] ?? []) {
                    $orderItems = $orderItemsList[$order['order_id']] ;
                    foreach ($orderItems as $orderItem) {
                        if (in_array($orderItem['aftersales_status'], $aftersalesStatus)) {
                            $haveAftersales = true;
                            break;
                        }
                    }
                }

                if ($haveAftersales) {
                    $fail_message .= $order['order_id']."售后未处理";
                    continue;
                }

                //获取售后时效时间
                $aftersalesTime = intval($this->getOrdersSetting($order['company_id'], 'latest_aftersale_time'));
                $auto_close_aftersales_time = strtotime("+$aftersalesTime day", time());

                $finishFilter = [
                    'company_id' => $order['company_id'],
                    'order_id' => $order['order_id'],
                ];
                $updateInfo = [
                    'order_status' => 'DONE',
                    'end_time' => time(),
                    'order_auto_close_aftersales_time' => $auto_close_aftersales_time,
                ];

                $res = $orderService->update($finishFilter, $updateInfo);
                $orderProcessLog = [
                    'order_id' => $order['order_id'],
                    'company_id' => $order['company_id'],
                    'operator_type' => 'system',
                    'operator_id' => 0,
                    'remarks' => '订单完成',
                    'detail' => '订单单号：' . $order['order_id'] . '，订单自动完成',
                ];
                event(new OrderProcessLogEvent($orderProcessLog));

                $orderService->orderFinishBrokerage($order['company_id'], $order['order_id']);

                // 创建银联商务支付，分账订单关联表
                if ($order['pay_type'] == 'chinaums') {
                    if ($order['distributor_id'] > 0) {
                        $relDivisionService = new OrdersRelChinaumspayDivisionService();
                        $relDivisionService->addRelChinaumsPayDivision((int)$order['company_id'], (string)$order['order_id']);
                    }
                }

                //消费满送大转盘抽奖次数
                $turntableService = new TurntableService();
                $turntableService -> payGetTurntableTimes($order['user_id'], $order['company_id'], $order['total_fee']);

                //消费送积分
                if ($order['bonus_points'] > 0) {
                    $pointMemberService = new PointMemberService();
                    $mark = "订单号：".$order['order_id']." 消费送积分";
                    $pointMemberService->addPoint($order['user_id'], $order['company_id'], intval($order['bonus_points']), 7, true, $mark, $order['order_id']);
                }

                $orderProfitService = new OrderProfitService();
                $orderProfitService->orderProfitPlanCloseTime($order['company_id'], $order['order_id']);

                //订单完成推送营销中心事件

                $success_message .= $order['order_id'].', ';

                //更新会员等级- 积分支付订单不需要
                // if (!in_array($order['pay_type'], ['point'])) {
                //     //获取交易单信息
                //     $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
                //     $trade = $tradeRepository->getInfo(['company_id' => $order['company_id'], 'order_id' => $order['order_id']]);
                //     try {
                //         $memberService = new MemberService();
                //         $memberService->updateMemberConsumption($order['user_id'], $order['company_id'], $trade['pay_fee']);
                //     } catch (\Exception $e) {
                //         app('log')->debug('会员等级更新错误,会员id：'.$order['user_id']. '，错误信息: '.$e->getMessage());
                //     }
                // }
            }
        }
        app('log')->debug('成功执行自动确认收货'. $success_message);
        app('log')->debug('未执行自动确认收货'. $fail_message);
    }
}
