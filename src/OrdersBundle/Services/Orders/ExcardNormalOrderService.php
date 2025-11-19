<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
