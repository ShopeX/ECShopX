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

use CompanysBundle\Services\OperatorsService;
use OrdersBundle\Events\PaySuccessEvent;
use PromotionsBundle\Services\SmsManagerService;
use CompanysBundle\Services\CompanysService;

use Illuminate\Contracts\Queue\ShouldQueue;
use EspierBundle\Listeners\BaseListeners;

class TradeFinishSmsNotifySupplier extends BaseListeners implements ShouldQueue
{
    protected $queue = 'sms';

    /**
     * Handle the event.
     *
     * @param  PaySuccessEvent  $event
     * @return void
     */
    public function handle(PaySuccessEvent $event)
    {
        app('log')->info('开始下单成功通知供应商短信发送 ');
        // 积分支付订单不需要
        if (in_array($event->entities['order_type'], ['point', 'deposit'])) {
            return true;
        }

        $companyId = $event->entities['company_id'];
        $supplierId = $event->entities['supplier_id'];
        $orderId = $event->entities['order_id'];
        $createTime = $event->entities['create_time'];

        $companysService = new CompanysService();
        $shopexUid = $companysService->getPassportUidByCompanyId($companyId);
        if (!$shopexUid) {
            app('log')->debug('下单成功通知供应商短信发送失败，失败原因company_id获取shopexUid失败 companyId: '.$companyId);
            return true;
        }

        $operatorsService = new OperatorsService();
        $supplierInfo = $operatorsService->getInfo(['company_id' => $companyId, 'operator_id' => $supplierId, 'operator_type' => 'supplier']);
        if (!$supplierInfo) {
            app('log')->debug('下单成功通知供应商短信发送失败，获取供应商失败 supplierId: '. $supplierId);
            return true;
        }
        $mobile = $supplierInfo['mobile'];
        //通知供应商
        $data = [
            'create_time' => date("Y-m-d H:i:s", $createTime),
            'order_id' => $orderId,
        ];
        try {

            $smsManagerService = new SmsManagerService($companyId);
            $smsManagerService->send($mobile, $companyId, 'trade_pay_success_notice', $data);
        } catch (\Exception $e) {
            app('log')->debug('下单成功通知供应商短信发送失败: '.$e->getMessage());
        }

        app('log')->info('结束下单成功通知供应商短信发送 ');
    }
}
