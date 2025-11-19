<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MerchantBundle\Jobs;

use EspierBundle\Jobs\Job;

//发送短信引入类
use CompanysBundle\Services\CompanysService;
use PromotionsBundle\Services\SmsManagerService;

class MerchantAuditFailNotice extends Job
{
    protected $info = [];

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($info)
    {
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
        $info = $this->info;

        $companyId = $info['company_id'];

        try {
            $mobile = $info['mobile'];
            $smsManagerService = new SmsManagerService($companyId);
            $smsManagerService->send($mobile, $companyId, 'merchant_audit_fail_notice', []);
        } catch (\Exception $e) {
            app('log')->debug('短信发送失败: merchant_audit_fail_notice =>' . $e->getMessage());
        }
    }
}
