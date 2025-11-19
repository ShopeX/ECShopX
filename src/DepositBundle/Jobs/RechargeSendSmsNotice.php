<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DepositBundle\Jobs;

use EspierBundle\Jobs\Job;

//发送短信引入类
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;
use DepositBundle\Services\DepositTrade;
use PromotionsBundle\Services\SmsManagerService;

class RechargeSendSmsNotice extends Job
{
    protected $companyId = '';

    protected $userId = '';

    protected $mobile = '';

    protected $totalFee = '';

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($companyId, $userId, $mobile, $totalFee)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->mobile = $mobile;
        $this->totalFee = $totalFee;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        try {
            $depositTrade = new DepositTrade();
            $depositMoney = $depositTrade->getUserDepositTotal($this->companyId, $this->userId);

            $shopsService = new ShopsService(new WxShopsService());
            $shopSetting = $shopsService->getWxShopsSetting($this->companyId);
            $data = [
                'brand_name' => $shopSetting['brand_name'],
                'recharge_date' => date('Y-m-d H:i:s'),
                'recharge_money' => $this->totalFee / 100,
                'deposit_money' => $depositMoney / 100
            ];
            $smsManagerService = new SmsManagerService($this->companyId);
            $smsManagerService->send($this->mobile, $this->companyId, 'deposit_recharge', $data);
        } catch (\Exception $e) {
            app('log')->debug('短信发送失败: deposit_recharge =>'.$e->getMessage());
        }
    }
}
