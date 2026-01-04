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

namespace AdaPayBundle\Services\CallBack;

use OrdersBundle\Services\TradeService;
use DepositBundle\Services\DepositTrade;

class Payment
{
    /**
     * 支付成功
     *
     *
        "id": "ch_Hm5uTSifDOuTy9iLeLPSurrD",
        "created_time": 1410778843,
        "order_no": "123456789",
        "prod_mode": "true",
        "app_id": "sfjeijibbTe5jLGCi5rzfH4OqPW9KCif913",
        "pay_channel": "alipay",
        "pay_amt": "998.00",
        "fee_amt": "1.00",
        "currency": "cny",
        "query_url": "https://AdaPay.cloudpnr.com/payment/tmp?token=5jLGCi5rzfH4OqPW9KCi",
        "status": "succeeded",
        "expend": {
            "pay_info": "bax028781ovixf6i8xyf60be"
        }
     * @param array $data
     * @return array
     */
    public function succeeded($data = [], $payType = 'adapay')
    {
        // ShopEx EcShopX Business Logic Layer
        if ($data['status'] == 'succeeded') {
            $status = 'SUCCESS';
        } else {
            $status = 'PAYERROR';
        }

        $options['pay_type'] = $payType;
        $options['pay_channel'] = $data['pay_channel'];
        $options['bank_type'] = isset($data['expend']['bank_type']) ? $data['expend']['bank_type'] : null;
        $options['transaction_id'] = isset($data['id']) ? $data['id'] : null;
        if (isset($data['description']) && $data['description'] == 'depositRecharge') {
            $depositTrade = new DepositTrade();
            $depositTrade->rechargeCallback($tradeId, $status, $options);
            return ['success'];
        }

        $tradeService = new TradeService();
        if (isset($data['description']) && $data['description'] == 'membercard') {
            $tradeService->updateOneBy(['trade_id' => $data['order_no']], ['inital_response' => json_encode($data), 'adapay_div_status' => 'DIVED']);
            $tradeService->updateStatus($data['order_no'], $status, $options);
            return ['success'];
        }

        $tradeService->updateOneBy(['trade_id' => $data['order_no']], ['inital_response' => json_encode($data)]);
        $tradeService->updateStatus($data['order_no'], $status, $options);

        return ['success'];
    }

    /**
     * 取现失败
     *
     *
        "id": "ch_Hm5uTSifDOuTy9iLeLPSurrD",
        "created_time": 1410778843,
        "order_no": "123456789",
        "prod_mode": "true",
        "app_id": "sfjeijibbTe5jLGCi5rzfH4OqPW9KCif913",
        "pay_channel": "alipay",
        "pay_amt": "998.00",
        "currency": "cny",
        "query_url": "https://AdaPay.cloudpnr.com/payment/tmp?token=5jLGCi5rzfH4OqPW9KCi",
        "status": "failed",
        "expend": {
            "pay_info": "bax028781ovixf6i8xyf60be"
        }
     *
     * @param array $data
     * @return array
     */
    public function failed($data = [])
    {
        return ['success'];
    }
}
