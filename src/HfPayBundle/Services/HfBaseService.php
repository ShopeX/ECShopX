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

namespace HfPayBundle\Services;

class HfBaseService
{
    /**
     * @param int $length
     * @param string $prefix
     * @param string $suffix
     * @return string
     *
     *  生成汇付order_id（必须保证唯一， 50位内的字母或数字组合）
     */
    public function getOrderId()
    {
        $redisId = app('redis')->incr('hfpay_order_id');
        app('redis')->expire('hfpay_order_id', strtotime(date('Y-m-d 23:59:59', time())));
        $max_length = 9;

        return date('Ymd'). str_pad($redisId, $max_length, '0', STR_PAD_LEFT);
    }

    /**
     * @param int $length
     * @param string $prefix
     * @param string $suffix
     * @return string
     *
     * 开户申请号，商户下唯一
     */
    public function getApplyId()
    {
        $redisId = app('redis')->incr('hfpay_apply_id');
        app('redis')->expire('hfpay_apply_id', strtotime(date('Y-m-d 23:59:59', time())));
        $max_length = 8;

        return date('Ymd'). str_pad($redisId, $max_length, '0', STR_PAD_LEFT);
    }

    public function getAttachNo()
    {
        return date('YmdHis', time()) . mt_rand(10000, 99999);
    }
}
