<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Jobs;

use CompanysBundle\Services\CompanysService;
use PromotionsBundle\Services\SmsDriver\ShopexSmsClient;
use PromotionsBundle\Services\SmsService;
use EspierBundle\Jobs\Job;
use ShuyunBundle\Services\SmsService as ShuyunSmsService;

class GroupSendSms extends Job
{
    public $smsData;
    public function __construct($smsData)
    {
        $this->smsData = $smsData;
    }

    public function handle()
    {
        // 数云模式
        if (config('common.oem-shuyun')) {
            return $this->handleShuyun();
        }
        $smsData = $this->smsData;
        try {
            $companyId = $smsData['company_id'];
            $mobiles = $smsData['send_to_phones'];
            $content = $smsData['sms_content'];

            app('log')->debug('ShopexSmsClient::短信群发1: fan-out::companyId=>'.$companyId);
            $companysService = new CompanysService();
            $shopexUid = $companysService->getPassportUidByCompanyId($companyId);

            app('log')->debug('ShopexSmsClient::短信群发2: fan-out::shopexUid=>'.$shopexUid);
            app('log')->debug('ShopexSmsClient::短信群发3: fan-out::mobiles=>'.json_encode($mobiles));
            $smsService = new SmsService(new ShopexSmsClient($companyId, $shopexUid));

            // 下游供应商该接口不支持批量发短信，所以改成一次提交一个手机号
            foreach ($mobiles as $mobile) {
                $smsService->sendContent($companyId, $mobile, $content, 'fan-out');
            }
        } catch (\Exception $e) {
            app('log')->debug('ShopexSmsClient::短信群发失败: fan-out::error=>'.var_export($e->getMessage(), 1));
        }
        
    }
    /**
     * 数云模式, 短信群发
     */
    private function handleShuyun()
    {
        $smsData = $this->smsData;
        try {
            $companyId = $smsData['company_id'];
            $mobiles = $smsData['send_to_phones'];
            $content = $smsData['sms_content'];

            app('log')->debug('shuyun:短信群发1: fan-out::companyId=>'.$companyId);
            app('log')->debug('shuyun:短信群发1: fan-out::mobiles=>'.json_encode($mobiles));
            // $companysService = new CompanysService();
            // $shopexUid = $companysService->getPassportUidByCompanyId($companyId);

            // app('log')->debug('短信群发2: fan-out =>'.$shopexUid);
            
            // $smsService = new SmsService(new ShopexSmsClient($companyId, $shopexUid));
            $smsService = new SmsService(new ShuyunSmsService($companyId));

            // 下游供应商该接口不支持批量发短信，所以改成一次提交一个手机号
            // foreach ($mobiles as $mobile) {
                $smsService->sendContent($companyId, $mobiles, $content, 'fan-out');
            // }
        } catch (\Exception $e) {
            app('log')->debug('shuyun::短信群发失败: fan-out::error=>'.var_export($e->getMessage(), 1));
        }
    }
}
