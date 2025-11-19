<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
