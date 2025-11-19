<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
