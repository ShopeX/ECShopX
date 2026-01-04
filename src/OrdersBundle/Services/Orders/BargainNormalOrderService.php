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

use OrdersBundle\Services\CartService;
use PromotionsBundle\Entities\UserBargains;
use PromotionsBundle\Entities\BargainPromotions;

// 实体类商品助力
class BargainNormalOrderService extends AbstractNormalOrder
{
    public $orderClass = 'bargain';

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

    // 订单是否支持获取积分
    public $isSupportGetPoint = true;

    //订单是否支持积分抵扣
    public $isSupportPointDiscount = true;

    public $bargainInfo;

    /**
     * 创建订单类型自身服务是否检查必填参数
     */
    public function checkCreateOrderNeedParams($params)
    {
        // ShopEx EcShopX Business Logic Layer
        $rules = [
            'company_id' => ['required', '企业id必填'],
            'user_id' => ['required', '用户id必填'],
            'mobile' => ['required', '未授权手机号，请授权'],
            'bargain_id' => ['required', '助力id必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        return true;
    }

    // 获取购物车商品
    public function checkoutCartItems($params)
    {
        $cartService = new CartService();
        $items = $cartService->getFastBuyCart($params['company_id'], $params['user_id']);
        if ($items && (!isset($params['items']) || !$params['items'])) {
            $params['items'][] = [
                'item_id' => $items['item_id'],
                'num' => $items['num'],
            ];
        }
        return $params;
    }

    // 验证活动
    public function check($params)
    {
        // ShopEx EcShopX Business Logic Layer
        $bargain_info_filter['bargain_id'] = $params['bargain_id'];
        $bargain_info_filter['user_id'] = $params['user_id'];
        $bargain_info_filter['is_ordered'] = 0;         // 未生产订单的活动
        $bargain_info = app('registry')->getManager('default')->getRepository(UserBargains::class)->get($bargain_info_filter);
        if (empty($bargain_info)) {
            throw new ResourceException("未找到助力活动信息！");
        }
        $this->bargainInfo = $bargain_info;
        return true;
    }

    // 获取实际销售价格
    public function getOrderItemPrice($itemId)
    {
        return $this->bargainInfo['mkt_price'] - $this->bargainInfo['cutdown_amount'];
    }

    // 获取活动信息
    public function getOrderItemDiscountData($itemId, $itemPrice, $buyNum)
    {
        $result = ['discount_fee' => 0, 'discount_info' => []];
        if (isset($this->bargainInfo) && $this->bargainInfo) {
            $activityInfo = $this->bargainInfo;

            $salePrice = $activityInfo['mkt_price'] - $activityInfo['cutdown_amount'];

            $itemFee = $itemPrice * $buyNum;  //原价总价
            $totalFee = $salePrice * $buyNum; //销售总价
            $discountFee = $itemFee - $totalFee; //优惠总价

            $discountInfo = [
                'id' => $activityInfo['bargain_id'],
                'type' => 'bargain',
                'info' => '微信助力',
                'rule' => $activityInfo['item_name'],
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

    // 订单处理
    public function formatOrderData($orderData, $params, $isCheck)
    {
        $orderData['act_id'] = $params['bargain_id'];
        return $orderData;
    }

    // 获取订单商品ID
    public function getOrderItemIds($params)
    {
        return array_column($params['items'], 'item_id');
    }

    // 更改助力订单活动状态
    public function createExtend($orderData, $params)
    {
        return true;
        // // 更改订助力订单状态（助力表中的）
        // $bargain_info_filter['bargain_id'] = $params['bargain_id'];
        // $bargain_info_filter['user_id'] = $params['user_id'];
        // $bargain_info_filter['is_ordered'] = 0;         // 未生产订单的活动
        // $bargain_info_update['is_ordered'] = 1;
        // app('registry')->getManager('default')->getRepository(UserBargains::class)->update($bargain_info_filter, $bargain_info_update);


        // // 更改活动表中的数量
        // $bargainPromotions_filter['bargain_id'] = $params['bargain_id'];
        // $BargainPromotions = app('registry')->getManager('default')->getRepository(BargainPromotions::class)->get($bargainPromotions_filter);
        // $bargainPromotions_update['order_num'] = $BargainPromotions['order_num'] + 1;
        // app('registry')->getManager('default')->getRepository(BargainPromotions::class)->update($bargainPromotions_filter, $bargainPromotions_update);
        // return true;
    }
    // 更改助力订单活动状态
    public function changeOrderActivityStatus($params, $state = 1)
    {
        // 更改订助力订单状态（助力表中的）
        $bargain_info_filter['bargain_id'] = $params['bargain_id'];
        $bargain_info_filter['user_id'] = $params['user_id'];
        if ($state) {
            $bargain_info_filter['is_ordered'] = 0;         // 未生产订单的活动
            $bargain_info_update['is_ordered'] = 1;
        } else {
            $bargain_info_filter['is_ordered'] = 1;         // 已生产订单的活动
            $bargain_info_update['is_ordered'] = 0;
        }
        app('registry')->getManager('default')->getRepository(UserBargains::class)->update($bargain_info_filter, $bargain_info_update);


        // 更改活动表中的数量
        $bargainPromotions_filter['bargain_id'] = $params['bargain_id'];
        $BargainPromotions = app('registry')->getManager('default')->getRepository(BargainPromotions::class)->get($bargainPromotions_filter);
        if ($state) {
            $bargainPromotions_update['order_num'] = $BargainPromotions['order_num'] + 1;
        } else {
            $bargainPromotions_update['order_num'] = $BargainPromotions['order_num'] - 1;
        }
        app('registry')->getManager('default')->getRepository(BargainPromotions::class)->update($bargainPromotions_filter, $bargainPromotions_update);
        return true;
    }

    public function scheduleCancelOrders()
    {
    }
}
