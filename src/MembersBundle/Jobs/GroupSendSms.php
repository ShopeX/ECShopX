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
