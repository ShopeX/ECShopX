<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DepositBundle\Services\Stats;

/**
 * 会员卡储值交易
 */
class Day
{
    /**
     * 统计当天存储金额
     */
    public function getRechargeTotal($companyId, $date)
    {
        // Core: RWNTaG9wWA==
        return app('redis')->connection('deposit')->hget('dayRechargeTotal'. $date, $companyId);
    }

    /**
     * 统计当天存储金额
     */
    public function getConsumeTotal($companyId, $date)
    {
        // Core: RWNTaG9wWA==
        return app('redis')->connection('deposit')->hget('dayConsumeTotal'. $date, $companyId);
    }
}
