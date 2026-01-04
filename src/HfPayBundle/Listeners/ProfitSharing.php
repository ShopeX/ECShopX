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

namespace HfPayBundle\Listeners;

use HfPayBundle\Events\HfpayProfitSharingEvent;
use HfPayBundle\Services\HfpayService;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\OrderProfitSharing;
use OrdersBundle\Entities\OrderProfitSharingDetails;
use OrdersBundle\Entities\Trade;

class ProfitSharing
{
    /**
     * Handle the event.
     *
     * @param  HfpayProfitSharingEvent $event
     * @return bool
     */
    public function handle(HfpayProfitSharingEvent $event)
    {
        //清空缓存，防止数据不一致
        $em = app('registry')->getManager('default');
        $em->clear();

        $order_profit_sharing_id = $event->entities['order_profit_sharing_id'];
        $orderProfitSharingRepository = app('registry')->getManager('default')->getRepository(OrderProfitSharing::class);
        $orderProfitSharingDetailsRepository = app('registry')->getManager('default')->getRepository(OrderProfitSharingDetails::class);
        $orderRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        if (empty($order_profit_sharing_id)) {
            return true;
        }
        foreach ($order_profit_sharing_id as $key => $val) {
            $data = $orderProfitSharingRepository->getInfoById($val);
            $shareDetals = $orderProfitSharingDetailsRepository->getLists(['sharing_id' => $val]);
            if (empty($data)) {
                continue;
            }

            if ($data['status'] == 1) {
                continue;
            }

            //查询订单信息
            $filter = [
                'order_id' => $data['order_id']
            ];
            $orderInfo = $orderRepository->getInfo($filter);

            //查询交易单号
            $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
            $filter = [
                'company_id' => $orderInfo['company_id'],
                'order_id' => $data['order_id'],
            ];
            $trade = $tradeRepository->getTradeList($filter);
            if ($trade['list']) {
                $tradeInfo = $trade['list'][0];
            }

            $div_details = [];
            $company_id = $data['company_id'];
            foreach ($shareDetals as $v) {
                if ($v['total_fee'] < 1) {
                    continue;
                }
                $div_details[] = [
                    'divCustId' => $v['channel_id'],
                    'divAcctId' => $v['channel_acct_id'],
                    'divAmt' => bcdiv($v['total_fee'], 100, 2),
                ];
            }
            $total_fee = bcdiv($data['total_fee'], 100, 2);
            $params = [
                'org_order_id' => $tradeInfo['tradeId'],
                'org_order_date' => date('Ymd', $orderInfo['create_time']),
                'org_trans_type' => '27',
                'trans_amt' => $total_fee,
                'div_details' => json_encode($div_details),
            ];
            $service = new HfpayService($company_id);
            $reslut = $service->pay006($params);

            app('log')->debug('汇付天下延迟分账确认接口，接口返回信息：' . json_encode($reslut));
            if ($reslut['resp_code'] == 'C00000') {
                $status = 1;
            } else {
                $status = 2;
            }
            //存储分账结果
            $filter = [
                'order_profit_sharing_id' => $val
            ];
            $data = [
                'status' => $status,
                'hf_order_id' => $reslut['order_id'] ?? '',
                'hf_order_date' => $reslut['order_date'] ?? '',
                'resp_code' => $reslut['resp_code'],
                'resp_desc' => $reslut['resp_desc']
            ];
            $orderProfitSharingRepository->updateOneBy($filter, $data);
        }

        return true;
    }
}
