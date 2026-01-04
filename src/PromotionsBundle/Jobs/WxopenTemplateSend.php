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


use PromotionsBundle\Services\WxaTemplateMsgService;

class WxopenTemplateSend extends Job
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
        $wxaTemplateMsgService = new WxaTemplateMsgService();
        $wxaTemplateMsgService->send($this->data, $this->isForceFire);
    }
}
