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
