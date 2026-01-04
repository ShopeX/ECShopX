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

class Refund
{
    /**
     * 退款成功
     *
        "payment_id": "002112019072917310300001847119303360512",
        "created_time": "1564736347000",
        "error_code": "",
        "error_msg": "",
        "fee_amt": "0.00",
        "id": "002112019080216590600003288632355946496",
        "status": "succeeded",
        "pay_amt": "0.04",
        "error_type": ""
     * @param array $data
     * @return array
     */
    public function succeeded($data = [])
    {
        return ['success'];
    }

    /**
     * 退款失败
     *
        "payment_id": "002112019072917310300001847119303360512",
        "created_time": "1564736347000",
        "error_code": "channel_unexpected_error",
        "error_msg": "支付渠道遇到未知错误。",
        "fee_amt": "0.00",
        "id": "002112019080216590600003288632355946496",
        "status": "failed",
        "pay_amt": "0.04",
        "error_type": "channel_error"
     * @param array $data
     * @return array
     */
    public function failed($data = [])
    {
        return ['success'];
    }
}
