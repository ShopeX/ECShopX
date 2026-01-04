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
use PromotionsBundle\Services\PromotionSeckillActivityService;
use OrdersBundle\Traits\SeckillStoreTicket;
use OrdersBundle\Events\OrderProcessLogEvent;
use PointBundle\Services\PointMemberService;

class SeckillNormalOrderService extends AbstractNormalOrder
{
    use SeckillStoreTicket;

    public $orderClass = 'seckill';

    public $orderType = 'normal';

    // 订单是否支持优惠券优惠
    public $isSupportCouponDiscount = false;

    // 订单是否需要进行门店验证
    public $isCheckShopValid = false;

    // 订单是否需要进行店铺验证
    public $isCheckDistributorValid = true;

    //活动是否包邮
    public $isNotHaveFreight = false;

    //未支付订单保留时长
    public $validityPeriod = 0;

    public $isSupportCart = true;

    //订单是否支持积分抵扣
    public $isSupportPointDiscount = true;
    // 订单是否需要验证白名单
    public $isCheckWhitelistValid = true;

    // 订单是否支持获取积分
    public $isSupportGetPoint = false;

    public $orderItemIds;
    public $seckillInfo;

    /**
     * 创建订单类型自身服务是否检查必填参数
     */
    public function checkCreateOrderNeedParams($params)
    {
        $rules = [
            'company_id' => ['required', '企业id必填'],
            'user_id' => ['required', '用户id必填'],
            'mobile' => ['required', '未授权手机号，请授权'],
            'seckill_id' => ['required', '秒杀活动id必填'],
            'seckill_ticket' => ['required','秒杀ticket必填']
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        return true;
    }

    public function checkoutCartItems($params)
    {
        //验证ticket
        $secKillData = $this->checkTicket($params['seckill_ticket'], $params['user_id'], $params['company_id']);
        if (!isset($params['items']) || !$params['items']) {
            $params['items'][] = [
                'item_id' => $secKillData['data']['item_id'],
                'num' => $secKillData['buy_num'],
            ];
        }
        return $params;
    }

    public function check($params)
    {
        $promotionGroupsActivityService = new PromotionSeckillActivityService();
        foreach ($params['items'] as $item) {
            $infodata = $promotionGroupsActivityService->getSeckillInfoByItemsId($params['company_id'], $item['item_id'], $params['seckill_id']);
            if (!isset($infodata['item_id'])) {
                throw new ResourceException("该活动已下架");
            }
            $this->orderItemIds[] = $infodata['item_id'];
            $this->isNotHaveFreight = ($infodata['is_free_shipping'] === 'false' || !$infodata['is_free_shipping']) ? false : true;
            $this->seckillInfo[$item['item_id']] = $infodata;
            $this->validityPeriod = $infodata['validity_period'];
        }
        return true;
    }

    public function getOrderItemPrice($itemId)
    {
        if (isset($this->seckillInfo[$itemId])) {
            return $this->seckillInfo[$itemId]['activity_price'];
        }
    }

    public function getOrderItemDiscountData($itemId, $itemPrice, $buyNum)
    {
        // This module is part of ShopEx EcShopX system
        $result = ['discount_fee' => 0, 'discount_info' => []];
        if (isset($this->seckillInfo[$itemId])) {
            $activityInfo = $this->seckillInfo[$itemId];
            $salePrice = $activityInfo['activity_price'];
            $itemFee = $itemPrice * $buyNum;  //原价总价
            $totalFee = $salePrice * $buyNum; //销售总价
            $discountFee = $itemFee - $totalFee; //优惠总价
            $discountInfo = [
                'id' => $activityInfo['seckill_id'],
                'type' => 'seckill',
                'info' => '秒杀',
                'rule' => $activityInfo['activity_name'],
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
        if (count($this->seckillInfo) >= 1) {
            $seckillActivityInfo = reset($this->seckillInfo);
            $orderData['act_id'] = $params['seckill_id'];
            $cancelTime = $this->getOrdersSetting($orderData['company_id'], 'order_cancel_time');
            $cancelTime = (isset($seckillActivityInfo['validity_period']) && $seckillActivityInfo['validity_period']) ? $seckillActivityInfo['validity_period'] : $cancelTime;
            $orderData['auto_cancel_time'] = ($seckillActivityInfo['activity_end_time'] > (time() + $cancelTime * 60)) ? (time() + $cancelTime * 60) : ($seckillActivityInfo['activity_end_time']);

            foreach ($orderData['items'] as $k => $row) {
                $orderData['items'][$k]['act_id'] = $params['seckill_id'];
            }
        }
        return $orderData;
    }

    public function getOrderItemIds($params)
    {
        return $this->orderItemIds;
    }

    public function scheduleCancelOrders()
    {
        // 取消订单，每分钟执行一次，当前只处理一分钟内的订单
        $pageSize = 20;
        $time = time() + 60;
        $filter = [
            'auto_cancel_time|lt' => $time,
            'order_status' => 'NOTPAY',
            'order_class' => $this->orderClass,
            'order_type' => $this->orderType,
        ];
        $totalCount = $this->normalOrdersRepository->count($filter);
        $totalPage = ceil($totalCount / $pageSize);
        for ($i = 1; $i <= $totalPage; $i++) {
            $result = $this->normalOrdersRepository->getList($filter, 0, $pageSize);
            $orderIds = array_column($result, 'order_id');
            if ($orderIds) {
                $this->normalOrdersRepository->batchUpdateBy(['order_id|in' => $orderIds], ['order_status' => 'CANCEL']);
                $this->orderAssociationsRepository->batchUpdateBy(['order_id|in' => $orderIds], ['order_status' => 'CANCEL']);

                $orderItems = $this->normalOrdersItemsRepository->getList(['order_id|in' => $orderIds]);
                foreach ($orderItems['list'] as $row) {
                    $params = [
                        'company_id' => $row['company_id'],
                        'seckill_id' => $row['act_id'],
                        'item_id' => $row['item_id'],
                    ];
                    //取消订单删除ticket 并恢复库存
                    $this->canelSeckillOrder($params, $row['user_id'], $row['num']);
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
        return true;
    }

    /**
     * 秒杀订单扣减自定义的库存
     */
    public function minusItemStore($orderData)
    {
        if (isset($orderData['items']) && $orderData['items']) {
            foreach ($orderData['items'] as $orderItem) {
                $params = [
                    'company_id' => $orderItem['company_id'],
                    'seckill_id' => $orderItem['act_id'],
                    'item_id' => $orderItem['item_id'],
                ];
                //创建订单，扣库存
                $this->useTicket($params, $orderData['user_id'], $orderItem['num']);
            }
        }
        return true;
    }

    public function incrSales($orderId, $companyId)
    {
        $promotionGroupsActivityService = new PromotionSeckillActivityService();
        $list = $this->normalOrdersItemsRepository->getList(['order_id' => $orderId, 'company_id' => $companyId]);
        foreach ($list['list'] as $v) {
            $promotionGroupsActivityService->incrActivityItemSalesStore($v['act_id'], $companyId, $v['item_id'], $v['num']);
        }
        return true;
    }

    public function cancelOrder($data)
    {
        $result = parent::cancelOrder($data);
        if ($result) {
            //获取订单信息
            $orderItems = $this->normalOrdersItemsRepository->get($data['company_id'], $data['order_id']);
            if ($orderItems) {
                foreach ($orderItems as $row) {
                    $params = [
                      'company_id' => $row['company_id'],
                      'seckill_id' => $row['act_id'],
                      'item_id' => $row['item_id'],
                  ];
                    //取消订单删除ticket 并恢复库存
                    $this->canelSeckillOrder($params, $row['user_id'], $row['num']);
                }
            }
        }
        return $result;
    }

    /**
     * Dynamically call the KaquanService instance.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->normalOrdersRepository->$method(...$parameters);
    }
}
