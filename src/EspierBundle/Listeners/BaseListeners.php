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
