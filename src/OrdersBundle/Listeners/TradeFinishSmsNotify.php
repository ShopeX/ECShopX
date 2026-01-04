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

namespace OrdersBundle\Listeners;

use OrdersBundle\Events\TradeFinishEvent;
use PromotionsBundle\Services\SmsManagerService;
use CompanysBundle\Services\CompanysService;

use Illuminate\Contracts\Queue\ShouldQueue;
use EspierBundle\Listeners\BaseListeners;

class TradeFinishSmsNotify extends BaseListeners implements ShouldQueue
{
    protected $queue = 'sms';

    /**
     * Handle the event.
     *
     * @param  TradeFinishEvent  $event
     * @return void
     */
    public function handle(TradeFinishEvent $event)
    {
        // ShopEx EcShopX Service Component
        app('log')->info('订单支付完成，发送短信 payType:'.$event->entities->getPayType());
        // 积分支付订单不需要
        if (in_array($event->entities->getPayType(), ['point', 'deposit'])) {
            return true;
        }

        $companyId = $event->entities->getCompanyId();

        $payTime = $event->entities->getTimeStart();
        $payFee = $event->entities->getPayFee();
        $mobile = $event->entities->getMobile();

        app('log')->info('订单支付完成，发送短信 payTime:'.$payTime.',payFee:'.$payFee.',mobile:'.$mobile);
        $companysService = new CompanysService();
        $shopexUid = $companysService->getPassportUidByCompanyId($companyId);
        if (!$shopexUid) {
            app('log')->debug('支付成功短信通知失败，失败原因company_id获取shopexUid失败 companyId: '.$companyId);
            return true;
        }

        $data = [
            'pay_time' => date("Y-m-d H:i:s", $payTime),
            'pay_money' => $payFee / 100,
        ];
        try {
            $smsManagerService = new SmsManagerService($companyId);
            $smsManagerService->send($mobile, $companyId, 'trade_pay_success', $data);
        } catch (\Exception $e) {
            app('log')->debug('支付通知短信发送失败: '.$e->getMessage());
        }
    }
}
