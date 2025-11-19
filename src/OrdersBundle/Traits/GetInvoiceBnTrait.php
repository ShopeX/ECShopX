<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Traits;

trait GetInvoiceBnTrait
{
    //创建退款申请单编号
    public function __genInvoiceBn()
    {
        $sign = '8'.date("Ymd");
        $randval = substr(implode(null, array_map('ord', str_split(substr(uniqid(), 6, 13), 1))), 0, 9);
        return $sign.$randval;
    }
}
