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

use GoodsBundle\Services\ItemsService;
use MembersBundle\Services\MemberService;

use OrdersBundle\Traits\GetOrderServiceTrait;
use ThirdPartyBundle\Services\SaasErpCentre\Request as SaasErpRequest;
use OrdersBundle\Entities\NormalOrdersItems;

class OrderService
{
    const DADA_SHIPPING_TYPE = 'STORE_TONGCHEN_EXPRESS';//同城配送
    
    use GetOrderServiceTrait;

    public function __construct()
    {
    }

    /**
     * 订单结构体
     *
     */
    public function getOrderStruct($companyId, $orderId, $sourceType = 'normal')
    {
        $orderService = $this->getOrderService($sourceType);

        // 获取订单信息
        $orderData = $orderService->getOrderInfo($companyId, $orderId);

        if (!$orderData) {
            app('log')->debug("saaserp ".__FUNCTION__.",".__LINE__.", companyId===>: ".$companyId.', orderId===>:'.$orderId."\n");
            return false;
        }

        $orderInfo = $orderData['orderInfo'];
        $tradeInfo = $orderData['tradeInfo'];
        $distributor = $orderData['distributor'] ?? [];
        unset($orderData);

        $key = 'sendOmsSetting:'. $companyId;
        $setting = app('redis')->connection('companys')->get($key);
        $setting = json_decode($setting, 1);
        // 自提订单暂不推送
        if ($orderInfo['receipt_type'] == 'ziti' && !($setting['ziti_send_oms'] ?? false)) {
            app('log')->debug("saaserp ".__FUNCTION__.",".__LINE__.", receipt_type===>: ".$orderInfo['receipt_type']."\n");
            return false;
        }

        if (in_array($orderInfo['order_status'], ['NOTPAY'])) {
            app('log')->debug("saaserp ".__FUNCTION__.",".__LINE__.", order_status===>: ".var_export($orderInfo, true)."\n");
            return false;
        }

        // 获取买家信息
        $member_info = $this->__formatMemberInfo($orderInfo);

        $status = '';
        $pay_status = '';
        switch ($orderInfo['order_status']) {
            case 'DONE':
                $status = 'TRADE_FINISHED';
                $pay_status = 'PAY_FINISH';
                break;
            case 'NOTPAY':
                $status = 'TRADE_ACTIVE';
                $pay_status = 'PAY_NO';
                $tradeInfo = [];
                break;
            case 'PAYED':
                $status = 'TRADE_ACTIVE';
                $pay_status = 'PAY_FINISH';
                break;
            case 'CANCEL':
                $status = 'TRADE_CLOSED';
                $pay_status = 'REFUND_ALL';
                break;
        }

        $ship_status = '';
        switch ($orderInfo['delivery_status']) {
            case 'DONE':
                $ship_status = 'SHIP_FINISH';
                break;
            case 'PENDING':
                $ship_status = 'SHIP_NO';
                break;
            case 'PARTAIL':
                $ship_status = 'SHIP_PART';
                break;
        }

        if ($orderInfo['receipt_type'] == 'ziti' && $orderInfo['ziti_status'] == 'DONE') {
            $ship_status = 'SHIP_FINISH';
        }

        if ($orderInfo['receipt_type'] == 'dada' && $orderInfo['order_status'] == 'DONE') {
            $ship_status = 'SHIP_FINISH';
        }

        $payment_type = [
            'amorepay' => '微信支付(amorepay)',
            'wxpay' => '微信支付',
            'wxpayh5' => '微信支付',
            'wxpayjs' => '微信支付',
            'wxpayapp' => '微信支付',
            'wxpaypos' => '微信条码支付',
            'wxpaypc' => '微信PC支付',
            'pos' => '刷卡',
            'point' => '积分',
            'dhpoint' => '积分',
            'deposit' => '预存款支付',
            'alipay' => '支付宝支付',
            'alipayh5' => '支付宝支付',
            'alipayapp' => '支付宝支付',
            'alipaypos' => '支付宝条码支付',
            'alipaymini' => '支付宝小程序',
            'hfpay' => '汇付支付',
        ];

        // 优惠信息
        $promotion_details = $this->__getPmtDetail($orderInfo);

        //订单明细
        $orders = $this->___formatOrder($orderInfo, $tradeInfo, $status, $distributor);

        // 获取支付列表信息
        $payment_lists = $this->__formatPaymentLists($tradeInfo);

        //获取订单发票
        $taxInfo = $this->__getTaxInfo($orderInfo);

        //包装信息
        $pack = (isset($orderInfo['pack']) && $orderInfo['pack']) ? json_decode($orderInfo['pack'], 1) : 0;
        $packDesc = '';
        if ($pack) {
            $packDesc = isset($pack['packDes']) ? $pack['packDes'] : 0;
        }

        $erpOrderStruct = [
            'tid' => $orderInfo['order_id'],
            'lastmodify' => date('Y-m-d H:i:s', $orderInfo['update_time']),
            'promotion_details' => json_encode($promotion_details),
            'total_weight' => '0.000',
            'created' => date('Y-m-d H:i:s', $orderInfo['create_time']),
            'payment_lists' => json_encode($payment_lists),
            'status' => $status,
            'pay_status' => $pay_status,
            'ship_status' => $ship_status,
            'payed_fee' => bcdiv($tradeInfo['payFee'] ?? 0, 100, 2),
            'total_goods_fee' => bcdiv($orderInfo['item_fee'], 100, 2),
            'total_trade_fee' => bcdiv($orderInfo['total_fee'], 100, 2),
            'currency' => 'CNY',
            'currency_rate' => $orderInfo['fee_rate'],
            'buyer_obtain_point_fee' => '0.000',
            'is_protect' => 'false',
            'protect_fee' => '0.000',
            'discount_fee' => 0,
            'is_cod' => 'false',
            'payment_tid' => $tradeInfo['payType'] ?? $orderInfo['pay_type'],
            'payment_type' => $payment_type[$tradeInfo['payType'] ?? $orderInfo['pay_type']] ?? '',
            'orders_number' => 1,// 子单商品总数量 暂定
            'buyer_uname' => $member_info['uname'],
            'buyer_name' => isset($member_info['name']) ? trim($member_info['name']) : $member_info['uname'],
            'buyer_mobile' => $member_info['mobile'],
            'buyer_phone' => $member_info['mobile'],
            'buyer_email' => "",
            'buyer_state' => "",
            'receiver_name' => $orderInfo['receiver_name'],
            'receiver_phone' => "",
            'receiver_mobile' => $orderInfo['receiver_mobile'],
            'receiver_state' => $orderInfo['receiver_state'],
            'receiver_city' => $orderInfo['receiver_city'],
            'receiver_district' => $orderInfo['receiver_district'],
            'receiver_address' => $orderInfo['receiver_address'],
            'receiver_zip' => $orderInfo['receiver_zip'],
            'orders_discount_fee' => bcdiv($orderInfo['discount_fee'] + ($orderInfo['point_fee'] ?? 0), 100, 2),
            'orders' => json_encode($orders),
            'goods_discount_fee' => '0.00',
            'shipping_tid' => '0',
            'shipping_type' => $orderInfo['delivery_corp'],
            'shipping_fee' => bcdiv($orderInfo['freight_fee'], 100, 2),
            'has_invoice' => $taxInfo['is_tax'],
            'invoice_title' => $taxInfo['title'],
            'invoice_fee' => $taxInfo['cost_tax'],
            'pay_cost' => '0.00',
            'buyer_memo' => $orderInfo['remark'],
            'trade_memo' => $packDesc,
        ];

        if ($distributor) {
            $erpOrderStruct['o2o_info'] = json_encode([
                'o2o_store_bn' => $distributor['shop_code'],
                'o2o_store_name' => $distributor['name'],
            ]);
        }

        if ($orderInfo['receipt_type'] == 'ziti') {
            $erpOrderStruct['receiver_name'] = '';
            $erpOrderStruct['receiver_mobile'] = '';
            $erpOrderStruct['receiver_state'] = '';
            $erpOrderStruct['receiver_city'] = '';
            $erpOrderStruct['receiver_district'] = '';
            $erpOrderStruct['receiver_address'] = '';
            $erpOrderStruct['receiver_zip'] = '';
            $erpOrderStruct['delivery_corp'] = '';
            $erpOrderStruct['shipping_type'] = 'STORE_SELF_FETCH';
        }

        if ($orderInfo['receipt_type'] == 'dada') {
            $erpOrderStruct['shipping_type'] = self::DADA_SHIPPING_TYPE;
        }

        if (($tradeInfo['payType'] ?? '') == 'dhpoint') {
            $erpOrderStruct['total_trade_fee'] = bcdiv(($orderInfo['item_fee'] + $orderInfo['freight_fee']), 100, 2);
            $erpOrderStruct['payed_fee'] = $erpOrderStruct['total_trade_fee'];
        }
        $allow_params = array('rights_level','lastmodify','payment_lists','promotion_details','total_weight','buyer_name','currency_rate','app_id','shipping_type','receiver_address','has_invoice','receiver_district','from_type','callback_type','protect_fee','receiver_phone','to_node_id','order_source','logistics_no','pay_cost','buyer_uname','timestamp','_id','tid','receiver_mobile','goods_discount_fee','orders_number','invoice_fee','discount_fee','pay_status','buyer_obtain_point_fee','payment_type','v','real_time','shipping_fee','refresh_time','is_cod','msg_id','currency','node_type','pay_time','payment_tid','orders','receiver_city','channel_ver','orders_discount_fee','format','buyer_memo','from_node_id','shipping_tid','method','channel','status','total_trade_fee','buyer_state','receiver_zip','callback_type_id','to_type','node_id','total_goods_fee','date','buyer_mobile','task','created','ship_status','payed_fee','is_protect','receiver_state','receiver_name','consign_time','step_trade_status','trade_memo','invoice_desc   ','invoice_title','trade_type','buyer_email','cod_status','step_paid_fee','modified','end_time', 'service_order_objects', 'buyer_phone', 'service_orders', 'o2o_info');

        foreach ($erpOrderStruct as $k => $v) {
            if (!in_array($k, $allow_params)) {
                unset($erpOrderStruct[$k]);
            }
        }

        $this->__fixPayed($erpOrderStruct);

        app('log')->debug("saaserp ".__FUNCTION__.",".__LINE__.", OrderStruct===>:".json_encode($erpOrderStruct, 256)."\n");
        return $erpOrderStruct;
    }

    /**
     * 处理实付金额和订单金额不一致的问题
     */
    private function __fixPayed(&$erpOrderStruct)
    {
        //如果实付金额 > 订单金额，实付金额改成 = 订单金额
        if (floatval($erpOrderStruct['payed_fee']) > floatval($erpOrderStruct['total_trade_fee'])) {
            $erpOrderStruct['payed_fee'] = $erpOrderStruct['total_trade_fee'];
        }
    }

    /**
     * 获取发票信息
     */
    private function __getTaxInfo($orderInfo)
    {
        app('log')->debug('saaserp getTaxInfo:'.json_encode($orderInfo['invoice']));
        $taxInfo = [
            'title' => isset($orderInfo['invoice']['content']) ? trim($orderInfo['invoice']['content']) : '',
            'is_tax' => isset($orderInfo['invoice']['content']) ? 1 : 0 ,
            'cost_tax' => '0.00',
        ];
        app('log')->debug('saaserp getTaxInfo_result:'.json_encode($taxInfo));
        return $taxInfo;
    }

    /**
     * 组织买家信息 转换成 SaasErp 结构
     */
    private function __formatMemberInfo($orderInfo)
    {
        if ($orderInfo['user_id'] == 0) {
            return [
                'uname' => '',
                'tel' => '',
                'name' => '匿名用户',
                'mobile' => '',
            ];
        }

        $memberService = new MemberService();

        $memberInfo = $memberService->getMemberInfo(['user_id' => $orderInfo['user_id'], 'company_id' => $orderInfo['company_id']]);

        if (!$memberInfo) {
            return false;
        }

        $member_info = [
            'uname' => $memberInfo['mobile'],
            'tel' => '',
            'name' => $memberInfo['username'],
            'mobile' => $memberInfo['mobile'],
        ];

        return $member_info;
    }

    /**
     * 组织收货地址信息 转换成 SaasErp 结构
     */
    private function __formatConsignee($orderInfo)
    {
        $consignee = [
            'r_time' => $orderInfo['delivery_time'],
            'email' => '',
            'name' => remove_emoji($orderInfo['receiver_name']),
            'zip' => $orderInfo['receiver_zip'],
            'area_state' => $orderInfo['receiver_state'],
            'telephone' => '',
            'mobile' => $orderInfo['receiver_mobile'],
            'area_district' => $orderInfo['receiver_district'],
            'area_city' => $orderInfo['receiver_city'],
            'addr' => $orderInfo['receiver_address'],
        ];

        return $consignee;
    }

    /**
     * 组织支付列表信息 转换成 SaasErp 结构
     */
    private function __formatPaymentLists($tradeInfo)
    {
        if (!$tradeInfo) {
            return [];
        }

        $pay_type = [
            'amorepay' => '微信支付(amorepay)',
            'wxpay' => '微信支付',
            'wxpayh5' => '微信支付',
            'wxpayjs' => '微信支付',
            'wxpayapp' => '微信支付',
            'wxpaypos' => '微信条码支付',
            'wxpaypc' => '微信PC支付',
            'pos' => '刷卡',
            'point' => '积分',
            'dhpoint' => '积分',
            'deposit' => '预存款支付',
            'alipay' => '支付宝支付',
            'alipayh5' => '支付宝支付',
            'alipayapp' => '支付宝支付',
            'alipaypos' => '支付宝条码支付',
            'alipaymini' => '支付宝小程序',
            'hfpay' => '汇付支付',
        ];

        $payment_lists['payment_list'][] = [
            'tid' => $tradeInfo['orderId'],
            'payment_id' => $tradeInfo['tradeId'],
            'seller_bank' => $pay_type[$tradeInfo['payType']],
            'seller_account' => $tradeInfo['openId'],
            'buyer_account' => $tradeInfo['mchId'],
            'currency' => 'CNY',
            'paycost' => '0.000',
            'pay_type' => 'online',
            'payment_name' => $pay_type[$tradeInfo['payType']],
            'payment_code' => $tradeInfo['payType'],
            't_begin' => date('Y-m-d H:i:s', $tradeInfo['timeStart']),
            't_end' => date('Y-m-d H:i:s', $tradeInfo['timeExpire']),
            'status' => 'SUCC',
            'memo' => '',
            'outer_no' => $tradeInfo['transactionId'],
            'pay_fee' => bcdiv($tradeInfo['payType'] == 'point' ? 0 : $tradeInfo['payFee'], 100, 2),
            'currency_fee' => !empty($tradeInfo['curPayFee']) ? bcdiv($tradeInfo['payType'] == 'point' ? 0 : $tradeInfo['curPayFee'], 100, 2) : '',
        ];

        return $payment_lists;
    }

    /**
     * 组织支付单信息 转换成 SaasErp 结构
     */
    private function __formatPayments($payments)
    {
        if (!$payments || $payments['tradeState'] != 'SUCCESS') {
            return false;
        }

        $pay_type = [
            'amorepay' => '微信支付(amorepay)',
            'wxpay' => '微信支付',
            'deposit' => '预存款支付',
            'pos' => '刷卡',
            'point' => '积分',
            'dhpoint' => '积分',
        ];

        $order_payments[] = [
            'trade_no' => $payments['tradeId'],
            'account' => trim($payments['body']),
            'pay_account' => $payments['mchId'],
            'paymethod' => $pay_type[$payments['payType']],
            'money' => bcdiv($payments['payFee'], 100, 2),
            'memo' => trim($payments['detail']),
            'paycost' => '0.00',
            'bank' => $payments['bankType'],
        ];

        return $order_payments;
    }

    /**
     * 组织orders数据 SaasErp 单拉数据使用
     */
    private function ___formatOrder($orderInfo, $tradeInfo, $status, $distributor)
    {
        $order_objects = [];

        $itemsService = new ItemsService();

        foreach ($orderInfo['items'] as $key => $value) {
            $order_item['item'][0] = [
                'iid' => $value['item_id'],
                'bn' => $value['item_bn'],
                'price' => bcdiv($value['price'], 100, 2),   //原价
                'name' => remove_emoji($value['item_name']),
                'num' => $value['num'],
                'total_item_fee' => bcdiv($value['item_fee'], 100, 2), //商品总额
                'sendnum' => '0',
                'item_type' => $value['order_item_type'] == 'gift' ? 'gift' : 'product', //固定product
                'sale_price' => bcdiv($value['item_fee'], 100, 2),  //商品销售价
                'discount_fee' => '0.00', //商品优惠金额
                'part_mjz_discount' => bcdiv($value['discount_fee'] + ($value['point_fee'] ?? 0), 100, 2), //优惠分摊
                'score' => $value['item_point'],
                'item_status' => 'normal',
                'divide_order_fee' => bcdiv($value['total_fee'], 100, 2), //实付金额
            ];
            $order_objects['order'][] = [
                'iid' => $value['item_id'],
                'title' => remove_emoji($value['item_name']),
                'bn' => $value['item_bn'],
                'orders_bn' => $value['item_bn'],
                'items_num' => $value['num'],
                'total_order_fee' => bcdiv($value['total_fee'], 100, 2),
                'oid' => $value['order_id'],
                'status' => $status,
                'sale_price' => bcdiv($value['item_fee'], 100, 2),
                'discount_fee' => bcdiv($value['discount_fee'] + ($value['point_fee'] ?? 0), 100, 2),
                'type' => $value['order_item_type']=='gift' ? 'gift' : 'goods',
                'store_code' => $distributor['shop_code'] ?? null,
                'order_items' => $order_item,
                'part_mjz_discount' => bcdiv($value['discount_fee'] + ($value['point_fee'] ?? 0), 100, 2), //优惠分摊
                'divide_order_fee'=> bcdiv($value['total_fee'], 100, 2), //实付金额
            ];
        }

        return $order_objects;
    }

    /**
     * SaasErp 获取订单优惠
     *
     */
    public function __getPmtDetail($orderInfo)
    {
        $pmtDetail = [];
        if (isset($orderInfo['discount_info']) && $orderInfo['discount_info']) {
            foreach ((array)$orderInfo['discount_info'] as $key => $val) {
                if (!$val) {
                    continue;
                }
                $pmtValue = [
                    'pmt_amount' => bcdiv($val['discount_fee'], 100, 2),
                    'pmt_id' => $orderInfo['order_id'],
                    'pmt_describe' => $val['rule'],
                ];
                $pmtDetail[] = $pmtValue;
            }
        }
        return $pmtDetail;
    }

    /**
    * SaasErp 更新订单状态
    */
    public function updateOrderStatus($companyId, $orderId, $sourceType = 'normal')
    {
        $orderService = $this->getOrderService($sourceType);

        // 获取订单信息
        $orderData = $orderService->getOrderInfo($companyId, $orderId);

        if (!$orderData) {
            return false;
        }

        $orderInfo = $orderData['orderInfo'];
        $tradeInfo = $orderData['tradeInfo'];
        $distributor = $orderData['distributor'] ?? [];
        unset($orderData);

        $key = 'sendOmsSetting:'. $companyId;
        $setting = app('redis')->connection('companys')->get($key);
        $setting = json_decode($setting, 1);
        // 自提订单暂不推送
        if ($orderInfo['receipt_type'] == 'ziti' && !($setting['ziti_send_oms'] ?? false)) {
            return false;
        }

        if (in_array($orderInfo['order_status'], ['NOTPAY', 'WAIT_BUYER_CONFIRM'])) {
            return false;
        }

        // 获取买家信息
        $member_info = $this->__formatMemberInfo($orderInfo);

        $status = '';
        $pay_status = '';
        switch ($orderInfo['order_status']) {
            case 'DONE':
                $status = 'TRADE_FINISHED';
                $pay_status = 'PAY_FINISH';
                break;
            case 'NOTPAY':
                $status = 'TRADE_ACTIVE';
                $pay_status = 'PAY_NO';
                break;
            case 'PAYED':
                $status = 'TRADE_ACTIVE';
                $pay_status = 'PAY_FINISH';
                break;
            case 'CANCEL':
                $status = 'TRADE_CLOSED';
                $pay_status = 'REFUND_ALL';
                break;
        }

        $ship_status = '';
        switch ($orderInfo['delivery_status']) {
            case 'DONE':
                $ship_status = 'SHIP_FINISH';
                break;
            case 'PENDING':
                $ship_status = 'SHIP_NO';
                break;
            case 'PARTAIL':
                $ship_status = 'SHIP_PART';
                break;
        }

        if ($orderInfo['receipt_type'] == 'ziti' && $orderInfo['ziti_status'] == 'DONE') {
            $ship_status = 'SHIP_FINISH';
        }

        if ($orderInfo['receipt_type'] == 'dada' && $orderInfo['order_status'] == 'DONE') {
            $ship_status = 'SHIP_FINISH';
        }

        $payment_type = [
            'amorepay' => '微信支付(amorepay)',
            'wxpay' => '微信支付',
            'wxpayh5' => '微信支付',
            'wxpayjs' => '微信支付',
            'wxpayapp' => '微信支付',
            'wxpaypos' => '微信条码支付',
            'wxpaypc' => '微信PC支付',
            'pos' => '刷卡',
            'point' => '积分',
            'dhpoint' => '积分',
            'deposit' => '预存款支付',
            'alipay' => '支付宝支付',
            'alipayh5' => '支付宝支付',
            'alipayapp' => '支付宝支付',
            'alipaypos' => '支付宝条码支付',
            'alipaymini' => '支付宝小程序',
            'hfpay' => '汇付支付',
        ];

        // 优惠信息
        $promotion_details = $this->__getPmtDetail($orderInfo);

        //订单明细
        $orders = $this->___formatOrder($orderInfo, $tradeInfo, $status, $distributor);

        // 获取支付列表信息
        $payment_lists = $this->__formatPaymentLists($tradeInfo);

        //获取订单发票
        $taxInfo = $this->__getTaxInfo($orderInfo);

        //包装信息
        $pack = (isset($orderInfo['pack']) && $orderInfo['pack']) ? json_decode($orderInfo['pack'], 1) : 0;
        $packDesc = '';
        if ($pack) {
            $packDesc = isset($pack['packDes']) ? $pack['packDes'] : 0;
        }

        $erpOrderStruct = [
            'tid' => $orderInfo['order_id'],
            'lastmodify' => date('Y-m-d H:i:s', $orderInfo['update_time']),
            'promotion_details' => json_encode($promotion_details),
            'total_weight' => '0.000',
            'created' => date('Y-m-d H:i:s', $orderInfo['create_time']),
            'payment_lists' => json_encode($payment_lists),
            'status' => $status,
            'pay_status' => $pay_status,
            'ship_status' => $ship_status,
            'payed_fee' => bcdiv($tradeInfo['payFee'] ?? 0, 100, 2),
            'total_goods_fee' => bcdiv($orderInfo['item_fee'], 100, 2),
            'total_trade_fee' => bcdiv($orderInfo['total_fee'], 100, 2),
            'currency' => 'CNY',
            'currency_rate' => $orderInfo['fee_rate'],
            'buyer_obtain_point_fee' => '0.000',
            'is_protect' => 'false',
            'protect_fee' => '0.000',
            'discount_fee' => 0,
            'is_cod' => 'false',
            'payment_tid' => $tradeInfo['payType'] ?? $orderInfo['pay_type'],
            'payment_type' => $payment_type[$tradeInfo['payType'] ?? $orderInfo['pay_type']] ?? '',
            'orders_number' => 1,// 子单商品总数量 暂定
            'buyer_uname' => $member_info['uname'],
            'buyer_name' => isset($member_info['name']) ? trim($member_info['name']) : $member_info['uname'],
            'buyer_mobile' => $member_info['mobile'],
            'buyer_phone' => $member_info['mobile'],
            'buyer_email' => "",
            'buyer_state' => "",
            'receiver_name' => $orderInfo['receiver_name'],
            'receiver_phone' => "",
            'receiver_mobile' => $orderInfo['receiver_mobile'],
            'receiver_state' => $orderInfo['receiver_state'],
            'receiver_city' => $orderInfo['receiver_city'],
            'receiver_district' => $orderInfo['receiver_district'],
            'receiver_address' => $orderInfo['receiver_address'],
            'receiver_zip' => $orderInfo['receiver_zip'],
            'orders_discount_fee' => bcdiv($orderInfo['discount_fee'] + ($orderInfo['point_fee'] ?? 0), 100, 2),
            'orders' => json_encode($orders),
            'goods_discount_fee' => '0.00',
            'shipping_tid' => '0',
            'shipping_type' => $orderInfo['delivery_corp'],
            'shipping_fee' => bcdiv($orderInfo['freight_fee'], 100, 2),
            'has_invoice' => $taxInfo['is_tax'],
            'invoice_title' => $taxInfo['title'],
            'invoice_fee' => $taxInfo['cost_tax'],
            'pay_cost' => '0.00',
            'buyer_memo' => $orderInfo['remark'],
            'trade_memo' => $packDesc,
        ];

        if ($distributor) {
            $erpOrderStruct['o2o_info'] = json_encode([
                'o2o_store_bn' => $distributor['shop_code'],
                'o2o_store_name' => $distributor['name'],
            ]);
        }

        if ($orderInfo['receipt_type'] == 'ziti') {
            $erpOrderStruct['receiver_name'] = '';
            $erpOrderStruct['receiver_mobile'] = '';
            $erpOrderStruct['receiver_state'] = '';
            $erpOrderStruct['receiver_city'] = '';
            $erpOrderStruct['receiver_district'] = '';
            $erpOrderStruct['receiver_address'] = '';
            $erpOrderStruct['receiver_zip'] = '';
            $erpOrderStruct['delivery_corp'] = '';
            $erpOrderStruct['shipping_type'] = 'STORE_SELF_FETCH';
        }

        if ($orderInfo['receipt_type'] == 'dada') {
            $erpOrderStruct['shipping_type'] = self::DADA_SHIPPING_TYPE;
        }

        if (($tradeInfo['payType'] ?? '') == 'dhpoint') {
            $erpOrderStruct['total_trade_fee'] = bcdiv(($orderInfo['item_fee'] + $orderInfo['freight_fee']), 100, 2);
            $erpOrderStruct['payed_fee'] = $erpOrderStruct['total_trade_fee'];
        }
        $allow_params = array('rights_level','lastmodify','payment_lists','promotion_details','total_weight','buyer_name','currency_rate','app_id','shipping_type','receiver_address','has_invoice','receiver_district','from_type','callback_type','protect_fee','receiver_phone','to_node_id','order_source','logistics_no','pay_cost','buyer_uname','timestamp','_id','tid','receiver_mobile','goods_discount_fee','orders_number','invoice_fee','discount_fee','pay_status','buyer_obtain_point_fee','payment_type','v','real_time','shipping_fee','refresh_time','is_cod','msg_id','currency','node_type','pay_time','payment_tid','orders','receiver_city','channel_ver','orders_discount_fee','format','buyer_memo','from_node_id','shipping_tid','method','channel','status','total_trade_fee','buyer_state','receiver_zip','callback_type_id','to_type','node_id','total_goods_fee','date','buyer_mobile','task','created','ship_status','payed_fee','is_protect','receiver_state','receiver_name','consign_time','step_trade_status','trade_memo','invoice_desc   ','invoice_title','trade_type','buyer_email','cod_status','step_paid_fee','modified','end_time', 'service_order_objects', 'buyer_phone', 'service_orders', 'o2o_info');

        foreach ($erpOrderStruct as $k => $v) {
            if (!in_array($k, $allow_params)) {
                unset($erpOrderStruct[$k]);
            }
        }
        $this->__fixPayed($erpOrderStruct);
        app('log')->debug("\n saaserp 更新订单信息".__FUNCTION__.",".__LINE__.", OrderStruct===>:".json_encode($erpOrderStruct)."\n");
        $request = new SaasErpRequest($companyId);
        $return = $request->call('store.trade.update', $erpOrderStruct);
        return $return;
    }

    /**
     * 用户撤销仅退款申请后通知 SaasErp
     *
     */
    public function cancelSaasErpRefund($companyId, $orderId, $refundBn)
    {
        //获取订单信息
        $orderService = $this->getOrderService('normal');
        $orderData = $orderService->getOrderInfo($companyId, $orderId);
        $orderInfo = $orderData['orderInfo'];
        $tradeInfo = $orderData['tradeInfo'];

        //获取退款申请单
        $filter = [
            'order_id' => $orderId,
            'company_id' => $companyId,
            'refund_bn' => $refundBn,
        ];
        $aftersalesRefundService = new AftersalesRefundService();
        $refundInfo = $aftersalesRefundService->aftersalesRefundRepository->getInfo($filter);
        if ($refundInfo) {
            throw new Exception("未获取到退款信息");
        }

        $memo = $refundInfo['refunds_memo'];

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
        ];

        return $refundData;
    }
}
