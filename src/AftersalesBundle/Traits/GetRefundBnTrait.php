<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AftersalesBundle\Traits;

trait GetRefundBnTrait
{
    //创建退款申请单编号
    public function __genRefundBn()
    {
        $sign = '2'.date("Ymd");
        $randval = substr(implode(null, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 10);
        return $sign.$randval;
    }
}
