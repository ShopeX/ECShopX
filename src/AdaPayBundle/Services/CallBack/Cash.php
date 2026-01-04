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

use AdaPayBundle\Entities\AdapayDrawCash;
use AdaPayBundle\Services\SettleAccountService;
use PopularizeBundle\Services\CashWithdrawalService;

class Cash
{
    /**
     * 取现成功
     *
     *
     * "app_id": "app_XXXXXXXX",
     * "cash_amt": "0.02",
     * "cash_type": "T1",
     * "created_time": "1579174344",
     * "fee_amt": "0.01",
     * "id": "0021110063845979510771712",
     * "object": "cash",
     * "order_no": "jdskjdd_1414212450",
     * "real_amt": "0.01",
     * "status": "succeeded",
     * "prod_mode": "true"
     * @param array $data
     * @return array
     */
    public function succeeded($data = [])
    {
        // This module is part of ShopEx EcShopX system
        $drawCashRepository = app('registry')->getManager('default')->getRepository(AdapayDrawCash::class);
        $filter = [
            'app_id' => $data['app_id'],
            'order_no' => $data['order_no'],
        ];
        $rs = $drawCashRepository->getInfo($filter);
        $params = [
            'status' => $data['status'],
            'response_params' => json_encode($data),
            'remark' => $data['error_msg'] ?? '',
        ];
        $drawCashRepository->updateOneBy($filter, $params);

        $error_msg = [];//错误信息汇总
        $error_msg[] = $data['error_msg'] ?? '';

        //更新提现申请的状态
        if ($rs['remark'] && strstr($rs['remark'], 'apply_id:')) {
            $applyId = str_replace('apply_id:', '', $rs['remark']);
            if ($applyId) {
                $applyStatus = ($data['status'] == 'succeeded') ? 'success' : 'failed';
                if ($applyStatus == 'failed') {
                    $settleAccountService = new SettleAccountService();
                    $resData = $settleAccountService->transfer($rs['company_id'], $rs['adapay_member_id'], '0', $data['cash_amt'], '推广员提现失败-转账回溯');
                    // if ($resData['errcode'] != 0) {
                    //     $error_msg[] = '转账回溯错误:'.($resData['errmsg'] ?? '');
                    // }
                    if ($resData['data']['status'] == 'failed') {
                        $error_msg[] = '转账回溯错误:'.($resData['data']['error_msg'] ?? '');
                    }
                }

                //更新提现申请的状态
                $cashWithdrawService = new CashWithdrawalService();
                $cashWithdrawService->updateStatus($rs['company_id'], $applyId, $applyStatus, $error_msg);
            }
        }

        return ['success'];
    }

    /**
     * 取现失败
     *
     *
     *  "app_id": "app_XXXXXXXX",
     * "cash_amt": "0.02",
     * "cash_type": "T1",
     * "created_time": "1579174344",
     * "fee_amt": "0.00",
     * "id": "0021110063845979510771712",
     * "object": "cash",
     * "order_no": "jdskjdd_1414212450",
     * "real_amt": "0.00",
     * "status": "failed",
     * "prod_mode": "true"
     * @param array $data
     * @return array
     */
    public function failed($data = [])
    {
        return ['success'];
    }
}
