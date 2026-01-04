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

namespace SystemLinkBundle\Services\ShopexErp;

use Dingo\Api\Exception\ResourceException;

use OrdersBundle\Services\Orders\NormalOrderService;
use OrdersBundle\Services\Orders\AbstractNormalOrder;

use AftersalesBundle\Services\AftersalesService;
use AftersalesBundle\Services\AftersalesRefundService;

use OrdersBundle\Traits\GetOrderServiceTrait;

use Exception;
// use Dingo\Api\Exception\ResourceException;
use SystemLinkBundle\Events\TradeAftersalesEvent;
use SystemLinkBundle\Events\TradeRefundEvent;

class OrderAftersalesService
{
    use GetOrderServiceTrait;

    /**
     * 生成发给OME售后申请单数据
     *
     */
    public function getOrderAfterInfo($companyId, $orderId, $aftersales_bn)
    {
        // 获取售后单
        $aftersalesService = new AftersalesService();
        $afterInfo = $aftersalesService->getAftersales(['aftersales_bn' => $aftersales_bn, 'company_id' => $companyId]);
        app('log')->debug('trade_after_afterInfo=>:'.var_export($afterInfo, 1));
        if (!$afterInfo) {
            //此处是否考虑重建售后单 ？ @todo
            throw new Exception("售后单获取失败");
        }

        //$abstractNormalOrder = new AbstractNormalOrder();
        $return_product_items = [];
        foreach ($afterInfo['detail'] as $v) {
            /*
            if(isset($v['sub_order_id']) && $v['sub_order_id']){
                $orderItemData = $abstractNormalOrder->getOrderItemInfoById($v['sub_order_id']); // 获取订单商品单个明细
            } else {
                $orderItemData = $abstractNormalOrder->getOrderItemInfo($companyId, $orderId, $v['item_id']); // 获取订单商品单个明细
            }
            */
            $return_product_items[] = [
                'refund_money' => bcdiv($v['refund_fee'], 100, 2),
                'price' => bcdiv($v['refund_fee'], 100 * $v['num'], 3),
                'bn' => $v['item_bn'],
                'name' => remove_emoji($v['item_name']),
                'num' => $v['num'],
                'bbc_item_id' => $v['sub_order_id'],
            ];
        }
        // if ($afterInfo['aftersales_type'] == 'EXCHANGING_GOODS') {
        //     $return_type = 'change';
        // }else if ($afterInfo['aftersales_type'] == 'REFUND_GOODS') {
        //     $return_type = 'return';
        // }
        //组织ome 售后申请单
        $afterData = [
            'comment' => '',
            'node_version' => '',
            'status' => '1',
            'return_product_items' => json_encode($return_product_items),
            'return_bn' => $afterInfo['aftersales_bn'],
            'add_time' => $afterInfo['create_time'],
            'title' => $afterInfo['reason'],
            'content' => $afterInfo['description'],
            'order_bn' => $afterInfo['order_id'],
            'member_id' => $afterInfo['user_id'],
            'attachment' => $afterInfo['evidence_pic'] ? implode(',', $afterInfo['evidence_pic']) : '',
            // 'return_type' => $return_type ?? ''
        ];

        return $afterData;
    }

    /**
     * 更新售后单物流信息到OME
     *
     */
    public function getAfterLogistics($params)
    {
        $aftersalesService = new AftersalesService();
        $afterInfo = $aftersalesService->getAftersales(['aftersales_bn' => $params['aftersales_bn'], 'company_id' => $params['company_id']]);
        app('log')->debug('OrderAftersalesService_getAfterLogistics_afterInfo=>:'.var_export($afterInfo, 1));

        if (!$afterInfo) {
            throw new Exception("售后单详情获取失败");
        }

        $sendback_data = $afterInfo['sendback_data'];

        if (empty($sendback_data) || !$sendback_data['logi_no'] || !$sendback_data['corp_code']) {
            throw new Exception("未获取到相关物流信息");
        }

        if ($sendback_data['logi_no'] == 'YD') {
            $sendback_data['logi_no'] = 'YUNDA'; // 韵达快递特殊处理
        }

        //退货物流信息
        $logistics_info = [
            'logi_no' => $sendback_data['logi_no'],
            'logi_company' => $sendback_data['corp_code']
        ];

        $afterData = [
            'node_version' => '3.0',
            'logistics_info' => json_encode($logistics_info),
            'order_bn' => $afterInfo['order_id'],
            'return_bn' => $afterInfo['aftersales_bn'],
        ];

        return $afterData;
    }

    /**
     * 用户撤销仅退款申请后通知OME
     *
     */
    public function cancelOmeRefund($companyId, $orderId, $aftersalesBn)
    {
        $aftersalesService = new AftersalesService();

        // 获取售后单信息
        $afterInfo = $aftersalesService->aftersalesRepository->get(['aftersales_bn' => trim($aftersalesBn), 'company_id' => $companyId]);
        app('log')->debug('ome_cancelOmeRefund_afterInfo=>:'.var_export($afterInfo, 1));

        if (empty($afterInfo)) {
            throw new Exception("未获取到相关售后信息");
        }

        //获取订单信息
        $orderService = $this->getOrderService('normal');
        // 获取订单信息
        $orderData = $orderService->getOrderInfo($companyId, $orderId);
        app('log')->debug('ome_orderData=>:'.var_export($orderData, 1));

        //获取退款申请单
        $aftersalesRefundService = new AftersalesRefundService();

        $filter = [
            'order_id' => $afterInfo['order_id'],
            'company_id' => $afterInfo['company_id'],
            'aftersales_bn' => $aftersalesBn,
        ];

        $refundInfo = $aftersalesRefundService->aftersalesRefundRepository->getInfo($filter);
        app('log')->debug('ome_cancelOmeRefund_refundInfo=>:'.var_export($refundInfo, 1));

        //组织ome 确认退款单
        $refundData = [
            'refund_type' => 'apply',
            'status' => '3',
            'node_version' => '2.0',
            't_received' => '', //买家确认收款时间
            'paymethod' => '',
            'pay_account' => '',
            'memo' => isset($refundInfo['refunds_memo']) ? $refundInfo['refunds_memo'] : '买家撤销申请',
            'currency' => $refundInfo['currency'],
            'payment' => '',
            'bank' => '',
            'date' => date('Y-m-d H:i:s', time()), //当前时间
            't_sent' => date('Y-m-d H:i:s', $refundInfo['refund_success_time']),  //商家发款时间
            't_ready' => date('Y-m-d H:i:s', $refundInfo['create_time']), //创建退款单时间
            'trade_no' => isset($orderData['tradeInfo']['transactionId']) ? $orderData['tradeInfo']['transactionId'] : $orderData['tradeInfo']['tradeId'],
            // 'pay_type' => $refundInfo['pay_type'],
            'pay_type' => 'online',
            'account' => '',
            'cur_money' => bcdiv($refundInfo['refund_fee'], 100, 2),
            'refund_bn' => $refundInfo['refund_bn'],
            'order_bn' => $refundInfo['order_id'],
            'money' => bcdiv($refundInfo['refund_fee'], 100, 2),
        ];

        return $refundData;
    }

    /**
     * OME修改售后状态
     *
     */
    public function aftersaleStatusUpdate($params, $status = '1')
    {
        $aftersalesService = new AftersalesService();

        $afterInfo = $aftersalesService->aftersalesRepository->get(['aftersales_bn' => trim($params['aftersale_id']), 'order_id' => trim($params['tid'])]);
        app('log')->debug('aftersaleStatusUpdate_afterInfo=>:'.var_export($afterInfo, 1));

        if (!$afterInfo) {
            throw new Exception("未获取到售后单信息");
        }

        $afterData = [
            'aftersales_bn' => $afterInfo['aftersales_bn'],
            'refuse_reason' => (isset($params['addon']) && $params['addon'] != 'null') ? trim($params['addon']) : '',
            'company_id' => $afterInfo['company_id'],
            // 'refund_fee' => ,
        ];

        try {
            $result = [];
            switch ($status) {
                case '2': // oms点击审核中
                    break;
                case '3': //ome同意售后申请
                    $afterData['is_approved'] = 1;
                    $result = $aftersalesService->review($afterData);
                    break;

                case '5': //ome拒绝售后
                    $afterData['is_approved'] = 0;
                    $result = $aftersalesService->review($afterData);
                    break;

                case '4': // ome确认收货
                    $result = $aftersalesService->sendBackConfirm($afterData);
                    // 暂不做操作
                    break;
            }
        } catch (\Exception $e) {
            app('log')->debug('OME请求失败:'. $e->getMessage());
        }
        app('log')->debug('aftersaleStatusUpdate_result=>:'.var_export($result, 1));
        return $result;
    }

    /**
    * oms推送售后单提交售后申请
    *
    * @param array $data 创建售后申请提交的参数
    */
    public function omsSendAftersalesCreate($data)
    {
        $aftersales_bn = $data['aftersales_bn'];
        unset($data['aftersales_bn']);
        $aftersalesService = new AftersalesService();
        // 检查是否可以申请售后
        $aftersalesService->__checkApply($data);
        $filter = [
            'company_id' => $data['company_id'],
            'order_id' => $data['order_id'],
            'user_id' => $data['user_id'],
        ];
        $normalOrderService = new NormalOrderService();
        $orderInfo = $normalOrderService->getSimpleOrderInfo($filter);

        $aftersales_data = [
            'aftersales_bn' => $aftersales_bn,
            'order_id' => $data['order_id'],
            'company_id' => $data['company_id'],
            'user_id' => $data['user_id'],
            'shop_id' => $orderInfo['shop_id'],
            'distributor_id' => $orderInfo['distributor_id'],
            'aftersales_type' => $data['aftersales_type'],
            'aftersales_status' => 0,
            'progress' => 0,
            'reason' => $data['reason'],
            'description' => $data['description'] ?? '',
            'evidence_pic' => $data['evidence_pic'] ?? [],
        ];
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 创建售后单
            $total_refund_fee = 0;
            foreach ($data['detail'] as $v) {
                $suborder_filter = [
                    'company_id' => $data['company_id'],
                    'user_id' => $data['user_id'],
                    'order_id' => $data['order_id'],
                    'id' => $v['id'],
                ];
                $subOrderInfo = $normalOrderService->getSimpleSubOrderInfo($suborder_filter);
                $applied_num = $aftersalesService->getAppliedNum($data['company_id'], $data['order_id'], $v['id']); // 已申请数量
                $applied_refund_fee = $aftersalesService->getAppliedTotalRefundFee($data['company_id'], $data['order_id'], $v['id']); // 已申请退款总金额
                if ($v['num'] == $subOrderInfo['num']) { // 子订单 全部 退货
                    $refund_fee = $subOrderInfo['total_fee'] - $subOrderInfo['cash_discount'];
                } else { // 子订单 部分 退货
                    $left_num = $subOrderInfo['num'] - $applied_num - $v['num'];
                    if ($left_num == 0) { // 申请的是本明细剩余的所有数量
                        $refund_fee = $subOrderInfo['total_fee'] - $subOrderInfo['cash_discount'] - $applied_refund_fee;
                    } elseif ($left_num > 0) { // 还有没申请的商品的时候  通过除法来计算退款金额，向下取整
                        $refund_fee = floor(bcmul(bcdiv(($subOrderInfo['total_fee'] - $subOrderInfo['cash_discount']), $subOrderInfo['num']), $v['num']));
                    } else {
                        throw new ResourceException('申请售后单数据异常');
                    }
                }
                if ($v['refund_fee'] > $refund_fee) {
                    throw new Exception("退款金额不正确");
                }
                $total_refund_fee += $v['refund_fee'];
                $aftersales_detail_data = [
                    'company_id' => $data['company_id'],
                    'user_id' => $data['user_id'],
                    'aftersales_bn' => $aftersales_bn,
                    'order_id' => $data['order_id'],
                    'sub_order_id' => $v['id'],
                    'item_id' => $subOrderInfo['item_id'],
                    'item_bn' => $subOrderInfo['item_bn'],
                    'item_pic' => $subOrderInfo['pic'],
                    'refund_fee' => $v['refund_fee'],
                    'item_name' => $subOrderInfo['item_name'],
                    'order_item_type' => $subOrderInfo['order_item_type'],
                    'num' => $v['num'],
                    'aftersales_type' => $data['aftersales_type'],
                    'progress' => 0,
                    'aftersales_status' => 0,
                ];
                if ($subOrderInfo['item_spec_desc']) {
                    $aftersales_detail_data['item_name'] = $subOrderInfo['item_name'] . '(' . $subOrderInfo['item_spec_desc'] . ')';
                }
                // 创建售后明细
                $aftersales_detail = $aftersalesService->aftersalesDetailRepository->create($aftersales_detail_data);
            }
            $aftersales_data['refund_fee'] = $total_refund_fee;
            // 创建售后主单据
            $aftersales = $aftersalesService->aftersalesRepository->create($aftersales_data);

            // 创建售后退款单
            $aftersalesRefundService = new AftersalesRefundService();
            $refundData = [
                'company_id' => $aftersales_data['company_id'],
                'user_id' => $aftersales_data['user_id'],
                'aftersales_bn' => $aftersales_data['aftersales_bn'],
                'order_id' => $aftersales_data['order_id'],
                'trade_id' => $aftersales_data['order_id'], // 爱茉莉一个订单只有一个支付单，不再查了
                'shop_id' => $aftersales_data['shop_id'] ?? 0,
                'distributor_id' => $aftersales_data['distributor_id'] ?? 0,
                'refund_type' => 0, // 0 售后申请退款
                'refund_channel' => 'original', // 默认原路退回
                'refund_fee' => $total_refund_fee,
                'return_freight' => 0, // 0 不退运费
                'pay_type' => $orderInfo['pay_type'],
            ];
            $refund = $aftersalesRefundService->createAftersalesRefund($refundData);

            if ($orderInfo['order_status'] != 'DONE') {
                $normalOrderService->confirmReceipt($filter);
            }

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            app('log')->debug('omsAddAftersale_result=>:'.$e->getMessage());
            return false;
        }

        $date = date('Ymd');
        $redisKey = 'OrderPayStatistics:normal:' . $data['company_id'] . ':' . $date;
        app('redis')->hincrby($redisKey, 'orderAftersales', 1);
        if (isset($aftersales_data['distributor_id'])) {
            app('redis')->hincrby($redisKey, $aftersales_data['distributor_id'] . '_orderAftersales', 1);
        }

        //联通OME售后申请埋点
        // if ($data['aftersales_type'] == 'REFUND_GOODS' || $data['aftersales_type'] == 'EXCHANGING_GOODS') {
        //     event(new TradeAftersalesEvent($aftersales)); // 退款退货 或换货
        // } else {
        //     event(new TradeRefundEvent($refund)); // 售后仅退款
        // }
        return $aftersales;
    }
}
