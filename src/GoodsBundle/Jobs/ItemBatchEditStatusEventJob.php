<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Jobs;

use EspierBundle\Jobs\Job;
use GoodsBundle\Events\ItemBatchEditStatusEvent;

class ItemBatchEditStatusEventJob extends Job
{
    protected $data = [];

    /**
     * 创建一个新的任务实例。
     *
     * @param array $data
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * 运行任务。
     *
     * @return bool
     */
    public function handle()
    {
        event(new ItemBatchEditStatusEvent($this->data));
        return true;
    }
}

