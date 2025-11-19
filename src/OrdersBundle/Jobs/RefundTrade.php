<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Jobs;

use EspierBundle\Jobs\Job;
use OrdersBundle\Services\TradeService;

class RefundTrade extends Job
{
    public $tradeId;

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($tradeId)
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
        $this->tradeId = $tradeId;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
        $tradeService = new TradeService();
        $tradeService->refundTrade($this->tradeId, false);
        return true;
    }
}
