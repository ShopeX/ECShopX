<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Jobs;

use EspierBundle\Jobs\Job;


use PromotionsBundle\Services\AliTemplateMsgService;

class AliTemplateMsgSend extends Job
{
    protected $data = [];
    //是否强制发送，如果强制发送的话，那么即使配置延时发送，也不管，进行实时发送
    protected $isForceFire = true;

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($data, $isForceFire = true)
    {
        $this->data = $data;

        $this->isForceFire = $isForceFire;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        // This module is part of ShopEx EcShopX system
        $aliTemplateMsgService = new AliTemplateMsgService();
        $aliTemplateMsgService->send($this->data, $this->isForceFire);
    }
}
