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

use OrdersBundle\Traits\SeckillStoreTicket;
use PromotionsBundle\Services\PromotionSeckillActivityService;

class SeckillServiceOrderService extends AbstractNormalOrder
{
    use SeckillStoreTicket;

    public $orderClass = 'seckill';

    public $orderType = 'service';

    // 订单是否支持优惠券优惠
    public $isSupportCouponDiscount = false;

    // 订单是否需要进行门店验证
    public $isCheckShopValid = true;

    // 订单是否需要进行店铺验证
    public $isCheckDistributorValid = false;

    //活动是否包邮
    public $isNotHaveFreight = false;

    // 订单是否需要验证白名单
    public $isCheckWhitelistValid = true;

    public $orderItemIds;
    public $seckillInfo;
    public $serviceOrderRepository;

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
            'seckill_id' => ['required', '秒杀活动id必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        return true;
    }

    public function check($params)
    {
        $this->checkTicket($params['seckill_ticket'], $params['user_id'], $params['company_id']);

        $promotionGroupsActivityService = new PromotionSeckillActivityService();
        $infodata = $promotionGroupsActivityService->getSeckillInfoByItemsId($params['company_id'], $params['item_id'], $params['seckill_id']);
        $this->orderItemIds[] = $params['item_id'];
        $this->isNotHaveFreight = ($infodata['is_free_shipping'] === 'false' || !$infodata['is_free_shipping']) ? false : true;
        $this->seckillInfo[$params['item_id']] = $infodata;

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
        if (count($this->seckillInfo) > 1) {
            $seckillActivityInfo = reset($this->seckillInfo);
            $orderData['bargain_id'] = $params['seckill_id'];
            $cancelTime = $this->getOrdersSetting($orderData['company_id'], 'order_cancel_time');
            $cancelTime = (isset($seckillActivityInfo['validity_period']) && $seckillActivityInfo['validity_period']) ? $seckillActivityInfo['validity_period'] : $cancelTime;
            $orderData['auto_cancel_time'] = ($seckillActivityInfo['activity_end_time'] > (time() + $cancelTime * 60)) ? (time() + $cancelTime * 60) : ($seckillActivityInfo['activity_end_time']);
        }
        return $orderData;
    }

    public function getOrderItemIds($params)
    {
        return $this->orderItemIds;
    }

    public function scheduleCancelOrders()
    {
        return true;
        // 取消订单，每分钟执行一次，当前只处理一分钟内的订单
        /*$pageSize = 20;
        $time = time() + 60;
        $filter = [
            'auto_cancel_time|lt' => $time,
            'order_status' => 'NOTPAY',
            'order_class' => $this->orderClass,
            'order_type' => $this->orderType,
        ];
        $totalCount = $this->serviceOrderRepository->countOrderNum($filter);
        $totalPage = ceil($totalCount/$pageSize);
        for($i=1; $i<= $totalPage; $i++) {
            $result = $this->serviceOrderRepository->getOrderList($filter, 1, $pageSize);
            $orderIds = array_column($result['list'], 'order_id');
            if ($orderIds) {
                $this->serviceOrderRepository->batchUpdateBy(['order_id|in'=>$orderIds], ['order_status'=>'CANCEL']);
                $this->orderAssociationsRepository->batchUpdateBy(['order_id|in'=>$orderIds], ['order_status'=>'CANCEL']);
                foreach($result['list'] as $row){
                    $params = [
                        'company_id' => $row['company_id'],
                        'seckill_id' => $row['bargain_id'],
                        'item_id' => $row['item_id'],
                    ];
                    //取消订单 恢复库存
                    $this->canelSeckillOrder($params, $row['user_id'], $row['item_num']);
                }
            }
        }
        return true;
     */
    }

    /**
     * 秒杀订单扣减自定义的库存
     */
    public function minusItemStore($orderData)
    {
        if (isset($orderData['item_id']) && $orderData['item_id']) {
            $params = [
                'company_id' => $orderData['company_id'],
                'seckill_id' => $orderData['bargain_id'],
                'item_id' => $orderData['item_id'],
            ];
            //创建订单，扣库存
            $this->useTicket($params, $orderData['user_id'], $orderData['item_num']);
        }
        return true;
    }

    public function incrSales($orderId, $companyId)
    {
        $orderItems = $this->serviceOrderRepository->getOrderInfo($companyId, $orderId);
        if ($orderItems) {
            $promotionGroupsActivityService = new PromotionSeckillActivityService();
            $promotionGroupsActivityService->incrActivityItemSalesStore($orderItems['bargain_id'], $companyId, $orderItems['item_id'], $orderItems['item_num']);
        }
        return true;
    }

    public function cancelOrder($data)
    {
        return true;
        /*$result = parent::cancelOrder($data);
        if ($result) {
            //获取订单信息
            $orderItems = $this->serviceOrderRepository->getOrderInfo($data['company_id'], $data['order_id']);
            if ($orderItems) {
                $params = [
                    'company_id' => $orderItems['company_id'],
                    'seckill_id' => $orderItems['bargain_id'],
                    'item_id' => $orderItems['item_id'],
                ];
                //取消订单删除ticket 并恢复库存
                $this->canelSeckillOrder($params, $orderItems['user_id'], $orderItems['item_num']);
            }
        }
        return $result;
     */
    }
}
