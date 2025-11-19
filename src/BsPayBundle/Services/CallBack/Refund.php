<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace BsPayBundle\Services\CallBack;

class Refund
{
    public const TRADE_PENDING = 'P';
    public const TRADE_SUCC = 'S';
    public const TRADE_FAIL = 'F';

    /**
     * 退款完成（成功/失败）
     */
    public function handle($data = [], $eventType = '', $payType = 'bspay')
    {
        
        
        return ['success'];
    }
}
