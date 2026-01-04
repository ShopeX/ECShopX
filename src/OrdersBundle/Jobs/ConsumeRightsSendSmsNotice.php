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

namespace OrdersBundle\Jobs;

use EspierBundle\Jobs\Job;

//发送短信引入类
use PromotionsBundle\Services\SmsManagerService;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;

class ConsumeRightsSendSmsNotice extends Job
{
    protected $rightsData = [];

    protected $consumNum = '';

    protected $shopId = '';

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($shopId, $consumNum, $rightsData)
    {
        $this->rightsData = $rightsData;
        $this->consumNum = $consumNum;
        $this->shopId = $shopId;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        $rightsData = $this->rightsData;
        $shopId = $this->shopId;
        $consumNum = $this->consumNum;

        $companyId = $rightsData['company_id'];

        try {
            $shopName = '';
            if ($shopId) {
                $shopsService = new ShopsService(new WxShopsService());
                $shopInfo = $shopsService->getShopInfoByShopId($shopId);
                $shopName = $shopInfo['store_name'];
            }
            $available_times = ($rightsData['is_not_limit_num'] ?? 2) == 1 ? '无限次数' : ($rightsData['total_num'] - $rightsData['total_consum_num']);
            $data = [
                'rights_name' => $rightsData['rights_name'],
                'shop_name' => $shopName,
                'num' => $consumNum,
                'available_times' => $available_times,
            ];
            $mobile = $rightsData['mobile'];
            $smsManagerService = new SmsManagerService($companyId);
            $smsManagerService->send($mobile, $companyId, 'hexiao_notice', $data);
        } catch (\Exception $e) {
            app('log')->debug('短信发送失败: hexiao_notice =>'.$e->getMessage());
        }
    }
}
