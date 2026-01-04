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

use HfPayBundle\Entities\HfpayCashRecord;
use HfPayBundle\Events\HfPayDistributorWithdrawEvent;
use HfPayBundle\Services\AcouService;

class DistributorWithdrawListener
{
    public function handle(HfPayDistributorWithdrawEvent $event)
    {
        //清空缓存，防止数据不一致
        $em = app('registry')->getManager('default');
        $em->clear();

        $hfpay_cash_record_id = $event->entities['hfpay_cash_record_id'];
        $hfpayCashRecordRepository = app('registry')->getManager('default')->getRepository(HfpayCashRecord::class);
        $data = $hfpayCashRecordRepository->getInfoById($hfpay_cash_record_id);
        if (empty($data)) {
            return true;
        }

        if ($data['trans_amt'] < 1) {
            return true;
        }
        $user_cust_id = $data['user_cust_id'];
        $company_id = $data['company_id'];
        $trans_amt = bcdiv($data['trans_amt'], 100, 2);
        $bind_card_id = $data['bind_card_id'];
        $cash_type = $data['cash_type'];
        //发起提现
        $dev_info_json = [
            'ipAddr' => get_client_ip(),
            'devType' => '1',
            'MAC' => 'D4-81-D7-F0-42-F8', //暂时写死无实际用处，客户端没有获取MAC地址
        ];
        $params = [
            'order_id' => $data['order_id'],
            'user_cust_id' => $user_cust_id,
            'trans_amt' => $trans_amt,
            'bind_card_id' => $bind_card_id,
            'cash_type' => $cash_type,
            'dev_info_json' => json_encode($dev_info_json),
            'mer_priv' => 'cash01_distributor'
        ];

        $service = new AcouService($company_id);
        $result = $service->cash01($params);
        //保存提现申请状态
        if ($result['resp_code'] == 'C00001') {
            $cash_status = 1;
        } else {
            $cash_status = 3;
        }
        $resp_code = $result['resp_code'] ?? '';
        $resp_desc = $result['resp_desc'] ?? '';
        $hf_order_id = isset($result['order_id']) ? $result['order_id'] : '';
        $hf_order_date = isset($result['order_date']) ? $result['order_date'] : '';
        $fee_amt = 0;
        if (isset($result['fee_amt']) && !empty($result['fee_amt'])) {
            $fee_amt = bcmul($result['fee_amt'], 100);
        }
        $filter = [
            'hfpay_cash_record_id' => $hfpay_cash_record_id
        ];
        $params = [
            'cash_status' => $cash_status,
            'fee_amt' => $fee_amt,
            'resp_code' => $resp_code,
            'resp_desc' => $resp_desc,
            'hf_order_id' => $hf_order_id,
            'hf_order_date' => $hf_order_date
        ];
        $hfpayCashRecordRepository->updateOneBy($filter, $params);

        return true;
    }
}
