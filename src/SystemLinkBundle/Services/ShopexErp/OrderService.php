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

use OrdersBundle\Services\Orders\NormalOrderService;
use GoodsBundle\Services\ItemsService;
use MembersBundle\Services\MemberService;
use CompanysBundle\Services\CompanysService;

use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Services\OrdersPromotionService;
use PromotionsBundle\Services\PromotionSeckillActivityService;

class OrderService
{
    use GetOrderServiceTrait;

    public function __construct()
    {
    }

    /**
     * 生成发给taoex 订单结构体
     *
     */
    public function getOrderStruct($companyId, $orderId, $sourceType = 'normal')
    {
        // ShopEx EcShopX Service Component
        $orderService = $this->getOrderService($sourceType);

        // 获取订单信息
        $orderData = $orderService->getOrderInfo($companyId, $orderId);

        if (!$orderData) {
            return false;
        }

        // 自提订单暂不推送
        if ($orderData['orderInfo']['receipt_type'] == 'ziti') {
            return false;
        }

        $key = 'sendOmsSetting:'. $companyId;
        $setting = app('redis')->connection('companys')->get($key);
        $setting = json_decode($setting, 1);

        // 获取订单明细
        $order_objects = $this->___formatOrderObjects($orderData['orderInfo'], $orderData['tradeInfo']);

        // 获取支付单信息
        $payments = $this->__formatPayments($orderData['tradeInfo']);

        // 获取订单配送信息
        $consignee = $this->__formatConsignee($orderData['orderInfo']);

        // 获取买家信息
        $member_info = $this->__formatMemberInfo($orderData['orderInfo']);

        // 获取支付列表信息
        $payment_lists = $this->__formatPaymentLists($orderData['tradeInfo']);

        // 获取公司信息
        $company_info = $this->__getCompanysInfo($companyId, $orderData['orderInfo']['order_id']);

        //获取订单优惠
        $pmt_detail = $this->__getPmtDetail($orderData);

        //获取订单发票
        $taxInfo = $this->__getTaxInfo($orderData);

        $status = 'active';
        $pay_status = '0';
        switch ($orderData['orderInfo']['order_status']) {
            case 'DONE':
                $status = 'finish';
                $pay_status = '1';
                break;
            case 'NOTPAY':
                $status = 'active';
                $pay_status = '0';
                break;
            case 'PAYED':
                $status = 'active';
                $pay_status = '1';
                break;
            case 'CANCEL':
                $status = 'dead';
                $pay_status = '5';
                break;
        }

        // 组织OME订单数据
        $orderStruct = [
            'to_api_v' => '3.0',
            'refresh_time' => date('Y-m-d H:i:s', time()),
            'cost_item' => bcdiv($orderData['orderInfo']['item_fee'], 100, 2),
            'lastmodify' => date('Y-m-d H:i:s', $orderData['orderInfo']['update_time']),
            'title' => remove_emoji($orderData['orderInfo']['title']),
            'from_type' => 'ecos.b2c',
            'order_bn' => $orderData['orderInfo']['order_id'],
            'pmt_detail' => json_encode($pmt_detail),
            'pmt_goods' => '0.000', //
            'score_u' => '0.000',
            'timestamp' => time(),
            'from_api_v' => '2.0',
            'score_g' => '0.000',
            'is_tax' => $taxInfo['is_tax'],
            'tax_title' => $taxInfo['title'],
            'cost_tax' => $taxInfo['cost_tax'],
            'orders_number' => '1', //购买数量
            'mark_text' => remove_emoji($orderData['orderInfo']['remark']), //买家备注
            'from_release_version' => 'default',
            'modified' => time(),
            'payed' => bcdiv($orderData['tradeInfo']['payFee'], 100, 2),
            'order_objects' => json_encode($order_objects),
            'payments' => json_encode($payments),
            'pay_bn' => $orderData['tradeInfo']['payType'],
            'weight' => '0.000',
            'cur_rate' => '1.0000',
            'consignee' => json_encode($consignee),
            'currency' => 'CNY',
            'node_type' => 'ecos.ome',
            'consigner' => '{}',
            'payinfo' => json_encode(['pay_name' => $payments[0]['paymethod'],'cost_payment' => $payments[0]['paycost']]),
            'custom_mark' => '',
            'node_version' => '2.0',
            'shipping_tid' => '3',
            'selling_agent' => '',
            //'pay_status' => '1',
            'pay_status' => $pay_status,
            //'status' => 'active',
            'status' => $status,
            'pmt_order' => bcdiv($orderData['orderInfo']['discount_fee'], 100, 2),
            'member_info' => json_encode($member_info),
            'discount' => '0.000',
            'payment_lists' => json_encode($payment_lists),
            'total_amount' => bcdiv($orderData['tradeInfo']['totalFee'], 100, 2),
            'to_type' => 'ecos.ome',
            'ship_status' => '0',
            'cur_amount' => bcdiv($orderData['tradeInfo']['totalFee'], 100, 2),
            'shipping' => json_encode(['cost_shipping' => bcdiv($orderData['orderInfo']['freight_fee'], 100, 2)]),
            'sales_org' => $company_info['vkorg'], //销售组织
            'customer_code' => $company_info['kunnr'], //客户编码
            'customer_name' => $company_info['mall_id'], //客户名称
            'brand_code' => $company_info['werks'], //品牌编码
            'buyer_id' => $orderData['orderInfo']['user_id'],
            'createtime' => time(),
            'to_node_type' => 'ecos.ome',
        ];

        if ($orderData['tradeInfo']['payType'] == 'dhpoint') {
            $orderStruct['total_amount'] = bcdiv(($orderData['orderInfo']['item_fee'] + $orderData['orderInfo']['freight_fee']), 100, 2);
            $orderStruct['cur_amount'] = $orderStruct['total_amount'];
            $orderStruct['payed'] = $orderStruct['total_amount'];
        }

        $this->__fixPayed($orderStruct);

        app('log')->debug('ome orderStruct===>:'.json_encode($orderStruct, 256));
        return $orderStruct;
    }

    /**
     * 处理实付金额和订单金额不一致的问题
     */
    private function __fixPayed(&$orderStruct)
    {
        //如果实付金额 > 订单金额，实付金额改成 = 订单金额
        if (floatval($orderStruct['payed']) > floatval($orderStruct['total_amount'])) {
            $orderStruct['payed'] = $orderStruct['total_amount'];
        }
    }

    /**
     * 获取发票信息
     */
    private function __getTaxInfo($orderData)
    {
        app('log')->debug('ome getTaxInfo:'.var_export($orderData['orderInfo']['invoice'], 1));
        $taxInfo = [
            'title' => isset($orderData['orderInfo']['invoice']['title']) ? trim($orderData['orderInfo']['invoice']['title']) : '0',
            'is_tax' => isset($orderData['orderInfo']['invoice']['title']) ? 1 : 0 ,
            // 'cost_tax' => isset($orderData['tradeInfo']['payFee']) ? $orderData['tradeInfo']['payFee']/100 : '0.00',
            'cost_tax' => '0.00',
        ];
        app('log')->debug('ome getTaxInfo_result:'.var_export($taxInfo, 1));
        return $taxInfo;
    }

    /**
     * 获取公司信息
     * ckorg:销售组织  kunnr:客户编码  mall_id:店铺  werks:工厂  lgort:库位
     */
    private function __getCompanysInfo($company_id, $order_id)
    {
        $ordersPromotionService = new OrdersPromotionService();

        $promotionInfo = $ordersPromotionService->getInfo(['moid' => $order_id, 'activity_type' => 'limited_time_sale']);

        if (!isset($promotionInfo['activity_id']) || !$promotionInfo['activity_id']) {
            $companysService = new CompanysService();

            $companysInfo = $companysService->getInfo(['company_id' => $company_id]);

            $thirdInfo = $companysInfo['third_params'] ?? [];
            $result['werks'] = '';
        } else {
            // 内购
            $promotionSeckillActivityService = new PromotionSeckillActivityService();

            $seckillActivity = $promotionSeckillActivityService->getInfoById($promotionInfo['activity_id']);

            $thirdInfo = $seckillActivity['otherext'];

            $vkorg = '';
            if (isset($thirdInfo['vkorg'])) {
                $vkorg = trim($thirdInfo['vkorg']);
            }

            $kunnr = '';
            if (isset($thirdInfo['kunnr'])) {
                $kunnr = trim($thirdInfo['kunnr']);
            }

            $result['werks'] = $vkorg. $kunnr;
        }

        $result['vkorg'] = isset($thirdInfo['vkorg']) ? trim($thirdInfo['vkorg']) : '';
        $result['kunnr'] = isset($thirdInfo['kunnr']) ? trim($thirdInfo['kunnr']) : '';
        $result['mall_id'] = isset($thirdInfo['mall_id']) ? trim($thirdInfo['mall_id']) : '';

        return $result;
    }

    /**
     * 组织买家信息 转换成OME结构
     */
    private function __formatMemberInfo($orderInfo)
    {
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
     * 组织收货地址信息 转换成OME结构
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
     * 组织支付列表信息 转换成OME结构
     */
    private function __formatPaymentLists($tradeInfo)
    {
        $pay_type = [
            'amorepay' => '微信支付(amorepay)',
            'wxpay' => '微信支付',
            'deposit' => '预存款支付',
            'pos' => '刷卡',
            'point' => '积分',
            'dhpoint' => '积分',
            'alipayh5' => '支付宝H5',
            'wxpayjs' => '微信支付H5',
        ];

        $payment_lists['payment_list'][] = [
            'tid' => $tradeInfo['orderId'],
            'payment_id' => $tradeInfo['tradeId'],
            'seller_bank' => $pay_type[$tradeInfo['payType']] ?? $tradeInfo['payType'],
            'seller_account' => $tradeInfo['openId'],
            'buyer_account' => $tradeInfo['mchId'],
            'currency' => 'CNY',
            'paycost' => '0.000',
            'pay_type' => 'online',
            'payment_name' => $pay_type[$tradeInfo['payType']] ?? $tradeInfo['payType'],
            'payment_code' => $tradeInfo['payType'],
            't_begin' => date('Y-m-d H:i:s', $tradeInfo['timeStart']),
            't_end' => date('Y-m-d H:i:s', $tradeInfo['timeExpire']),
            'status' => 'SUCC',
            'memo' => '',
            'outer_no' => $tradeInfo['transactionId'],
            'pay_fee' => bcdiv($tradeInfo['payFee'], 100, 2),
            'currency_fee' => !empty($tradeInfo['curPayFee']) ? bcdiv($tradeInfo['curPayFee'], 100, 2) : '',
        ];

        return $payment_lists;
    }

    /**
     * 组织支付单信息 转换成OME结构
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
            'alipayh5' => '支付宝H5',
        ];

        $order_payments[] = [
            'trade_no' => $payments['tradeId'],
            'account' => trim($payments['body']),
            'pay_account' => $payments['mchId'],
            'paymethod' => $pay_type[$payments['payType']] ?? $payments['payType'],
            'money' => bcdiv($payments['payFee'], 100, 2),
            'memo' => trim($payments['detail']),
            'paycost' => '0.00',
            'bank' => $payments['bankType'],
        ];

        return $order_payments;
    }

    /**
     * 组织order_objects数据 转换成OME结构
     */
    private function ___formatOrderObjects($orderInfo, $tradeInfo)
    {
        $order_objects = [];

        $itemsService = new ItemsService();

        foreach ($orderInfo['items'] as $key => $value) {
            // $itemData = $itemsService->getItemsDetail($value['item_id']);

            $order_item[0] = [
                'score' => $value['item_point'],
                'addon' => '',
                'name' => remove_emoji($value['item_name']),
                'weight' => $value['weight'] ?? 0,
                'pmt_price' => '0.00', //优惠金额
                'bn' => $value['item_bn'],
                'item_status' => 'normal',
                'weblink' => '',
                'product_attr' => [], //暂时没有多规格
                'item_type' => $value['order_item_type'] == 'gift' ? 'gift' : 'product', //固定product
                'amount' => bcdiv($value['item_fee'], 100, 2),  //商品总价
                'shop_goods_id' => $value['item_id'],
                'sendnum' => '0',
                'sale_price' => bcdiv($value['item_fee'], 100, 2), //销售价
                'quantity' => $value['num'],
                'price' => bcdiv($value['price'], 100, 2), //原价
                'shop_product_id' => $value['item_id'],
                'divide_order_fee' => bcdiv($value['total_fee'], 100, 2),
                'part_mjz_discount' => bcdiv($value['discount_fee'], 100, 2),
            ];
            $order_objects[] = [
                'consign_time' => '',
                'weight' => ($value['weight'] ?? 0) * $value['num'],
                'bn' => $value['item_bn'],
                //'oid' => $value['order_id'],
                'oid' => $value['id'],//用来识别拆单发货的商品
                'order_items' => $order_item,
                'obj_alias' => '商品区块', //固定
                'obj_type' => $value['order_item_type'] == 'gift' ? 'gift' : 'goods', //固定goods
                'name' => remove_emoji($value['item_name']),
                'pmt_price' => '0.00',
                'amount' => bcdiv($value['item_fee'], 100, 2),
                'score' => $value['item_point'],
                'shop_goods_id' => $value['item_id'],
                'order_status' => 'SHIP_NO', //未发货
                'price' => bcdiv($value['price'], 100, 2),
                'quantity' => $value['num'],
                'is_gift' => $value['order_item_type'] == 'gift' ? '1' : '0',
                'is_mileage' => $tradeInfo['payType'] == 'dhpoint' ? '1' : '0', // 积分支付 默认0
            ];
        }

        return $order_objects;
    }

    /**
     * 组织orders数据 OME单拉数据使用
     */
    private function ___formatOmeOrder($orderData)
    {
        $orderInfo = $orderData['orderInfo'];
        $tradeInfo = $orderData['tradeInfo'];

        $order_objects = [];

        $itemsService = new ItemsService();

        foreach ($orderInfo['items'] as $key => $value) {
            // $itemData = $itemsService->getItemsDetail($value['item_id']);

            $order_item['orderitem'][0] = [
                'sku_id' => $value['item_id'],
                'name' => remove_emoji($value['item_name']),
                'weight' => $value['weight'] ?? 0,
                'iid' => $value['item_id'],
                'discount_fee' => '0.00', //商品优惠金额
                'bn' => $value['item_bn'],
                'sku_properties' => [], //暂时没有多规格
                'item_status' => 'normal',
                'weblink' => '',
                'item_type' => $value['order_item_type'] == 'gift' ? 'gift' : 'product', //固定product
                'num' => $value['num'],
                'sendnum' => '0',
                'sale_price' => bcdiv($value['item_fee'], 100, 2),  //商品销售价
                'score' => $value['item_point'],
                'price' => bcdiv($value['price'], 100, 2),   //原价
                'total_item_fee' => bcdiv($value['item_fee'], 100, 2), //商品总额
                'divide_order_fee' => bcdiv($value['total_fee'], 100, 2), //实付金额
                'part_mjz_discount' => bcdiv($value['discount_fee'], 100, 2), //优惠分摊
            ];
            $order_objects['order'][] = [
                'consign_time' => '',
                'weight' => (($value['weight'] ?? 0) ? $value['weight'] : 0) * $value['num'],
                'title' => remove_emoji($orderInfo['title']),
                'discount_fee' => bcdiv($tradeInfo['discountFee'], 100, 2),
                'type' => $value['order_item_type'] == 'gift' ? 'gift' : 'goods',
                'price' => bcdiv($value['price'], 100, 2),  //原价
                'oid' => $value['order_id'],
                'order_status' => 'SHIP_NO', //未发货
                'order_items' => $order_item,
                'iid' => $value['item_id'],
                'type_alias' => '商品区块', //固定
                'total_order_fee' => bcdiv($tradeInfo['totalFee'], 100, 2),
                'items_num' => $value['num'],
                'orders_bn' => $value['order_id'],
                'is_gift' => $value['order_item_type'] == 'gift' ? '1' : '0', //是否赠品
                'is_mileage' => $tradeInfo['payType'] == 'dhpoint' ? '1' : '0', // 积分支付 默认0
            ];
        }

        return $order_objects;
    }

    /**
     * OME 获取订单优惠
     *
     */
    public function __getPmtDetail($orderData)
    {
        $pmtDetail = [];
        if (isset($orderData['orderInfo']['discount_info']) && $orderData['orderInfo']['discount_info']) {
            foreach ((array)$orderData['orderInfo']['discount_info'] as $key => $val) {
                if (!$val) {
                    continue;
                }
                $pmtValue = [
                    'pmt_amount' => bcdiv($val['discount_fee'], 100, 2),
                    'pmt_id' => $orderData['orderInfo']['order_id'],
                    'pmt_describe' => $val['rule'],
                ];
                $pmtDetail[] = $pmtValue;
            }
        }
        return $pmtDetail;
    }


    /**
     * OME 主动获取订单结构体
     *
     */
    public function getOmeOrderInfo($companyId, $orderId, $sourceType = 'normal')
    {
        $orderService = $this->getOrderService($sourceType);

        // 获取订单信息
        $orderData = $orderService->getOrderInfo($companyId, $orderId);

        if (!$orderData) {
            return false;
        }

        if (in_array($orderData['orderInfo']['order_status'], ['NOTPAY', 'CANCEL', 'WAIT_BUYER_CONFIRM'])) {
            return false;
        }

        // 获取买家信息
        $member_info = $this->__formatMemberInfo($orderData['orderInfo']);

        $status = '';
        $pay_status = '';
        switch ($orderData['orderInfo']['order_status']) {
            case 'DONE':
                $status = 'TRADE_FINISHED';
                $pay_status = 'PAY_FINISH';
                break;
            case 'NOPTAY':
                $status = 'TRADE_ACTIVE';
                $pay_status = 'PAY_NO';
                break;
            case 'PAYED':
                $status = 'TRADE_ACTIVE';
                $pay_status = 'PAY_FINISH';
                break;
            case 'CANCEL':
                $status = 'TRADE_CLOSED';
                $py_status = 'REFUND_ALL';
                break;
        }

        $ship_status = '';
        switch ($orderData['orderInfo']['delivery_status']) {
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


        $payment_type = [
            'amorepay' => '微信支付(amorepay)',
            'wxpay' => '微信支付',
            'deposit' => '预存款支付',
            'pos' => '刷卡',
            'point' => '积分',
            'dhpoint' => '积分',
        ];

        // 优惠信息
        $promotion_details = $this->__getPmtDetail($orderData);

        //订单明细
        $orders = $this->___formatOmeOrder($orderData);

        // 获取支付列表信息
        $payment_lists = $this->__formatPaymentLists($orderData['tradeInfo']);

        // 获取公司信息
        $company_info = $this->__getCompanysInfo($companyId, $orderData['orderInfo']['order_id']);

        //获取订单发票
        $taxInfo = $this->__getTaxInfo($orderData);

        // 组织OME订单数据
        $omeOrderStruct = [
            'promotion_details' => $promotion_details,
            'buyer_name' => isset($member_info['name']) ? trim($member_info['name']) : $member_info['uname'],
            'is_cod' => 'false',
            'receiver_email' => '',
            'point_fee' => intval($orderData['orderInfo']['point']),
            'total_goods_fee' => bcdiv($orderData['orderInfo']['item_fee'], 100, 2),
            'currency' => 'CNY',
            'total_weight' => '0.000',
            'total_currency_fee' => bcdiv($orderData['orderInfo']['total_fee'], 100, 2),
            'shipping_type' => $orderData['orderInfo']['delivery_corp'],
            'receiver_address' => $orderData['orderInfo']['receiver_address'],
            'payment_tid' => $orderData['tradeInfo']['payType'],
            'orders' => $orders,
            'trade_memo' => null,
            'lastmodify' => date('Y-m-d H:i:s', $orderData['orderInfo']['update_time']),
            'receiver_district' => $orderData['orderInfo']['receiver_district'],
            'receiver_city' => $orderData['orderInfo']['receiver_city'],
            'title' => remove_emoji($orderData['orderInfo']['title']),
            'orders_discount_fee' => bcdiv($orderData['tradeInfo']['discountFee'], 100, 2),
            'buyer_memo' => '',
            'receiver_state' => $orderData['orderInfo']['receiver_state'],
            'tid' => $orderData['orderInfo']['order_id'],
            'protect_fee' => '0.000',
            'receiver_phone' => '',
            'pay_status' => $pay_status,
            'buyer_id' => $orderData['orderInfo']['user_id'],
            'status' => $status,
            'total_trade_fee' => bcdiv($orderData['tradeInfo']['totalFee'], 100, 2),
            'buyer_address' => null,
            'pay_cost' => '0.000',
            'buyer_uname' => $member_info['uname'],
            'buyer_email' => null,
            'receiver_time' => '任意时间,任意时间段',
            'buyer_zip' => null,
            'payment_lists' => $payment_lists,
            'receiver_mobile' => $orderData['orderInfo']['receiver_mobile'],
            'buyer_mobile' => $member_info['mobile'],
            'goods_discount_fee' => '0.000',
            'orders_number' => 1,
            'shipping_tid' => '0',
            'created' => date('Y-m-d H:i:s', $orderData['orderInfo']['create_time']),
            'ship_status' => $ship_status,
            'payed_fee' => bcdiv($orderData['tradeInfo']['payFee'], 100, 2),
            'has_invoice' => $taxInfo['is_tax'],
            'invoice_title' => $taxInfo['title'],
            'invoice_fee' => $taxInfo['cost_tax'],
            'modified' => date('Y-m-d H:i:s', $orderData['orderInfo']['update_time']),
            'is_protect' => 'false',
            'discount_fee' => '0.000',
            'buyer_obtain_point_fee' => '0.000',
            'payment_type' => $payment_type[$orderData['tradeInfo']['payType']] ?? $orderData['tradeInfo']['payType'],
            'buyer_phone' => $member_info['mobile'],
            'receiver_name' => $orderData['orderInfo']['receiver_name'],
            'shipping_fee' => bcdiv($orderData['orderInfo']['freight_fee'], 100, 2),
            'receiver_zip' => $orderData['orderInfo']['receiver_zip'],
            'currency_rate' => $orderData['orderInfo']['fee_rate'],
            'sales_org' => $company_info['vkorg'], //销售组织
            'customer_code' => $company_info['kunnr'], //客户编码
            'customer_name' => $company_info['mall_id'], //客户名称
            'brand_code' => $company_info['werks'], //品牌编码
        ];
        if ($orderData['tradeInfo']['payType'] == 'dhpoint') {
            $omeOrderStruct['total_trade_fee'] = bcdiv(($orderData['orderInfo']['item_fee'] + $orderData['orderInfo']['freight_fee']), 100, 2);
            $omeOrderStruct['payed_fee'] = $omeOrderStruct['total_trade_fee'];
        }
        // echo "<pre>";var_dump('sss',$omeOrderStruct);exit;
        app('log')->debug('ome omeOrderStruct===>:'.var_export($omeOrderStruct, 1));
        return $omeOrderStruct;
    }

    public function updateOrderStatusReview($orderId, $status, $reviewTime)
    {
        $normalOrderService = new NormalOrderService();
        $filter = [
            'order_id' => $orderId,
        ];
        $orderInfo = $normalOrderService->normalOrdersRepository->getInfo($filter);
        if (!$orderInfo) {
            return false;
        }
        if ($orderInfo['order_status'] != 'PAYED') {
            return false;
        }
        $filter['company_id'] = $orderInfo['company_id'];
        $updatedata['order_status'] = $status;
        $updatedata['delivery_time'] = $reviewTime;
        $result = $normalOrderService->update($filter, $updatedata);
        if ($result) {
            return true;
        }
        return false;
    }

    public function updateSendOmsStatus($orderId)
    {
        $normalOrderService = new NormalOrderService();
        $filter = [
            'order_id' => $orderId,
        ];
        $orderInfo = $normalOrderService->normalOrdersRepository->getInfo($filter);
        if (!$orderInfo) {
            return false;
        }
        $filter['company_id'] = $orderInfo['company_id'];
        $updatedata['is_send_oms_status'] = true;
        $result = $normalOrderService->update($filter, $updatedata);
        if ($result) {
            return true;
        }
        return false;
    }
}
