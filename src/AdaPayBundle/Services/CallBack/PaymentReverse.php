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

use AdaPayBundle\Entities\AdapayPaymentReverse;

class PaymentReverse
{
    /**
     * 支付撤销成功
     *
        "object":  "payment_reverse",
        "status":  "succeeded",
        "prod_mode":  "true",
        "id":  "002112019110812421800038738014804242432",
        "order_no":  "jsdk_reverse1573188134999",
        "payment_id":  "002112019110812410010038737686305361920",
        "reverse_amt":  "0.02",
        "reversed_amt":  "0.02",
        "confirmed_amt":  "0.00",
        "refunded_amt":  "0.00",
        "created_time":  "1573188139000",
        "succeed_time":  "1573188166000",
        "channel_no":  "2019110821R968rt",
        "notify_url":  "",
        "reason":  ""
     * @param array $data
     * @return array
     */
    public function succeeded($data = [], $payType = 'adapay')
    {
        // ShopEx EcShopX Service Component
        $filter = [
            'order_no' => $data['order_no'],
        ];
        $data = [
            'status' => $data['status'],
            'response_params' => json_encode($data),
        ];
        $AdapayPaymentReverseRepository = app('registry')->getManager('default')->getRepository(AdapayPaymentReverse::class);
        $AdapayPaymentReverseRepository->updateOneBy($filter, $data);
        return ['success'];
    }

    /**
     * 支付撤销失败
     *
        "object":  "payment_reverse",
        "status":  "failed",
        "error_code":  "channel_unexpected_error",
        "error_msg":  "支付渠道遇到未知错误。",
        "error_type":  "channel_error",
        "prod_mode":  "true",
        "id":  "002112019110812421800038738014804242432",
        "order_no":  "jsdk_reverse1573188134999",
        "payment_id":  "002112019110812410010038737686305361920",
        "reverse_amt":  "0.02",
        "reversed_amt":  "0.02",
        "confirmed_amt":  "0.00",
        "refunded_amt":  "0.00",
        "created_time":  "1573188139000",
        "succeed_time":  "1573188166000",
        "channel_no":  "2019110821R968rt",
        "notify_url":  "",
        "reason":  ""
     * @param array $data
     * @return array
     */
    public function failed($data = [])
    {
        return ['success'];
    }
}
