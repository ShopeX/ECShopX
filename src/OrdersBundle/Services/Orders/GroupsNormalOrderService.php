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

use GoodsBundle\Entities\Items;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemStoreService;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\NormalOrdersItems;
use Dingo\Api\Exception\ResourceException;

use PromotionsBundle\Services\PromotionGroupsTeamMemberService;
use PromotionsBundle\Services\GroupItemStoreService;
use PromotionsBundle\Services\PromotionGroupsActivityService;
use PromotionsBundle\Services\PromotionGroupsTeamService;
use OrdersBundle\Services\CartService;
use PointBundle\Services\PointMemberService;
use OrdersBundle\Events\OrderProcessLogEvent;

// 实体类商品拼团
class GroupsNormalOrderService extends AbstractNormalOrder
{
    public $orderClass = 'groups';

    public $orderType = 'normal';

    // 订单是否支持优惠券优惠
    public $isSupportCouponDiscount = false;

    // 订单是否需要进行门店验证
    public $isCheckShopValid = false;

    // 订单是否需要进行店铺验证
    public $isCheckDistributorValid = true;

    // 是否免邮
    public $isNotHaveFreight = false;

    public $isSupportCart = true;

    //订单是否支持积分抵扣
    public $isSupportPointDiscount = true;
    // 订单是否需要验证白名单
    public $isCheckWhitelistValid = true;

    // 订单是否支持获取积分
    public $isSupportGetPoint = true;

    public $orderItemIds;
    public $groupInfo;

    /**
     * 创建订单类型自身服务是否检查必填参数
     */
    public function checkCreateOrderNeedParams($params)
    {
        $rules = [
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

    public function checkoutCartItems($params)
    {
        $cartService = new CartService();
        $items = $cartService->getFastBuyCart($params['company_id'], $params['user_id']);
        if ($items && (!isset($params['items']) || !$params['items'])) {
            $temp = [
                [
                    'item_id' => $items['item_id'],
                    'num' => $items['num'],
                ]
            ];
            $params['items'] = $temp;
        }
        return $params;
    }

    public function check($params)
    {
        $promotionGroupsActivityService = new PromotionGroupsActivityService();
        $groupInfo = $promotionGroupsActivityService->checkCreateGroupOrder($params);
        $this->orderItemIds[] = $groupInfo['goods_id'];
        if ($groupInfo['free_post']) {
            $this->isNotHaveFreight = true;
        }
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
        $orderData['act_id'] = $params['bargain_id'];
        $cancelTime = $this->getOrdersSetting($orderData['company_id'], 'order_cancel_time');
        $orderData['auto_cancel_time'] = $this->groupInfo['end_time'] > (time() + $cancelTime * 60) ? time() + $cancelTime * 60 : $this->groupInfo['end_time'];
        return $orderData;
    }

    public function getOrderItemIds($params)
    {
        return $this->orderItemIds;
    }

    public function createExtend($orderData, $params)
    {
        $teamId = isset($params['team_id']) ? $params['team_id'] : '';
        if (empty($params['team_id'])) {
            $teamMemberData = [
                'company_id' => $params['company_id'],
                'act_id' => $params['bargain_id'],
                'head_mid' => $params['user_id'],
                'group_goods_type' => 'normal',
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
            'group_goods_type' => 'normal',
            'order_id' => $orderData['order_id']
        ];
        $promotionGroupsTeamMemberService = new PromotionGroupsTeamMemberService();
        $promotionGroupsTeamMemberService->createGroupsTeamMember($teamMemberData);

        $return['marge'] = true;
        $return['ordersResult']['team_id'] = $teamId;
        return $return;
    }

    /**
     * 拼团订单扣减自定义的库存
     *
     */
    public function minusItemStore($orderData)
    {
        $promotionGroupsActivityService = new PromotionGroupsActivityService();
        $promotionGroupsActivityInfo = $promotionGroupsActivityService->getInfo(['groups_activity_id' => $orderData['act_id']]);
        if (($promotionGroupsActivityInfo['store'] - $orderData['items'][0]['num']) < 0) {
            throw new ResourceException(trans('OrdersBundle/Order.insufficient_stock'));
        }
        ##扣减商品库存
        foreach ($orderData['items'] as $vitem) {
            $minusItemStoreParams[] = [
                'item_id' => $vitem['item_id'],
                'key' => $vitem['is_total_store'] ? $vitem['item_id'] : $vitem['distributor_id'] . '_' . $vitem['item_id'],
                'num' => $vitem['num'],
            ];
        }
        $itemStoreService = new ItemStoreService();
        $itemStoreService->batchMinusItemStore($minusItemStoreParams);
        ##扣减活动库存
        $groupItemStoreService = new GroupItemStoreService();
        $flag = $groupItemStoreService->minusGroupItemStore($orderData['act_id'], $orderData['items'][0]['num']);
        if (!$flag) {
            ##恢复商品库存
            $filter = ['company_id' => $orderData['company_id'], 'order_id' => $orderData['order_id']];
            $this->addItemStore($filter);
            throw new ResourceException(trans('OrdersBundle/Order.insufficient_stock'));
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
        $totalCount = $this->normalOrdersRepository->count($filter);
        $totalPage = ceil($totalCount / $pageSize);
        $itemStoreService = new ItemStoreService();
        for ($i = 1; $i <= $totalPage; $i++) {
            $result = $this->normalOrdersRepository->getList($filter, 0, $pageSize);
            $orderIds = array_column($result, 'order_id');
            $orderItems = $this->normalOrdersItemsRepository->getList(['order_id|in' => $orderIds]);
            if ($orderIds) {
                $this->normalOrdersRepository->batchUpdateBy(['order_id|in' => $orderIds], ['order_status' => 'CANCEL']);
                $this->orderAssociationsRepository->batchUpdateBy(['order_id|in' => $orderIds], ['order_status' => 'CANCEL']);
                ##恢复商品库存，活动库存
                foreach ($orderItems['list'] as $row) {
                    // 总部发货
                    if ($row['is_total_store']) {
                        $itemStoreService->minusItemStore($row['item_id'], -$row['num'], $row['distributor_id'], true);
                    } else {
                        $itemStoreService->minusItemStore($row['item_id'], -$row['num'], $row['distributor_id'], false);
                    }
                    ##拼团订单，恢复库存
                    $groupItemStoreService = new GroupItemStoreService();
                    ##查询活动id
                    $promotionGroupsTeamMemberService = new PromotionGroupsTeamMemberService();
                    $promotionGroupsTeamMember = $promotionGroupsTeamMemberService->promotionGroupsTeamMemberRepository->getInfo(['company_id' => $row['company_id'], 'order_id' => $row['order_id'], 'member_id' => $row['user_id']]);
                    $groupItemStoreService->minusGroupItemStore($promotionGroupsTeamMember['act_id'], -$row['num']);
                }
                foreach ($result as $orderData) {
                    //退还积分
                    (new PointMemberService())->cancelOrderReturnBackPoints($orderData);
                    // 升值积分，额度返回
                    if ($orderData['uppoint_use'] > 0) {
                        parent::minusOrderUppoints($orderData['company_id'], $orderData['user_id'], $orderData['uppoint_use']);
                    }
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

    /**
     * 拼团订单恢复商品库存
     */
    public function addItemStore($filter)
    {
        $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        $orderItems = $normalOrdersItemsRepository->getList(['order_id' => $filter['order_id']]);
        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $order = $normalOrdersRepository->get($filter['company_id'], $filter['order_id']);
        $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        $itemStoreService = new ItemStoreService();
        $order_class = $order->getOrderClass();
        foreach ($orderItems['list'] as $row) {
            if (in_array($order_class, ['groups'])) {
                // 总部发货
                if ($row['is_total_store']) {
                    $itemStoreService->minusItemStore($row['item_id'], -$row['num'], $row['distributor_id'], true);
                } else {
                    $itemStoreService->minusItemStore($row['item_id'], -$row['num'], $row['distributor_id'], false);
                }
            }
        }
    }
}
