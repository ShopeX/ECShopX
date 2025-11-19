<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
