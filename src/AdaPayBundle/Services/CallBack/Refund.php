<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
