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

namespace ThirdPartyBundle\Services\ShansongCenter\Config;

class UrlConfig
{

    // 查询开通城市
    public const OPEN_CITIES_LISTS = '/openapi/merchants/v5/openCitiesLists';

    // 分页查询商户店铺
    public const QUERY_ALL_STORES = '/openapi/merchants/v5/queryAllStores';

    // 查询城市可指定的交通工具
    public const OPTIONAL_TRAVEL_WAY = '/openapi/merchants/v5/optionalTravelWay';

    // 订单计费
    public const ORDER_CALCULATE = '/openapi/merchants/v5/orderCalculate';

    // 提交订单
    public const ORDER_PLACE = '/openapi/merchants/v5/orderPlace';

    // 订单加价
    public const ADDITION = '/openapi/merchants/v5/addition';

    // 查询订单详情
    public const ORDER_INFO = '/openapi/merchants/v5/orderInfo';

    // 查询闪送员位置信息
    public const COURIER_INFO = '/openapi/merchants/v5/courierInfo';

    // 查询订单续重加价金额
    public const CALCULATE_ORDER_ADD_WEIGHT_FEE = '/openapi/merchants/v5/calculateOrderAddWeightFee';

    // 支付订单续重费用
    public const PAY_ADD_WEIGHT_FEE = '/openapi/merchants/v5/payAddWeightFee';

    // 订单预取消
    public const PRE_ABORT_ORDER = '/openapi/merchants/v5/preAbortOrder';

    // 订单取消
    public const ABORT_ORDER = '/openapi/merchants/v5/abortOrder';

    // 确认物品送回
    public const CONFIRM_GOODS_RETURN = '/openapi/merchants/v5/confirmGoodsReturn';

    // 新增/修改店铺
    public const STORE_OPERATION = '/openapi/merchants/v5/storeOperation';

    // 查询账号额度
    public const GET_USER_ACCOUNT = '/openapi/merchants/v5/getUserAccount';

    // 修改收件人手机号
    public const UPDATE_TO_MOBILE = '/openapi/merchants/v5/updateToMobile';

    // 批量新增店铺
    public const ADD_STORES = '/openapi/merchants/v5/addStores';

    // 查询订单ETA
    public const ORDER_ETA = '/openapi/merchants/v5/orderEta';

    // 订单追单
    public const APPEND_ORDER = '/openapi/merchants/v5/appendOrder';

    // 查询是否支持尊享送
    public const QUALITY_DELIVERY_SWITCH = '/openapi/merchants/v5/qualityDeliverySwitch';

    // 查询尊享送达成状态
    public const QUALITY_DELIVERY_STATUS = '/openapi/merchants/v5/qualityDeliveryStatus';
}
