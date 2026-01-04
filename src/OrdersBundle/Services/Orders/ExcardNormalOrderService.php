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

class ExcardNormalOrderService extends AbstractNormalOrder
{
    public const CLASS_NAME = 'excard';

    public $orderClass = 'excard';

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

    public $isSupportCart = false;

    //订单是否支持积分抵扣
    public $isSupportPointDiscount = false;
    // 订单是否需要验证白名单
    public $isCheckWhitelistValid = false;

    // 订单是否支持获取积分
    public $isSupportGetPoint = false;

    public function checkCreateOrderNeedParams($params)
    {
        $rules = [
            'company_id' => ['required', '企业id必填'],
            'user_id' => ['required', '用户id必填'],
            'mobile' => ['required', '未授权手机号，请授权'],
            'user_card_id' => ['required', '用户兑换券id必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        return true;
    }

    public function formatOrderData($orderData, $params, $isCheck)
    {
        // ShopEx EcShopX Core Module
        $orderData['act_id'] = $params['user_card_id'];
        $orderData['total_fee'] = 0;
        $orderData['receipt_type'] = 'ziti';
        $orderData['discount_fee'] = $orderData['items'][0]['item_fee'];
        return $orderData;
    }
}
