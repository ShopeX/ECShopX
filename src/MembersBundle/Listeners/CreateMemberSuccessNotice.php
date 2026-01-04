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

namespace MembersBundle\Listeners;

use MembersBundle\Events\CreateMemberSuccessEvent;
use CompanysBundle\Services\CompanysService;
use PromotionsBundle\Services\SmsManagerService;

use MembersBundle\Services\MemberService;

use EspierBundle\Listeners\BaseListeners;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateMemberSuccessNotice extends BaseListeners implements ShouldQueue
{
    protected $queue = 'slow';

    /**
     * Handle the event.
     *
     * @param  TradeFinishEvent  $event
     * @return void
     */
    public function handle(CreateMemberSuccessEvent $event)
    {
        $companyId = $event->companyId;
        // $companysService = new CompanysService();
        // $setting = $companysService->getCompanySetting($companyId);
        // try {
        //     $mobile = $event->mobile;
        //     $data = [
        //         'brand_name' => $setting['brand_name'] ?? ''
        //     ];
        //     $smsManagerService = new SmsManagerService($companyId);
        //     $smsManagerService->send($mobile, $companyId, 'register_notice', $data);
        // } catch (\Exception $e) {
        //     app('log')->debug('短信发送失败: '.$e->getMessage());
        // }

        if ($event->openid && $event->wxa_appid) {
            try {
                $memberService = new MemberService();
                $memberInfo = $memberService->getMemberInfo(['user_id' => $event->userId]);

                $inviter = [];
                if ($event->inviter_id) {
                    $inviter = $memberService->getMemberInfo(['user_id' => $event->inviter_id]);
                }
                //发送小程序模版
                $wxaTemplateMsgData = [
                'mobile' => $event->mobile,
                'username' => $memberInfo['username'] ?? '',
                'inviter' => $inviter ? substr_replace($inviter['mobile'], '****', 3, 4) : '无',
                'date' => date('Y-m-d H:i:s'),
                'notice' => '感谢您的加入',
            ];
                $sendData['scenes_name'] = 'memberCreateSucc';
                $sendData['company_id'] = $companyId;
                $sendData['appid'] = $event->wxa_appid;
                $sendData['openid'] = $event->openid;
                $sendData['data'] = $wxaTemplateMsgData;
                app('wxaTemplateMsg')->send($sendData);
            } catch (\Exception $e) {
                app('log')->debug('会员注册发送订阅消息失败: '.$e->getMessage());
            }
        }
    }
}
