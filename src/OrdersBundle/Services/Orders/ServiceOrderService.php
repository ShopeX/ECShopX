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

class ServiceOrderService extends AbstractServiceOrder
{
    public $orderClass = 'normal';

    public $orderType = 'service';

    // 创建订单服务类订单必须验证门店
    public $isCheckShopValid = true;

    // 订单是否支持优惠券优惠
    public $isSupportCouponDiscount = false;

    // 订单是否需要进行店铺验证
    public $isCheckDistributorValid = false;

    // 订单是否需要验证白名单
    public $isCheckWhitelistValid = true;

    /**
     * 创建订单自定义验证参数
     */
    public function checkCreateOrderNeedParams($params)
    {
        // FIXME: check performance
        $rules = [
            'item_id' => ['required', '商品id必填'],
            'item_num' => ['required|integer|min:1', '商品数量必填,商品数量必须为整数,商品数量最少为1'],
            'user_id' => ['required', '用户id必填'],
            'company_id' => ['required', '企业id必填'],
            'mobile' => ['required', '手机号必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        return true;
    }
}
