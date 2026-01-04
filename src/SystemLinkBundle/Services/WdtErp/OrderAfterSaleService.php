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

namespace SystemLinkBundle\Services\WdtErp;

use AftersalesBundle\Services\AftersalesService;
use OrdersBundle\Traits\GetOrderServiceTrait;

use Exception;

class OrderAfterSaleService
{
    use GetOrderServiceTrait;

    /**
     * @param $companyId
     * @param $afterSaleBn
     * @return \stdClass
     * @throws Exception
     */
    public function getAfterSaleStruct($companyId, $afterSaleBn)
    {
        $aftersalesService = new AftersalesService();
        $afterInfo = $aftersalesService->aftersalesRepository->get(['aftersales_bn' => $afterSaleBn, 'company_id' => $companyId]);
        app('log')->debug('trade_after_afterInfo=>:'.var_export($afterInfo,1));
        if (!$afterInfo) {
            throw new Exception("售后单获取失败");
        }

        $afterItemList = $aftersalesService->aftersalesDetailRepository->getList(['aftersales_bn' => $afterInfo['aftersales_bn'], 'company_id' => $afterInfo['company_id']]);
        app('log')->debug('trade_after_afterItemList=>:'.var_export($afterItemList,1));
        if (!$afterItemList) {
            throw new Exception("售后单详情获取失败");
        }

        $afterData = new \stdClass();
        $afterData->refund_no = $afterInfo['aftersales_bn']; // 原始退款单号
        $afterData->num = array_sum(array_column($afterItemList['list'], 'num'));
        $afterData->tid = $afterInfo['order_id'];
        $afterData->oid = $afterInfo['order_id'] .'-'. $afterItemList['list'][0]['sub_order_id'];
        $afterData->type = $this->getAfterSaleType($afterInfo['aftersales_type']);
        $afterData->status = $this->getAfterSaleStatus($afterInfo['progress']);
        $afterData->refund_version = 1;
        $afterData->refund_amount = floatval(bcdiv($afterInfo['refund_fee'], 100, 2));
        $afterData->actual_refund_amount = floatval(bcdiv($afterInfo['refund_fee'], 100, 2));
        $afterData->title = $afterItemList['list'][0]['item_name'];
        $afterData->logistics_name = $afterInfo['sendback_data']['corp_code'] ?? '';
        $afterData->logistics_no = $afterInfo['sendback_data']['logi_no'] ?? '';
        $afterData->buyer_nick = '';
        $afterData->refund_time = date('Y-m-d H:i:s', $afterInfo['create_time']);
        $afterData->current_phase_timeout = date('Y-m-d H:i:s', $afterInfo['create_time']);
        $afterData->is_aftersale = 1;
        $afterData->reason = $afterInfo['reason'];

        return $afterData;
    }

    /**
     * @param $progress
     * @return int
     */
    private function getAfterSaleStatus($progress)
    {
        // 1取消退款,2已申请退款,3等待退货,4等待收货,5退款成功
        switch ($progress) {
            case 1: // 商家接受申请，等待消费者回寄
                return 3;
            case 2: // 消费者回寄，等待商家收货确认
                return 4;
            case 6: // 退款完成
            case 9: // 退款处理中
                return 5;
            case 0: // 等待商家处理
            case 4: // 已处理
            case 8: // 商家确认收货,等待审核退款
                return 2;
            case 3: // 已驳回
            case 5: // 退款驳回
            case 7: // 已撤销。已关闭
            default:
                return 1;
        }
    }

    /**
     * @param $aftersalesType
     * @return int
     */
    private function getAfterSaleType($aftersalesType)
    {
        // 0取消订单1退款(未发货，退款申请)2退货3换货4退款不退货
        switch ($aftersalesType) {
            case 'ONLY_REFUND':
                return 4;
            case 'REFUND_GOODS':
                return 2;
            case 'EXCHANGING_GOODS':
                return 3;
            default:
                return 0;
        }
    }

    /**
     * 修改售后状态
     *
     */
    public function aftersaleStatusUpdate($aftersalesBn, $orderId, $status, $stockinStatus)
    {
        $aftersalesService = new AftersalesService();
        $afterInfo = $aftersalesService->aftersalesRepository->get(['aftersales_bn' => $aftersalesBn, 'order_id'=> $orderId]);
        app('log')->debug('aftersaleStatusUpdate_afterInfo=>:'.var_export($afterInfo,1));

        if (!$afterInfo) {
            throw new Exception("未获取到售后单信息");
        }

        switch ($afterInfo['aftersales_type']) {
            case 'REFUND_GOODS': // 退货退款
                if ($status >= 30 && $stockinStatus === 3) {
                    $afterData = [
                        'aftersales_bn' => $afterInfo['aftersales_bn'],
                        'refuse_reason' => '',
                        'company_id' => $afterInfo['company_id'],
                    ];

                    $result = $aftersalesService->confirmReceipt($afterData);
                    app('log')->debug('aftersaleStatusUpdate_result=>:'.var_export($result,1));
                }
                break;
            case 'ONLY_REFUND': // 仅退款
                if ($status >= 30) {
                    $afterData = [
                        'aftersales_bn' => $afterInfo['aftersales_bn'],
                        'company_id' => $afterInfo['company_id'],
                    ];

                    $result = $aftersalesService->confirmRefund($afterData);
                    app('log')->debug('aftersaleStatusUpdate_result=>:'.var_export($result,1));
                }
                break;
        }
    }
}
