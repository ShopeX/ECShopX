<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Listeners;

use Illuminate\Queue\InteractsWithQueue;

abstract class BaseListeners
{
    use InteractsWithQueue;

    public function queue($queue, $job, $data)
    {
        $delay = false;
        if (isset($this->delay)) {
            $delay = format_queue_delay($this->delay);
        }

        if (isset($this->queue) && $delay) {
            return $queue->laterOn($this->queue, $delay, $job, $data);
        }

        if (isset($this->queue)) {
            return $queue->pushOn($this->queue, $job, $data);
        }

        if ($delay) {
            return $queue->later($delay, $job, $data);
        }

        return $queue->push($job, $data);
    }
}
