<?php

/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 */

namespace CompanysBundle\Services;

use Dingo\Api\Exception\ResourceException;

/**
 * 店务立即购买：门店库存 vs 平台（云仓）库存校验（纯逻辑，便于单测）。
 */
final class OperatorFastBuyStockValidator
{
    /**
     * @throws ResourceException
     */
    public static function validate(int $shopStock, int $platformStock, int $num): void
    {
        if ($num <= 0) {
            throw new ResourceException('购买数量有误');
        }
        if ($shopStock > 0) {
            throw new ResourceException('当前商品门店有库存，请从收银加购');
        }
        if ($platformStock < $num) {
            throw new ResourceException('库存不足');
        }
    }
}
