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

namespace OrdersBundle\Listeners;

use OrdersBundle\Entities\NormalOrdersItems;
use OrdersBundle\Events\TradeFinishEvent;
use OrdersBundle\Services\Orders\GroupsNormalOrderService;
use OrdersBundle\Services\Orders\GroupsServiceOrderService;
use PromotionsBundle\Services\GroupItemStoreService;
use PromotionsBundle\Services\PromotionGroupsActivityService;
use PromotionsBundle\Services\PromotionGroupsTeamMemberService;
use PromotionsBundle\Services\PromotionGroupsTeamService;
use OrdersBundle\Services\TradeService;
use OrdersBundle\Services\OrderAssociationService;
use OrdersBundle\Traits\GetOrderServiceTrait;
use ThirdPartyBundle\Events\TradeUpdateEvent as SaasErpUpdateEvent;

class UpdateGroupsActivityOrder
{
    use GetOrderServiceTrait;

    /**
     * Handle the event.
     *
     * @param TradeFinishEvent $event
     * @return void
     */
    public function handle(TradeFinishEvent $event)
    {
        ##拼团成功处理
        // 积分支付订单不需要
//        if (in_array($event->entities->getPayType(), ['point', 'deposit'])) {
//            return true;
//        }
        $userId = $event->entities->getUserId();
        $companyId = $event->entities->getCompanyId();
        $orderId = $event->entities->getOrderId();
        // 会员小程序直接买单 只有支付单 没有订单
        if (!$orderId) {
            return true;
        }

        if ($event->entities->getTradeSourceType() != 'normal_groups') {
            return true;
        }

        $paymentstate = $event->entities->getTradeState();

        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $orderId);
        $orderService = $this->getOrderServiceByOrderInfo($order);
        if ($order['order_class'] != 'groups') {
            return true;
        }
        $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        $orderItems = $normalOrdersItemsRepository->getRow(['company_id' => $companyId, 'order_id' => $orderId]);
        $groupsNormalOrderService = new GroupsNormalOrderService();
        $orderType = $order['order_type'] . '_' . $order['order_class'];
        $promotionGroupsTeamMemberService = new PromotionGroupsTeamMemberService();
        $promotionGroupsTeamMember = $promotionGroupsTeamMemberService->promotionGroupsTeamMemberRepository->getInfo(['company_id' => $companyId, 'order_id' => $orderId, 'member_id' => $userId]);
        $filter = ['company_id' => $companyId, 'order_id' => $orderId];
        if ($paymentstate == 'SUCCESS' && isset($promotionGroupsTeamMember['disabled']) && $promotionGroupsTeamMember['disabled'] == true) {
            $conn = app('registry')->getConnection('default');
            $conn->beginTransaction();
            try {
                // 拼团活动信息
                $promotionGroupsTeamService = new PromotionGroupsTeamService();
                $promotionGroupsTeamOldInfo = $promotionGroupsTeamService->promotionGroupsTeamRepository->getInfo(['team_id' => $promotionGroupsTeamMember['team_id']]);
                if (2 == $promotionGroupsTeamOldInfo['team_status']) {
                    ##恢复商品库存
                    $tradeService = new TradeService();
                    $tradeService->refundStatusRightNow($orderId, $companyId, $orderType);
                    $groupsNormalOrderService->addItemStore($filter);
                    ## 恢复活动库存
                    $groupItemStoreService = new GroupItemStoreService();
                    $groupItemStoreService->minusGroupItemStore($promotionGroupsTeamMember['act_id'], -$orderItems['num']);
                    $conn->commit();
                    return true;
                }
                $promotionGroupsTeamMemberService->promotionGroupsTeamMemberRepository->updateOneBy(['company_id' => $companyId, 'order_id' => $orderId, 'member_id' => $userId], ['disabled' => false]);
                $promotionGroupsActivityService = new PromotionGroupsActivityService();
                $promotionGroupsActivityInfo = $promotionGroupsActivityService->getInfo(['groups_activity_id' => $promotionGroupsTeamMember['act_id']]);

                $promotionGroupsTeamInfo = $promotionGroupsTeamService->promotionGroupsTeamRepository->updateNum(['team_id' => $promotionGroupsTeamMember['team_id']], $promotionGroupsActivityInfo['person_num']);
//                if (2 == $promotionGroupsTeamInfo['team_status'] || 1 == $promotionGroupsTeamInfo['team_status']) {
//                    $groupItemStoreService = new GroupItemStoreService();
//                    $flag = $groupItemStoreService->minusGroupItemStore($promotionGroupsTeamMember['act_id'], $orderItems['num']);
//                    if (!$flag) {
//                        $tradeService = new TradeService();
//                        $tradeService->refundStatus($orderId, $companyId, $orderType);
//                        $groupsNormalOrderService->addItemStore($filter);
//                        $conn->commit();
//                        return true;
//
//                    }
//                }
                if (2 == $promotionGroupsTeamInfo['team_status']) {
                    $promotionGroupsTeamMemberList = $promotionGroupsTeamMemberService->getGroupTeamSuccess($promotionGroupsTeamInfo['team_id']);
                    $groupsServiceOrderService = new GroupsServiceOrderService();
                    $groupsNormalOrderService = new GroupsNormalOrderService();
                    app('log')->debug('拼团订单赠送权益');
                    foreach ($promotionGroupsTeamMemberList['list'] as $v) {
                        $filterList = ['order_id' => $v['order_id']];
                        $updateStatus['order_status'] = 'PAYED';
                        if ($v['member_id'] > 0) {
                            if ($order['order_type'] == 'service') {
                                $groupsServiceOrderService->serviceOrderRepository->update($filterList, $updateStatus);
                                $groupsServiceOrderService->orderAssociationsRepository->update($filterList, $updateStatus);
                                $groupsServiceOrderService->addNewRights($v['company_id'], $v['member_id'], $v['order_id']);
                            } else {
                                $groupsNormalOrderService->normalOrdersRepository->update($filterList, $updateStatus);
                                $groupsNormalOrderService->orderAssociationsRepository->update($filterList, $updateStatus);
                            }
                        }
                    }

                    if ($order['order_type'] != 'service') {
                        event(new SaasErpUpdateEvent($order));
                    }
                }
                $conn->commit();
                return true;
            } catch (\Exception $e) {
                $conn->rollback();
                app('log')->debug($e->getMessage());
            }
        } else {
            app('log')->debug('拼团订单更新失败');
        }
    }
}
