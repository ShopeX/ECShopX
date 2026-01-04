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
