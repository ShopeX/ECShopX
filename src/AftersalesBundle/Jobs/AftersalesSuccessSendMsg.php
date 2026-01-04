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

namespace AftersalesBundle\Jobs;

use EspierBundle\Jobs\Job;

use OrdersBundle\Entities\NormalOrders;
use AftersalesBundle\Services\AftersalesService;

class AftersalesSuccessSendMsg extends Job
{
    public $companyId = '';
    public $orderId = '';
    public $aftersalesBn = '';
    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($companyId, $orderId, $aftersalesBn)
    {
        $this->companyId = $companyId;
        $this->orderId = $orderId;
        $this->aftersalesBn = $aftersalesBn;
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
            $filter = [
                'company_id' => $this->companyId,
                'order_id' => $this->orderId,
            ];

            $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
            $order = $normalOrdersRepository->getInfo($filter);

            if (!($order['wxa_appid'] ?? '')) {
                return true;
            }

            if (!($order['open_id'] ?? '')) {
                return true;
            }

            $aftersalesService = new AftersalesService();
            $filter = [
                'company_id' => $this->companyId,
                'aftersales_bn' => $this->aftersalesBn
            ];
            $aftersales = $aftersalesService->getAftersales($filter);

            $wxaTemplateMsgData = [
                'order_id' => $aftersales['order_id'],
                'refund_fee' => ($aftersales['refund_fee'] / 100) . '元',
                'remarks' => '您的售后已审核成功，请填写回寄物流！',
            ];
            $sendData['scenes_name'] = 'aftersalesSuccess';
            $sendData['company_id'] = $this->companyId;
            $sendData['appid'] = $order['wxa_appid'];
            $sendData['openid'] = $order['open_id'];
            $sendData['data'] = $wxaTemplateMsgData;
            app('wxaTemplateMsg')->send($sendData);
        } catch (\Exception $e) {
            app('log')->debug('小程序退货回寄物流通知订阅消息发送错误'. $e->getMessage());
        }
        return true;
    }
}
