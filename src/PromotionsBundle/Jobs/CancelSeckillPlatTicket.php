<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
