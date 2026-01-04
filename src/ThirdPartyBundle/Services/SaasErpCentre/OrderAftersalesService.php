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

namespace ThirdPartyBundle\Services\SaasErpCentre;

// use Dingo\Api\Exception\ResourceException;

use OrdersBundle\Services\Orders\NormalOrderService;
// use GoodsBundle\Services\ItemsService;
// use MembersBundle\Services\MemberService;

use AftersalesBundle\Services\AftersalesService;
use AftersalesBundle\Services\AftersalesRefundService;
use OrdersBundle\Services\Orders\AbstractNormalOrder;
use MembersBundle\Services\MemberService;

use DistributionBundle\Services\DistributorAftersalesAddressService;
use OrdersBundle\Traits\GetOrderServiceTrait;

use Exception;

class OrderAftersalesService
{
    use GetOrderServiceTrait;

    /**
     * 生成发给 SaasErp 售后申请单数据
     *
     */
    public function getOrderAfterInfo($companyId, $orderId, $aftersalesBn)
    {
        // 获取售后单
        $aftersalesService = new AftersalesService();
        $afterInfo = $aftersalesService->getAftersales(['aftersales_bn' => $aftersalesBn, 'company_id' => $companyId]);
        app('log')->debug('saaserp trade_after_afterInfo=>:'.var_export($afterInfo, 1)."\n");
        if (!$afterInfo) {
            //此处是否考虑重建售后单 ？ @todo
            throw new Exception("售后单获取失败");
        }

        // 获取订单信息
        // $abstractNormalOrder = new AbstractNormalOrder();
        $return_product_items = [];
        foreach ($afterInfo['detail'] as $v) {
            $return_product_items[] = [
                'price' => bcdiv($v['refund_fee'], 100 * $v['num'], 3),
                'sku_bn' => $v['item_bn'],
                'sku_name' => remove_emoji($v['item_name']),
                'number' => $v['num'],
            ];
            app('log')->debug("\n saaserp trade_after_return_product_items=>:".var_export($return_product_items, 1));
        }

        // 获取会员手机号
        $memberService = new MemberService();
        $user_mobile = $memberService->getMobileByUserId($afterInfo['user_id'], $afterInfo['company_id']);

        // 退货物流信息
        $sendback_data = $afterInfo['sendback_data'];
        $logistics_info = [
            'logistics_company' => $sendback_data['corp_code'] ?? '',
            'logistics_no' => $sendback_data['logi_no'] ?? '',
        ];

        $comment = $afterInfo['description'];
        // 导购端提交的售后单 传给oms的标识
        if ($afterInfo['salesman_id'] ?? 0) {
            $comment = 'SELLER_RECEIVE_BUYER_GOODS';
        }

        // 1 申请中',
        // 2 审核中',
        // 3 接受申请',
        // 4 完成',
        // 5 拒绝',
        // 6 已收货',
        // 7 已质检',
        // 8 补差价',
        // 9 已拒绝退款',
        switch ($afterInfo['aftersales_status']) {
            case 0:
                $status = 1;
                break;
            case 1:
                $status = 3;
                break;
            case 2:
                $status = 4;
                break;
            case 3:
            case 4:
                $status = 5;
                break;
        }

        //组织 售后申请单
        $afterData = [
            'aftersale_id' => $afterInfo['aftersales_bn'],//售后单号
            'tid' => $afterInfo['order_id'],//订单号
            'title' => '',
            'content' => $afterInfo['reason'],
            'messager' => $comment,
            'created' => date('Y-m-d H:i:s', $afterInfo['create_time']),
            'memo' => $afterInfo['aftersales_bn'].'|'.$afterInfo['memo'],
            'status' => $status,
            'buyer_id' => $afterInfo['user_id'],
            'buyer_name' => $user_mobile,
            'aftersale_items' => json_encode($return_product_items),
            'logistics_info' => json_encode($logistics_info),
            'attachment' => $afterInfo['evidence_pic'] ? implode('|', $afterInfo['evidence_pic']) : '',
        ];

        return $afterData;
    }

    /**
     * 更新售后单物流信息到ERP
     *
     */
    public function getAfterLogistics($params)
    {
        $aftersalesService = new AftersalesService();
        $afterInfo = $aftersalesService->getAftersalesDetail($params['company_id'], $params['aftersales_bn']);
        app('log')->debug("\n saaserp ".__FUNCTION__.",".__LINE__.",afterItemInfo=>:".var_export($afterInfo, 1));
        if (!$afterInfo) {
            throw new Exception("售后单详情获取失败");
        }

        if (!$params['sendback_data']) {
            $sendback_data = $afterInfo['sendback_data'];
        } else {
            $sendback_data = $params['sendback_data'];
        }

        if (empty($sendback_data) || !$sendback_data['logi_no'] || !$sendback_data['corp_code']) {
            throw new Exception("未获取到相关物流信息");
        }

        //退货物流信息
        $logistics_info = [
            'logistics_company' => $sendback_data['corp_code'],
            'logistics_no' => $sendback_data['logi_no'],
        ];

        $afterData = [
            'aftersale_id' => $afterInfo['aftersales_bn'],
            'tid' => $afterInfo['order_id'],
            'logistics_info' => json_encode($logistics_info),
        ];

        return $afterData;
    }

    /**
     * 用户撤销仅退款申请后通知 SaasErp
     *
     */
    public function cancelSaasErpRefund($companyId, $orderId, $aftersalesBn)
    {
        $aftersalesService = new AftersalesService();

        // 获取售后单信息
        $afterInfo = $aftersalesService->aftersalesRepository->get(['aftersales_bn' => trim($aftersalesBn), 'company_id' => $companyId]);
        app('log')->debug('saaserp cancelSaasErpRefund_afterInfo=>:'.var_export($afterInfo, 1)."\n");
        if (empty($afterInfo)) {
            throw new Exception("未获取到相关售后信息");
        }

        //获取订单信息
        $orderService = $this->getOrderService('normal');
        $orderData = $orderService->getOrderInfo($companyId, $orderId);
        $orderInfo = $orderData['orderInfo'];
        $tradeInfo = $orderData['tradeInfo'];
        app('log')->debug('saaserp cancelRefund orderData=>:'.var_export($orderData, 1)."\n");
        unset($orderData);

        //获取退款申请单
        $aftersalesRefundService = new AftersalesRefundService();
        $filter = [
            'order_id' => $afterInfo['order_id'],
            'company_id' => $afterInfo['company_id'],
            'aftersales_bn' => $afterInfo['aftersales_bn'],
        ];
        $refundInfo = $aftersalesRefundService->aftersalesRefundRepository->getInfo($filter);
        app('log')->debug('saaserp ancelSaasErpRefund_refundInfo=>:'.var_export($refundInfo, 1)."\n");

        $reason = isset($afterInfo['reason']) ? $afterInfo['reason'].'('.$afterInfo['description'].')' : $refundInfo['refunds_memo'];
        $memo = $reason;

        //组织erp 更新退款申请单
        $refundData = [
            'refund_id' => $refundInfo['refund_bn'],
            'tid' => $orderId,
            'buyer_bank' => '',
            'buyer_id' => $orderInfo['user_id'],
            'buyer_account' => '',
            'buyer_name' => '',
            'refund_fee' => bcdiv($refundInfo['refund_fee'], 100, 2),
            'currency' => $refundInfo['currency'],
            'currency_fee'  => bcdiv($refundInfo['pay_type'] == 'point' ? 0 : $refundInfo['cur_pay_fee'], 100, 2),
            'pay_type' => $refundInfo['pay_type'],
            'payment_tid' => '',
            'payment_type' => '',
            'seller_account' => '',
            't_begin' => date('Y-m-d H:i:s', $refundInfo['create_time']),
            't_sent' => '',
            't_received' => '',
            'refund_type' => 'apply',
            'status' => 'FAIL',
            'memo' => $memo,
            'outer_no' => $tradeInfo['tradeId'],
            // 'memo' => isset($afterInfo['reason']) ? $afterInfo['reason'].'('.$afterInfo['description'].')' : $refundInfo['refunds_memo'],
        ];

        return $refundData;
    }

    public function updateSaasErpAftersalesStatus($companyId, $aftersalesBn)
    {
        // 获取售后单
        $aftersalesService = new AftersalesService();
        $afterInfo = $aftersalesService->getAftersales(['aftersales_bn' => $aftersalesBn, 'company_id' => $companyId]);
        app('log')->debug('saaserp trade_after_afterInfo=>:'.var_export($afterInfo, 1)."\n");
        if (!$afterInfo) {
            throw new Exception("售后单获取失败");
        }

        // 1 申请中',
        // 2 审核中',
        // 3 接受申请',
        // 4 完成',
        // 5 拒绝',
        // 6 已收货',
        // 7 已质检',
        // 8 补差价',
        // 9 已拒绝退款',
        switch ($afterInfo['aftersales_status']) {
            case 0:
                $status = 1;
                break;
            case 1:
                $status = 3;
                break;
            case 2:
                $status = 4;
                break;
            case 3:
            case 4:
                $status = 5;
                break;
        }

        $afterData = [
            'status' => $status,
            'tid' => $afterInfo['order_id'],
            'aftersale_id' => $afterInfo['aftersales_bn'],
        ];

        return $afterData;
    }

    /**
     * 接收 SaasErp 修改售后状态
     * OMS审核中 [status] => 4
     * OMS接受申请 [status] => 4
     * OMS拒绝申请 [status] => 2
     * OMS同意退款 [status] => 1
     *
     */
    public function aftersaleStatusUpdate($params, $status = '1')
    {
        app('log')->debug("\nsaaserp OrderAftersalesService,".__FUNCTION__.",".__LINE__.", params=>:".var_export($params, 1)."\n");
        $aftersalesService = new AftersalesService();

        $afterInfo = $aftersalesService->aftersalesRepository->get(['aftersales_bn' => trim($params['aftersale_id']), 'order_id' => trim($params['order_id'])]);
        app('log')->debug("\nsaaserp OrderAftersalesService,".__FUNCTION__.",".__LINE__.", afterInfo=>:".var_export($afterInfo, 1)."\n");

        if (!$afterInfo) {
            throw new Exception("未获取到售后单信息");
        }

        $afterData = [
            'aftersales_bn' => $afterInfo['aftersales_bn'],
            'refuse_reason' => (isset($params['addon']) && $params['addon'] != 'null') ? trim($params['addon']) : '',
            'company_id' => $afterInfo['company_id'],
        ];
        try {
            $result = [];
            switch ($status) {
                case '4': //ome审核中、接受申请
                    if ($afterInfo['progress'] == '0') {
                        $afterData['is_approved'] = 1;
                        // 退货退款时，处理商家回寄地址
                        $afterData['aftersales_address_id'] = $this->aftersalesAddress($afterInfo);
                        $result = $aftersalesService->review($afterData);
                    }
                    break;

                case '2': //ome拒绝申请
                    $afterData['is_approved'] = 0;
                    $result = $aftersalesService->review($afterData);
                    break;

                case '1': // ome 售后完成  财务同意退款
                    if ($afterInfo['progress'] == '2') {// ome商家确认收到退货
                        $result = $aftersalesService->sendBackConfirm($afterData);
                    }
                    break;
            }
        } catch (\Exception $e) {
            $errorMsg = "saaserp OrderAftersalesService,".__FUNCTION__.",".__LINE__.", Error on line ".$e->getLine()." in ".$e->getFile().": <b>".$e->getMessage()."\n";
            app('log')->debug('saaserp OrderAftersalesService aftersaleStatusUpdate 请求失败:'. $errorMsg);
        }
        return $result;
    }

    /**
     * 退货退款的售后，OMS同意后，取店铺默认的回寄地址，更新售后单
     * @param  array $afterData 售后数据
     */
    private function aftersalesAddress($afterData)
    {
        if ($afterData['aftersales_type'] != 'REFUND_GOODS') {
            return 0;
        }
        $distributorAftersalesAddressService = new DistributorAftersalesAddressService();
        $addressFilter = [
            'company_id' => $afterData['company_id'],
            'distributor_id' => $afterData['distributor_id'],
            'return_type' => 'logistics',
            'is_default' => 1
        ];
        $aftersalesAddressList = $distributorAftersalesAddressService->getDistributorAfterSalesAddress($addressFilter, 1, -1);
        return $aftersalesAddressList['list'][0]['address_id'] ?? 0;
    }
}
