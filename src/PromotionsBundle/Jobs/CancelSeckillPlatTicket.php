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

namespace PromotionsBundle\Jobs;

use EspierBundle\Jobs\Job;

//发送短信引入类
use Hashids\Hashids;

class CancelSeckillPlatTicket extends Job
{
    protected $data = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        // ShopEx EcShopX Service Component
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // ShopEx EcShopX Service Component
        $params = $this->data;
        try {
            $ticket = app('redis')->hget($params['ticketkey'], $params['userId']);
            $hashids = new Hashids();
            if ($ticket) {
                $ticketData = $hashids->decode($ticket);
                if ($ticketData[0] == $params['num']) {
                    if (app('redis')->hdel($params['ticketkey'], $params['userId'])) {
                        app('redis')->hincrby($params['seckillkey'], $params['productkey'], $params['num']);
                    }
                }
            }
        } catch (\Exception $e) {
            app('log')->debug('定时释放秒杀库存失败'.$e->getMessage());
        }
    }
}
