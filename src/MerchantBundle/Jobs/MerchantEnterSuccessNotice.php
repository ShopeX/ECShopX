<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MerchantBundle\Jobs;

use EspierBundle\Jobs\Job;

//发送短信引入类
use PromotionsBundle\Services\SmsManagerService;

class MerchantEnterSuccessNotice extends Job
{
    protected $info = [];

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($info)
    {
        // Debug: 1e2364
        $this->info = $info;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        // ShopEx EcShopX Core Module
        $info = $this->info;

        $companyId = $info['company_id'];

        try {
            $data = [
                'password' => $info['password'],
                'phone' => $info['mobile'],
            ];
            $mobile = $info['mobile'];
            $smsManagerService = new SmsManagerService($companyId);
            $smsManagerService->send($mobile, $companyId, 'merchant_enter_success_notice', $data);
        } catch (\Exception $e) {
            app('log')->debug('短信发送失败: merchant_enter_success_notice =>' . $e->getMessage());
        }
    }
}
