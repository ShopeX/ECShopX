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
