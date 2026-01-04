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

namespace OpenapiBundle\Services\Order;

use AftersalesBundle\Entities\Aftersales;
use OrdersBundle\Entities\CancelOrders;
use OpenapiBundle\Services\BaseService;
use AftersalesBundle\Services\AftersalesRefundService;

class AftersalesService extends BaseService
{
    public function getEntityClass(): string
    {
        // ShopEx EcShopX Service Component
        return Aftersales::class;
    }

    /**
     * 格式化售后列表
     * @param  array $dataList 订单列表数据
     * @param  int    $page     当前页数
     * @param  int    $pageSize 每页条数
     * @return array
     */
    public function formateAftersalesList($dataList, int $page, int $pageSize)
    {
        $result = $this->handlerListReturnFormat($dataList, $page, $pageSize);
        if (empty($dataList['list'])) {
            return $result;
        }
        $result['list'] = [];
        foreach ($dataList['list'] as $list) {
            $_list = [
                'aftersales_bn' => $list['aftersales_bn'],
                'order_id' => $list['order_id'],
                'aftersales_type' => $list['aftersales_type'],
                'aftersales_status' => $list['aftersales_status'],
                'progress' => $list['progress'],
                'refund_fee' => $list['refund_fee'],
                'refund_point' => $list['refund_point'],
                'reason' => $list['reason'],
                'description' => $list['description'],
                'evidence_pic' => $list['evidence_pic'],
                'refuse_reason' => $list['refuse_reason'],
                'memo' => $list['memo'],
                'sendback_data' => $list['sendback_data'],
                'sendconfirm_data' => $list['sendconfirm_data'],
                'create_time' => date('Y-m-d H:i:s', $list['create_time']),
                'update_time' => date('Y-m-d H:i:s', $list['update_time']),
                'aftersales_address' => $list['aftersales_address'],
                'detail' => $this->formateDetail($list['detail']),

            ];
            $result['list'][] = $_list;
        }
        return $result;
    }

    /**
     * 格式化售后列表的详情字段数据
     * @param  array $detail 售后详情数据
     */
    private function formateDetail($detail): array
    {
        $_detail = [];
        foreach ($detail as $value) {
            $_detail[] = [
                'goods_id' => $value['goods_id'],
                'item_bn' => $value['item_bn'],
                'item_name' => $value['item_name'],
                // 'order_item_type' => $value['order_item_type'],
                'item_pic' => $value['item_pic'],
                'num' => $value['num'],
                'refund_fee' => $value['refund_fee'],
                'refund_point' => $value['refund_point'],
                'aftersales_type' => $value['aftersales_type'],
                'progress' => $value['progress'],
                'aftersales_status' => $value['aftersales_status'],
                'create_time' => date('Y-m-d H:i:s', $value['create_time']),
                'update_time' => date('Y-m-d H:i:s', $value['create_time']),
                'auto_refuse_time' => $value['auto_refuse_time'] ? date('Y-m-d H:i:s', $value['auto_refuse_time']) : '',
            ];
        }
        return $_detail;
    }

    /**
     * 格式化售后详情数据
     * @param  array $detail 售后详情数据
     */
    public function formateAftersalesDetail($detail): array
    {
        $_detail = [
            'aftersales_bn' => $detail['aftersales_bn'],
            'order_id' => $detail['order_id'],
            'aftersales_type' => $detail['aftersales_type'],
            'aftersales_status' => $detail['aftersales_status'],
            'progress' => $detail['progress'],
            'refund_fee' => $detail['refund_fee'],
            'refund_point' => $detail['refund_point'],
            'reason' => $detail['reason'],
            'description' => $detail['description'],
            'evidence_pic' => $detail['evidence_pic'],
            'refuse_reason' => $detail['refuse_reason'],
            'memo' => $detail['memo'],
            'sendback_data' => $detail['sendback_data'],
            'create_time' => date('Y-m-d H:i:s', $detail['create_time']),
            'update_time' => date('Y-m-d H:i:s', $detail['update_time']),
            'aftersales_address' => $detail['aftersales_address'],
            'detail' => $this->formateDetail($detail['detail']),
            'refund_bn' => $this->getAfterRefundBn($detail['company_id'], $detail['aftersales_bn']),
        ];
        return $_detail;
    }

    private function getAfterRefundBn($companyId, $afterSalesBn)
    {
        $aftersalesRefundService = new AftersalesRefundService();
        $info = $aftersalesRefundService->getInfo(['company_id' => $companyId, 'aftersales_bn' => $afterSalesBn]);
        return $info['refund_bn'] ?? '';
    }

    /**
     * 格式化退款单列表
     * @param  array $dataList 订单列表数据
     * @param  int    $page     当前页数
     * @param  int    $pageSize 每页条数
     * @return array
     */
    public function formateRefundList($dataList, int $page, int $pageSize)
    {
        // print_r($dataList);exit;
        $result = $this->handlerListReturnFormat($dataList, $page, $pageSize);
        if (empty($dataList['list'])) {
            return $result;
        }
        $result['list'] = [];
        foreach ($dataList['list'] as $list) {
            $_list = [
                'refund_bn' => $list['refund_bn'],
                'aftersales_bn' => $list['aftersales_bn'],
                'order_id' => $list['order_id'],
                'trade_id' => $list['trade_id'],
                'refund_channel' => $list['refund_channel'],
                'refund_status' => $list['refund_status'],
                'refund_fee' => $list['refund_fee'],
                'refunded_fee' => $list['refunded_fee'],
                'pay_type' => $list['pay_type'],
                'refund_id' => $list['refund_id'],
                'refund_point' => $list['refund_point'],
                'refunded_point' => $list['refunded_point'],
                'refund_success_time' => date('Y-m-d H:i:s', $list['refund_success_time']),
                'create_time' => date('Y-m-d H:i:s', $list['create_time']),
                'update_time' => date('Y-m-d H:i:s', $list['update_time']),

            ];
            $result['list'][] = $_list;
        }
        return $result;
    }

    /**
     * 格式化退款单详情数据
     * @param  array $detail 退款单详情数据
     */
    public function formateRefundDetail($detail): array
    {
        $_detail = [
            'refund_bn' => $detail['refund_bn'],
            'aftersales_bn' => $detail['aftersales_bn'],
            'order_id' => $detail['order_id'],
            'trade_id' => $detail['trade_id'],
            'refund_channel' => $detail['refund_channel'],
            'refund_status' => $detail['refund_status'],
            'return_freight' => $detail['return_freight'],
            'refund_fee' => $detail['refund_fee'],
            'refunded_fee' => $detail['refunded_fee'],
            'pay_type' => $detail['pay_type'],
            'refund_id' => $detail['refund_id'],
            'refund_point' => $detail['refund_point'],
            'refunded_point' => $detail['refunded_point'],
            'refund_success_time' => date('Y-m-d H:i:s', $detail['refund_success_time']),
            'create_time' => date('Y-m-d H:i:s', $detail['create_time']),
            'update_time' => date('Y-m-d H:i:s', $detail['update_time']),
        ];
        if (empty($detail['aftersales_bn'])) {
            $_detail['cancel_reason'] = $this->getOrderCancelReason($detail['company_id'], $detail['order_id']);
        }
        return $_detail;
    }

    private function getOrderCancelReason($companyId, $orderId)
    {
        $cancelOrdersRepository = app('registry')->getManager('default')->getRepository(CancelOrders::class);
        $info = $cancelOrdersRepository->getInfo(['company_id' => $companyId, 'order_id' => $orderId]);
        return $info['cancel_reason'] ?? '';
    }
}
