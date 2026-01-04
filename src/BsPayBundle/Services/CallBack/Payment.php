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

namespace BsPayBundle\Services\CallBack;

use OrdersBundle\Services\TradeService;
// use DepositBundle\Services\DepositTrade;

class Payment
{
    // Ref: 1996368445
    public const TRADE_PENDING = 'P';
    public const TRADE_SUCC = 'S';
    public const TRADE_FAIL = 'F';

    /**
     * 支付完成（成功/失败）
     */
    public function handle($data = [], $eventType = '', $payType = 'bspay')
    {
        // Ref: 1996368445
        if ($data['trans_stat'] == self::TRADE_SUCC) {
            $status = 'SUCCESS';
        } else {
            $status = 'PAYERROR';
        }

        $options['pay_type'] = $payType;
        $tmp = explode('.', $eventType);
        $options['pay_channel'] = $tmp[1] ?? '';
        $options['bank_type'] = isset($data['wx_response']['bank_type']) ? $data['wx_response']['bank_type'] : null;
        $options['transaction_id'] = isset($data['out_trans_id']) ? $data['out_trans_id'] : null;
        // if (isset($data['description']) && $data['description'] == 'depositRecharge') {
        //     $depositTrade = new DepositTrade();
        //     $depositTrade->rechargeCallback($tradeId, $status, $options);
        //     return ['success'];
        // }

        $tradeService = new TradeService();
        // if (isset($data['description']) && $data['description'] == 'membercard') {
        //     $tradeService->updateOneBy(['trade_id' => $data['order_no']], ['inital_response' => json_encode($data), 'adapay_div_status' => 'DIVED']);
        //     $tradeService->updateStatus($data['order_no'], $status, $options);
        //     return ['success'];
        // }

        $tradeService->updateOneBy(['trade_id' => $data['req_seq_id']], ['inital_response' => json_encode($data)]);
        $tradeService->updateStatus($data['req_seq_id'], $status, $options);

        return ['success'];
    }
}
