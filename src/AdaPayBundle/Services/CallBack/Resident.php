<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AdaPayBundle\Services\CallBack;

use AdaPayBundle\Services\OpenAccountService;

class Resident
{
    /**
     * 入驻成功
     *
        "request_id": "20200221081000007999",
        "prod_mode": "true"
        "type": "resident.succeeded",
        "status": "succeeded",
        "object": "batchconf",
        "alipay_stat": {
        "message": "",
        "stat": "S"
        },
        "wx_stat": {
        "message": "",
        "stat": "S"
        },
        "wx_alipay_response": {
        "alipay": {
        "message": "",
        "stat": "S"
        },
        "alipay_lite": {
        "message": "",
        "stat": "S"
        },
        "alipay_qr": {
        "message": "",
        "stat": "S"
        },
        "alipay_scan": {
        "message": "",
        "stat": "S"
        },
        "alipay_wap": {
        "message": "",
        "stat": "S"
        },
        "wx_lite": {
        "message": "",
        "stat": "S"
        },
        "wx_pub": {
        "message": "",
        "stat": "S"
        },
        "wx_scan": {
        "message": "",
        "stat": "S"
        }
        }
     * @param array $data
     * @return array
     */
    public function succeeded($data = [])
    {
        $openAccountService = new OpenAccountService();
        $openAccountService->residentCallback($data);
        return ['success'];
    }

    /**
     * 入驻失败
     *
        "request_id": "req_cfg_20200220140530952000",
        "prod_mode": "true",
        "type": "resident.failed",
        "status": "failed",
        "object": "batchconf",
        "alipay_stat": {
        "message": "根据我公司风险监测系统的监测结果，你的账户可能存在风险，暂时不能创建。原因：黑名单校验不通过",
        "stat": "F"
        },
        "wx_stat": {
        "message": "",
        "stat": "S"
        },
        "wx_alipay_response": {
        "wx_lite": {
        "message": "",
        "stat": "F"
        },
        "wx_pub": {
        "message": "",
        "stat": "S"
        },
        "wx_scan": {
        "message": "",
        "stat": "S"
        }
        }
     * @param array $data
     * @return array
     */
    public function failed($data = [])
    {
        // Hash: 0d723eca
        return ['success'];
    }
}
