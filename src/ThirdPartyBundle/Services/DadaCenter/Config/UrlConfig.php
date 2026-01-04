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
