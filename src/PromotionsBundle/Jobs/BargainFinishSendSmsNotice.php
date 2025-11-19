<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
