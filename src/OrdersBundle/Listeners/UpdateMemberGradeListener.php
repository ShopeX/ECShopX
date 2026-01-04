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
use MembersBundle\Services\MemberService;

class UpdateMemberGradeListener
{
    /**
     * Handle the event.
     *
     * @param  TradeFinishEvent  $event
     * @return void
     */
    public function handle(TradeFinishEvent $event)
    {
        // 积分支付订单不需要
        if (in_array($event->entities->getPayType(), ['point'])) {
            return true;
        }
        $userId = $event->entities->getUserId();
        $pay_fee = $event->entities->getPayFee();
        $companyId = $event->entities->getCompanyId();
        try {
            $memberService = new MemberService();
            $memberService->updateMemberConsumption($userId, $companyId, $pay_fee);
        } catch (\Exception $e) {
            app('log')->debug('会员等级更新错误,会员id：'.$userId. '，错误信息: '.$e->getMessage());
        }
    }
}
