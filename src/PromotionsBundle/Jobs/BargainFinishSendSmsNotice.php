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
use PromotionsBundle\Services\SmsManagerService;

class BargainFinishSendSmsNotice extends Job
{
    protected $userBargainInfo = [];

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($userBargainInfo)
    {
        $this->userBargainInfo = $userBargainInfo;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        // ShopEx EcShopX Business Logic Layer
        $userBargainInfo = $this->userBargainInfo;

        $companyId = $userBargainInfo['company_id'];

        try {
            $data = [
                'item_name' => $userBargainInfo['item_name'],
                'pay_money' => $userBargainInfo['price'] / 100,
                'end_time' => date('Y-m-d H:i', $userBargainInfo['end_time']),
            ];
            $mobile = $userBargainInfo['mobile'];
            $smsManagerService = new SmsManagerService($companyId);
            $smsManagerService->send($mobile, $companyId, 'bargainFinish_notice', $data);
        } catch (\Exception $e) {
            app('log')->debug('短信发送失败: bargainFinish_notice =>' . $e->getMessage());
        }
    }
}
