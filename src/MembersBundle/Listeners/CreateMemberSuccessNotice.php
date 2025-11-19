<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
