<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Constants;

/**
 * 常量 > 订单配送类型
 */
class OrderReceiptTypeConstant
{
    /**
     * 普通快递
     */
    public const LOGISTICS = "logistics";

    /**
     * 客户自提
     */
    public const ZITI = "ziti";

    /**
     * 同城配
     */
    public const DADA = "dada";

    /**
     * 商家自配
     */
    public const MERCHANT = "merchant";
}
