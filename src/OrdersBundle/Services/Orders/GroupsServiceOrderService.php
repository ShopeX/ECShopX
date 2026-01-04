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

namespace OrdersBundle\Services\Orders;

use Dingo\Api\Exception\ResourceException;

use PromotionsBundle\Services\PromotionGroupsTeamMemberService;
use PromotionsBundle\Services\PromotionGroupsActivityService;
use PromotionsBundle\Services\PromotionGroupsTeamService;
use PointBundle\Services\PointMemberService;
use OrdersBundle\Events\OrderProcessLogEvent;

// 服务类商品拼团
// 生成服务类拼团订单
class GroupsServiceOrderService extends AbstractServiceOrder
{
    public $orderClass = 'groups';

    public $orderType = 'service';

    // 订单是否支持优惠券优惠
    public $isSupportCouponDiscount = false;

    // 订单是否需要进行门店验证
    public $isCheckShopValid = true;

    // 订单是否需要进行店铺验证
    public $isCheckDistributorValid = false;

    // 是否免邮
    public $isNotHaveFreight = true;

    // 订单是否需要验证白名单
    public $isCheckWhitelistValid = true;
    public $orderItemIds;
    public $groupInfo;
    /**
     * 创建订单类型自身服务是否检查必填参数
     */
    public function checkCreateOrderNeedParams($params)
    {
        $rules = [
            'item_id' => ['required', '缺少商品参数'],
            'item_num' => ['required|integer|min:1', '商品数量必填,商品数量必须为整数,商品数量最少为1'],
            'company_id' => ['required', '企业id必填'],
            'user_id' => ['required', '用户id必填'],
            'mobile' => ['required', '未授权手机号，请授权'],
            'bargain_id' => ['required', '拼团id必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        return true;
    }

    public function check($params)
    {
        $promotionGroupsActivityService = new PromotionGroupsActivityService();
        $groupInfo = $promotionGroupsActivityService->checkCreateGroupOrder($params);
        $this->orderItemIds[] = $groupInfo['goods_id'];
        $this->groupInfo = $groupInfo;
        return true;
    }

    public function getOrderItemPrice($itemId)
    {
        return $this->groupInfo['act_price'];
    }

    public function getOrderItemDiscountData($itemId, $itemPrice, $buyNum)
    {
        $result = ['discount_fee' => 0, 'discount_info' => []];
        if (isset($this->groupInfo) && $this->groupInfo) {
            $activityInfo = $this->groupInfo;
            $salePrice = $activityInfo['act_price'];
            $itemFee = $itemPrice * $buyNum;  //原价总价
            $totalFee = $salePrice * $buyNum; //销售总价
            $discountFee = $itemFee - $totalFee; //优惠总价

            $discountInfo = [
                'id' => $activityInfo['groups_activity_id'],
                'type' => 'groups',
                'info' => '拼团团购',
                'rule' => $activityInfo['act_name'],
                'discount_fee' => $discountFee,
            ];
            $result['activity_price'] = $salePrice;
            $result['discount_fee'] = $discountFee;
            $result['item_fee'] = $itemFee;
            $result['total_fee'] = $totalFee;
            $result['discount_info'] = $discountInfo;
        }
        return $result;
    }


    public function formatOrderData($orderData, $params, $isCheck)
    {
        $orderData['bargain_id'] = $params['bargain_id'];
        $cancelTime = $this->getOrdersSetting($orderData['company_id'], 'order_cancel_time');
        $orderData['auto_cancel_time'] = $this->groupInfo['end_time'] > (time() + $cancelTime * 60) ? time() + $cancelTime * 60 : $this->groupInfo['end_time'];
        return $orderData;
    }

    public function getOrderItemIds($params)
    {
        return $this->orderItemIds;
    }

    // 订单支付状态修改操作
    public function orderStatusUpdate($filter, $orderStatus, $payType = '')
    {
        $serviceUpdate = ['order_status' => $orderStatus];
        if ($payType) {
            $serviceUpdate['pay_type'] = $payType;
        }
        $this->serviceOrderRepository->update($filter, $serviceUpdate);
        $result = $this->orderAssociationsRepository->update($filter, ['order_status' => $orderStatus]);
        return $result;
    }

    public function createExtend($orderData, $params)
    {
        $teamId = isset($params['team_id']) ? $params['team_id'] : '';
        // 如果是新开团
        if (empty($params['team_id'])) {
            $teamMemberData = [
                'company_id' => $params['company_id'],
                'act_id' => $params['bargain_id'],
                'head_mid' => $params['user_id'],
                'group_goods_type' => 'services',
                'begin_time' => time(),
                'end_time' => time() + $this->groupInfo['limit_time'] * 3600 < $this->groupInfo['end_time'] ? time() + $this->groupInfo['limit_time'] * 3600 : $this->groupInfo['end_time'],
            ];
            $promotionGroupsTeamService = new PromotionGroupsTeamService();
            $groupsTeamInfo = $promotionGroupsTeamService->createGroupsTeam($teamMemberData);
            $teamId = $groupsTeamInfo['team_id'];
        }

        $teamMemberData = [
            'team_id' => $teamId,
            'company_id' => $params['company_id'],
            'act_id' => $params['bargain_id'],
            'member_id' => $params['user_id'],
            'group_goods_type' => 'services',
            'order_id' => $orderData['order_id']
        ];
        $promotionGroupsTeamMemberService = new PromotionGroupsTeamMemberService();
        $promotionGroupsTeamMemberService->createGroupsTeamMember($teamMemberData);

        $return['marge'] = true;
        $return['ordersResult']['team_id'] = $teamId;
        return $return;
    }

    /**
     * 群组添加会员权益
     * @param $teamId
     * @return bool
     */
    public function addGroupsRights($teamId)
    {
        $promotionGroupsTeamMemberService = new PromotionGroupsTeamMemberService();
        $result = $promotionGroupsTeamMemberService->getGroupTeamSuccess($teamId);
        foreach ($result['list'] as $v) {
            if ($v['member_id'] > 0) {
                $this->addNewRights($v['company_id'], $v['member_id'], $v['order_id']);
            }
        }
        return true;
    }

    public function scheduleCancelOrders()
    {
        // 取消订单，每分钟执行一次，当前只处理一分钟内的订单
        $pageSize = 20;
        $time = time() + 60;
        $filter = [
            'auto_cancel_time|lt' => $time,
            'order_status' => 'NOTPAY',
            'order_class' => 'groups'
        ];
        $totalCount = $this->serviceOrderRepository->count($filter);
        $totalPage = ceil($totalCount / $pageSize);

        for ($i = 1; $i <= $totalPage; $i++) {
            $result = $this->serviceOrderRepository->getList($filter, 0, $pageSize);
            $orderIds = array_column($result, 'order_id');
            if ($orderIds) {
                $this->serviceOrderRepository->batchUpdateBy(['order_id|in' => $orderIds], ['order_status' => 'CANCEL']);
                $this->orderAssociationsRepository->batchUpdateBy(['order_id|in' => $orderIds], ['order_status' => 'CANCEL']);
                foreach ($result as $orderData) {
                    //退还积分
                    (new PointMemberService())->cancelOrderReturnBackPoints($orderData);
                    // 升值积分，额度返回
                    /*                    if ($orderData['uppoint_use'] > 0) {
                                            parent::minusOrderUppoints($orderData['company_id'], $orderData['user_id'], $orderData['uppoint_use']);
                                        }*/
                    $orderProcessLog = [
                        'order_id' => $orderData['order_id'],
                        'company_id' => $orderData['company_id'],
                        'operator_type' => 'system',
                        'operator_id' => 0,
                        'remarks' => '订单取消',
                        'detail' => '订单单号：' . $orderData['order_id'] . '，取消订单退款',
                    ];
                    event(new OrderProcessLogEvent($orderProcessLog));
                }
            }
        }
    }
}
