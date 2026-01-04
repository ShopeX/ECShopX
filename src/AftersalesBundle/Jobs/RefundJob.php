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

use AftersalesBundle\Repositories\AftersalesDetailRepository;
use AftersalesBundle\Repositories\AftersalesRepository;
use EspierBundle\Jobs\Job;
use AftersalesBundle\Services\AftersalesRefundService;
use AftersalesBundle\Entities\Aftersales;
use AftersalesBundle\Entities\AftersalesDetail;
use OrdersBundle\Services\Orders\BargainNormalOrderService;
use OrdersBundle\Services\Orders\NormalOrderService;
use OrdersBundle\Services\OrderService;
use OrdersBundle\Services\TradeService;

class RefundJob extends Job
{
    public $data;

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($params)
    {
        $this->data = $params;
    }

    /**
     * 运行任务。
     *
     * @return boolean
     */
    public function handle()
    {
        $params = $this->data;
        app('log')->info('schedule_refund::refundJob::handle::params::'.json_encode($params, 256));
        $refund_filter = [
            'refund_bn' => $params['refund_bn'],
            'company_id' => $params['company_id']
        ];
        $aftersalesRefundService = new AftersalesRefundService();
        $refund = $aftersalesRefundService->getInfo($refund_filter);
        app('log')->info('schedule_refund::refundJob::handle::refund_filter===>'.json_encode($refund_filter, 256).'===>refund::'.json_encode($refund, 256));
        if (empty($refund) || $refund['refund_status'] != 'AUDIT_SUCCESS') {
            return false;
        }
        // 线下转账的支付方式，不做处理
        if ($refund['pay_type'] == 'offline_pay') {
            app('log')->info('schedule_refund::refundJob::handle::refund_filter===>'.json_encode($refund_filter, 256).'===>pay_type:offline_pay无需处理');
            return true;
        }

        if (!$refund['aftersales_bn']) { // 售前退款
            $aftersalesRefundService->doRefund($refund_filter);

            $tradeId = $refund['trade_id'];
            $tradeService = new TradeService();
            $tradeInfo = $tradeService->getInfoById($tradeId);

            //助力订单处理
            if ($tradeInfo['trade_source_type'] == 'bargain') {                
                $this->__processBargainOrder($refund['company_id'], $refund['order_id']);
            }
        } else { // 售后退款            
            //处理线下支付订单
            if ($refund['refund_channel'] != 'offline') {
                $filter = [
                    'company_id' => $refund['company_id'],
                    'order_id' => $refund['order_id'],
                ];
                $normalOrderService = new NormalOrderService();
                $orderInfo = $normalOrderService->normalOrdersRepository->getInfo($filter);
                if ($orderInfo['pay_type'] == 'offline') {
                    $refund['refund_channel'] = $orderInfo['pay_type'];
                }
            }
            
            //线下退款直接更新状态
            if ($refund['refund_channel'] == 'offline') {
                $updateData = ['refund_status' => 'SUCCESS'];
                $aftersalesRefundService->updateOneBy($refund_filter, $updateData); // 更新退款单状态
            } elseif ($refund['refund_channel'] == 'original') {
                $aftersalesRefundService->doRefund($refund_filter);
            }

            $this->__updateAfterSaleFinish($refund);
        }
        return true;
    }
    
    //助力订单状态更新
    private function __processBargainOrder($companyId, $orderId)
    {
        $orderService = new OrderService(new BargainNormalOrderService());
        $orderInfo = $orderService->getOrderInfo($companyId, $orderId);
        if ($orderInfo['orderInfo']['order_class'] == 'bargain') {
            $bargainNormalOrderService = new BargainNormalOrderService();
            $params['user_id'] = $orderInfo['orderInfo']['user_id'];
            $params['bargain_id'] = $orderInfo['orderInfo']['act_id'];
            $state = 0;
            $bargainNormalOrderService->changeOrderActivityStatus($params, $state);
        }
    }
    
    //售后单更新到完成状态
    private function __updateAfterSaleFinish($refund = [])
    {
        app('log')->info('schedule_refund::refundJob::__updateAfterSaleFinish::refund::'.json_encode($refund, 256));
        $aftersales_filter = [
            'aftersales_bn' => $refund['aftersales_bn'],
            'company_id' => $refund['company_id']
        ];
        $aftersales_update = [
            'aftersales_status' => 2,//已处理。已完成
            'progress' => 4,//已处理
        ];
        app('log')->info('schedule_refund::refundJob::__updateAfterSaleFinish::aftersales_filter::'.json_encode($aftersales_filter, 256).'aftersales_update::'.json_encode($aftersales_update, 256));
        $aftersalesRepository = app('registry')->getManager('default')->getRepository(Aftersales::class);
        $aftersalesResult = $aftersalesRepository->update($aftersales_filter, $aftersales_update);// 更新售后主表状态
        app('log')->info('schedule_refund::refundJob::__updateAfterSaleFinish::aftersalesResult::'.json_encode($aftersalesResult, 256));

        $aftersalesDetailRepository = app('registry')->getManager('default')->getRepository(AftersalesDetail::class);
        $aftersalesDetailResult = $aftersalesDetailRepository->updateBy($aftersales_filter, $aftersales_update);// 更新售后明细状态
        app('log')->info('schedule_refund::refundJob::__updateAfterSaleFinish::aftersalesDetailResult::'.json_encode($aftersalesDetailResult, 256));

    }
}
