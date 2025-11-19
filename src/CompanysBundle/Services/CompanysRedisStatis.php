<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Services;

/**
* 会员卡储值交易
*/
class CompanysRedisStatis
{
    public $statisRedis;
    public function __construct()
    {
        $this->statisRedis = app('redis')->connection('statis');
    }

    public function __key($companyId, $date = null, $shopId = null)
    {
    }
}
