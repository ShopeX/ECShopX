<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Traits;

trait GetOrderStatisticsKey
{
    //统计商城订单支付订单数
    public function getOrderPayNumKey($companyId, $type)
    {
        return "OrderPayStatistics:Num:". $type. ":". $companyId;
    }

    //统计商城订单总支付金额
    public function getOrderPayFeeKey($companyId, $type)
    {
        return "OrderPayStatistics:PayFee:". $type. ":". $companyId;
    }

    //统计商城订单支付会员数
    public function getOrderPayUserNumKey($companyId, $type, $date)
    {
        return "OrderPayStatistics:PayUserNum:". $type. ":". $companyId.":".$date;
    }

    //统计店铺订单支付订单数
    public function getStoreOrderPayNumKey($companyId, $type, $date)
    {
        return "OrderPayStatistics:StorePayNum:". $type. ":". $companyId. ":". $date;
    }

    //统计店铺订单总金额
    public function getStoreOrderPayFeeKey($companyId, $type, $date)
    {
        return "OrderPayStatistics:StorePayFee:". $type. ":". $companyId. ":". $date;
    }

    //统计店铺订单支付会员数
    public function getStoreOrderPayUserNumKey($companyId, $type, $date, $storeId)
    {
        return "OrderPayStatistics:StorePayUserNum:". $type. ":". $companyId. ":". $date.":".$storeId;
    }
}
