<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\DadaCenter\Config;

class UrlConfig
{
    // 新增配送单
    public const ORDER_ADD_URL = "/api/order/addOrder";

    // 重新发布订单
    public const RE_ADD_ORDER = "/api/order/reAddOrder";

    // 查询订单运费
    public const QUERY_DELIVER_FEE = "/api/order/queryDeliverFee";

    // 查询运费后发单
    public const ADD_AFTER_QUERY = "/api/order/addAfterQuery";

    // 取消订单
    public const FORMAL_CANCEL = "/api/order/formalCancel";

    // 妥投异常之物品返回完成
    public const CONFIRM_GOODS = "/api/order/confirm/goods";

    // 获取取消原因列表
    public const CANCEL_REASONS = '/api/order/cancel/reasons';

    // 新增门店
    public const SHOP_ADD_URL = "/api/shop/add";

    // 更新门店
    public const SHOP_UPDATE_URL = "/api/shop/update";

    // 获取城市信息列表
    public const CITY_ORDER_URL = "/api/cityCode/list";

    // 商户注册
    public const MERCHANT_ADD_URL = "/merchantApi/merchant/add";

    // 生成充值链接
    public const RECHARGE_URL = "/api/recharge";

    // 查询账户余额
    public const BALANCE_QUERY_URL = "/api/balance/query";
}
