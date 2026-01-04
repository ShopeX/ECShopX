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

namespace SystemLinkBundle\Services\Jushuitan;

use OrdersBundle\Services\Orders\AbstractNormalOrder;
use AftersalesBundle\Services\AftersalesService;
use OrdersBundle\Traits\GetOrderServiceTrait;

use Exception;

class OrderAftersalesService
{
    use GetOrderServiceTrait;

    /**
     * 生成发给聚水潭售后申请单数据
     *
     */
    public function getOrderAfterInfo($companyId, $orderId, $aftersalesBn, $shopId)
    {
        // 获取售后单
        $aftersalesService = new AftersalesService();
        $afterInfo = $aftersalesService->aftersalesRepository->get(['aftersales_bn'=>$aftersalesBn, 'company_id'=>$companyId]);
        app('log')->debug('trade_after_afterInfo=>:'.var_export($afterInfo,1));
        if (!$afterInfo)
        {
            throw new Exception("售后单获取失败");
        }

        $afterItemList = $aftersalesService->aftersalesDetailRepository->getList(['aftersales_bn'=>$afterInfo['aftersales_bn'], 'company_id'=>$afterInfo['company_id']]);

        app('log')->debug('trade_after_afterItemList=>:'.var_export($afterItemList,1));
        if (!$afterItemList)
        {
            throw new Exception("售后单详情获取失败");
        }

        // 获取订单信息
        // $abstractNormalOrder = new AbstractNormalOrder();
        // $orderItemData = $abstractNormalOrder->getOrderItemInfo($companyId, $orderId, $item_id);
        // app('log')->debug('trade_after_orderItemData=>:'.var_export($orderItemData,1));
        // if (!$orderItemData)
        // {
        //     throw new Exception("获取订单详情失败");
        // }

        // if ($orderItemData['delivery_status'] != 'DONE')
        // {
        //     throw new Exception("订单不是发货状态");
        // }

        //组织聚水潭售后申请单
        $type = ''; //售后类型，普通退货，其它，拒收退货,仅退款,投诉,补发,换货
        $item_type = ''; // 可选: 退货，换货，其它，补发
        $good_status = ''; //BUYER_NOT_RECEIVED:买家未收到货,BUYER_RECEIVED:买家已收到货,BUYER_RETURNED_GOODS:买家已退货,SELLER_RECEIVED:卖家已收到退货
        switch ($afterInfo['aftersales_type']) {
            case 'ONLY_REFUND':
                $type = '仅退款';
                $item_type = '其它';
                $good_status = 'BUYER_NOT_RECEIVED';
                break;
            case 'REFUND_GOODS':
                $type = '普通退货';
                $item_type = '退货';
                $good_status = 'BUYER_RECEIVED';
                break;
            case 'EXCHANGING_GOODS':
                $type = '换货';
                $item_type = '换货';
                $good_status = 'BUYER_RECEIVED';
                break;
        }

        $shop_status = ''; //WAIT_SELLER_AGREE:买家已经申请退款，等待卖家同意,WAIT_BUYER_RETURN_GOODS:卖家已经同意退款，等待买家退货,WAIT_SELLER_CONFIRM_GOODS:买家已经退货，等待卖家确认收货,SELLER_REFUSE_BUYER:卖家拒绝退款,CLOSED:退款关闭,SUCCESS:退款成功
        switch ($afterInfo['progress']) {
            case '0':
                $shop_status = 'WAIT_SELLER_AGREE';
                break;
            case '1':
                $shop_status = 'WAIT_BUYER_RETURN_GOODS';
                break;
            case '2':
                $shop_status = 'WAIT_SELLER_CONFIRM_GOODS';
                $good_status = 'BUYER_RETURNED_GOODS';
                break;
            case '3':
                $shop_status = 'SELLER_REFUSE_BUYER';
                break;
            case '4':
                $shop_status = 'SUCCESS';
                $good_status = 'SELLER_RECEIVED';
                break;
            case '5':
                $shop_status = 'SELLER_REFUSE_BUYER';
                break;
            case '6':
                $shop_status = 'SUCCESS';
                $good_status = 'SELLER_RECEIVED';
                break;
            case '7':
                $shop_status = 'CLOSED';
                break;
            case '9':
                $shop_status = 'SUCCESS';
        }

        $afterData = [
            'shop_id' => intval($shopId),
            'outer_as_id' => $afterInfo['aftersales_bn'],
            'so_id' => $afterInfo['order_id'],
            'type' => $type,
            'shop_status' => $shop_status,
            'remark' => $afterInfo['description'],
            'good_status' => $good_status,
            'question_type' => $afterInfo['reason'],
            // 'total_amount' => floatval(bcdiv($orderItemData['total_fee'], 100, 2)),
            'refund' => floatval(bcdiv($afterInfo['refund_fee'], 100, 2)),
            'payment' => 0,
        ];

        if ($afterInfo['sendback_data']) {
            $afterData['logistics_company'] = $afterInfo['sendback_data']['corp_code'] ?? '';
            $afterData['l_id'] = $afterInfo['sendback_data']['logi_no'] ?? '';
        }
        foreach ($afterItemList['list'] as $item) {
            $items[] = [
                'sku_id' => isset($item['item_bn']) ? $item['item_bn'] : $item['item_id'],
                'qty' => $item['num'],
                'amount' => floatval(bcdiv($item['refund_fee'], 100, 2)),
                'type' => $item_type, //可选: 退货，换货，其它，补发
            ];
        }
        

        $afterData['items'] = $items;

        return $afterData;
    }

    /**
     * 修改售后状态
     *
     */
    public function aftersaleStatusUpdate($params, $status='1')
    {
        $aftersalesService = new AftersalesService();

        $afterInfo = $aftersalesService->aftersalesRepository->get(['aftersales_bn'=>trim($params['outer_as_id']), 'order_id'=>trim($params['so_id'])]);
        app('log')->debug('aftersaleStatusUpdate_afterInfo=>:'.var_export($afterInfo,1));

        if (!$afterInfo)
        {
            throw new Exception("未获取到售后单信息");
        }

        $afterData = [
            'aftersales_bn' => $afterInfo['aftersales_bn'],
            'refuse_reason' => (isset($params['remark']) && $params['remark']!='null') ? trim($params['remark']): '',
            'company_id' => $afterInfo['company_id'],
        ];

        $result = [];
        switch($status)
        {
            case '3': // 同意售后申请
                $afterData['is_approved'] = 1;
                $result = $aftersalesService->review($afterData);
                break;

            case '5': // 拒绝售后
                $afterData['is_approved'] = 0;
                $result = $aftersalesService->review($afterData);
                break;

            case '4': // 确认收货
                $result = $aftersalesService->confirmReceipt($afterData);
                break;

            case '6': // 取消收货
                $result = $aftersalesService->cancelReceipt($afterData);
                break;
        }

        app('log')->debug('aftersaleStatusUpdate_result=>:'.var_export($result,1));
        return $result;
    }
}
