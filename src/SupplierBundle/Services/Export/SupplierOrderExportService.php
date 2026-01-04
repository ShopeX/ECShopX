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

namespace SupplierBundle\Services\Export;

use DistributionBundle\Services\DistributorService;
use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;
use OrdersBundle\Entities\NormalOrdersRelSupplier;
use OrdersBundle\Services\NormalOrdersRelZitiService;
use OrdersBundle\Services\OrderDeliveryService;
use OrdersBundle\Services\Orders\NormalOrderService;
use OrdersBundle\Services\TradeService;
use SupplierBundle\Services\SupplierOrderService;

class SupplierOrderExportService implements ExportFileInterface
{
    private function getTitle()
    {
        $title = [
            'order_id' => '订单号',//
            'store_name' => '来源店铺',
            'mobile' => '会员手机号',//
            'create_time' => '下单时间',//
            'num' => '购买数量',
            'cost_fee' => '结算成本总价（¥）',
            'freight_fee' => '运费(总)',//
            'order_class' => '订单类型',
            'order_status' => '订单状态',//
            'receipt_type' => '收货方式',//
            'ziti_status' => '自提状态',
            'receiver_name' => '收货人姓名',//
            'receiver_mobile' => '收货人手机',//
            'receiver_zip' => '收货人邮编',//
            'receiver_state' => '收货人所在省份',//
            'receiver_city' => '收货人所在城市',//
            'receiver_district' => '收货人所在地区、县',//
            'receiver_address' => '收货地址',//
            'delivery_status' => '发货状态',
            'delivery_time' => '发货时间',
            'end_time' => '订单完成时间',
            'delivery_code' => '快递单号',
            'delivery_corp' => '快递公司',
            'pay_type' => '支付方式',
            'pay_time' => '支付时间',
            'invoice' => '发票内容',
            'remark' => '订单备注',
            'pickup_address' => '自提地址',
            'pickup_datetime' => '提货时间',
        ];
        return $title;
    }

    private function getTitleBak()
    {
        $title = [
            'order_id' => '订单号',//
            'store_name' => '来源店铺',
            'mobile' => '会员手机号',//
            'create_time' => '下单时间',//
            'item_fee' => '商品金额',
            'freight_fee' => '运费(总)',//
            'total_fee' => '实付金额(总)',//
            // 'commission_fee' => '佣金(总)',//
            'discount_fee' => '优惠金额',
            'discount_info' => '优惠详情',
            'order_class' => '订单类型',
            'order_status' => '订单状态',//
            'receipt_type' => '收货方式',//
            'ziti_status' => '自提状态',
            'receiver_name' => '收货人姓名',//
            'receiver_mobile' => '收货人手机',//
            'receiver_zip' => '收货人邮编',//
            'receiver_state' => '收货人所在省份',//
            'receiver_city' => '收货人所在城市',//
            'receiver_district' => '收货人所在地区、县',//
            'receiver_address' => '收货地址',//
            'delivery_status' => '发货状态',
            'delivery_time' => '发货时间',
            'delivery_code' => '快递单号',
            'delivery_corp' => '快递公司',
            'pay_type' => '支付方式',
            'invoice' => '发票内容',
            'remark' => '订单备注',
            'pickup_address' => '自提地址',
            'pickup_datetime' => '提货时间',
            // 'source_from' => '订单来源',
        ];
        return $title;
    }

    public function exportData($filter)
    {
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        $supplierOrderService = new SupplierOrderService();
        $count = $supplierOrderService->repository->count($filter);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis_')."订单数据";
        $title = $this->getTitle();
        $orderList = $this->getLists($filter, $count, $datapassBlock);
        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $title, $orderList);
        return $result;
    }

    private function getLists($filter, $count, $datapassBlock)
    {
        $title = $this->getTitle();
        $tradeState = [
            'PARTIAL_REFUND' => '部分退款',
            'FULL_REFUND' => '全额退款',
            'SUCCESS' => '支付完成',
        ];
        $orderClass = [
            'community' => '社区活动订单',
            'groups' => '拼团活动订单',
            'seckill' => '秒杀活动订单',
            'normal' => '普通订单',
            'drug' => '药品需求订单',
            'shopguide' => '代客下单订单',
            'pointsmall' => '积分商城订单',
            'bargain' => '砍价订单',
            'excard' => '兑换券订单',
            'shopadmin' => '门店订单',
        ];
        $payTypes = [
            'wxpay' => '微信支付',
            'wxpaypc' => '微信支付',
            'wxpayh5' => '微信支付',
            'wxpayjs' => '微信支付',
            'wxpayapp' => '微信支付',
            'wxpaypos' => '微信支付',
            'hfpay' => '微信支付',
            'adapay' => '微信支付',
            'alipay' => '支付宝',
            'alipayh5' => '支付宝',
            'alipayapp' => '支付宝',
            'alipaypos' => '支付宝',
            'point' => '积分支付',
            'deposit' => '余额支付',
            'pos' => '现金支付',
            'gat' => '关爱通支付',
            'offline' => '线下转账',
        ];
        $supplierOrderService = new SupplierOrderService();
        $distributorService = new DistributorService();
        $normalOrderService = new NormalOrderService();
        $orderDeliveryService = new OrderDeliveryService();
        $ordersRelZitiService = new NormalOrdersRelZitiService();
        $normalOrdersRelSupplierRepository = app('registry')->getManager('default')->getRepository(NormalOrdersRelSupplier::class);
        $tradeService = new TradeService();

        $pageSize = 500;
        $orderBy = ['id' => 'DESC'];
        $totalPage = ceil($count / $pageSize);
        for ($page = 1; $page <= $totalPage; $page++) {
            $orderList = [];
            $rsOrder = $supplierOrderService->repository->getLists($filter, '*', $page, $pageSize, $orderBy);

            //  发货信息
            $deliveryInfo = [];
            $order_ids = array_column($rsOrder, 'order_id');
            $rsDelivery = $orderDeliveryService->ordersDeliveryRepository->getLists(['supplier_id' => $filter['supplier_id'], 'order_id' => $order_ids]);
            if ($rsDelivery) {
                foreach ($rsDelivery as $v) {
                    $deliveryInfo[$v['order_id']] = $v;
                }
            }

            // 支付信息
            $tradeList = $tradeService->getSuccTradeInfoByOrderIdList($filter['company_id'], $order_ids);

            // 店铺信息
            $storeIds = array_column($rsOrder, 'distributor_id');
            if ($storeIds) {
                $sFilter = [
                    'company_id' => $filter['company_id'],
                    'distributor_id' => $storeIds,
                ];
                $storeList = $distributorService->getDistributorOriginalList($sFilter, 1, $pageSize);
                $storeData = array_column($storeList['list'], null, 'distributor_id');
            }

            // 自提信息
            $zitiList = $ordersRelZitiService->getLists(['order_id' => $order_ids]);
            $zitiData = array_column($zitiList, null, 'order_id');

            // 订单商品购买数量
            $numList = $supplierOrderService->getOrderSupplierNum($order_ids, $filter['company_id'],$filter['supplier_id'] ?? 0);
            $numMap = array_column($numList, null, 'order_id');
            
            // 供应商运费
            $supplierFreigthFilter = [
                'company_id' => $filter['company_id'],
                'order_id' => $order_ids,
                'supplier_id' => $filter['supplier_id'] ?? 0,
            ];
            $supplierMap = []; // 订单按supplier_id总运费运费
            $supplierFreigthList = $normalOrdersRelSupplierRepository->getLists($supplierFreigthFilter);
            foreach ($supplierFreigthList as $k => $v) {
                if (!isset($supplierMap[$v['order_id']]['freight'])) {
                    $supplierMap[$v['order_id']]['freight'] = 0;
                }
                $supplierMap[$v['order_id']]['freight'] += $v['freight_fee'];
            }

            foreach ($rsOrder as $value) {
                $order_id = $value['order_id'];
                if ($datapassBlock) {
                     $value['mobile'] = data_masking('mobile', (string) $value['mobile']);
//                     $value['receiver_name'] = data_masking('truename', (string) $value['receiver_name']);
//                     $value['receiver_mobile'] = data_masking('mobile', (string) $value['receiver_mobile']);
//                     $value['receiver_address'] = data_masking('address', (string) $value['receiver_address']);
                }
                $lineData = [];
                // 发货信息
                if (isset($deliveryInfo[$order_id])) {
                    $value['delivery_corp'] = $deliveryInfo[$order_id]['delivery_corp'] ?? '';
                    $value['delivery_code'] = $deliveryInfo[$order_id]['delivery_code'] ?? '';
                    $value['delivery_time'] = $deliveryInfo[$order_id]['delivery_time'] ?? '';
                    if ($value['delivery_time']) {
                        $value['delivery_time'] = date('Y-m-d H:i:s', $value['delivery_time']);
                    }
                }
                // 优惠信息
                $discountDesc = '';
                if ($value['discount_info']) {
                    $discountInfo = json_decode($value['discount_info'], true);
                    foreach ($discountInfo as $vv) {
                        $a = $this->getDiscountDesc($vv);
                        if ($a) {
                            $discountDesc .= $a;
                        }
                    }
                }
                // 发票信息
                $invoice = [];
                if (isset($value['invoice'])) {
                    $invoicearr = is_array($value['invoice']) ? $value['invoice'] : json_decode($value['invoice'], true);
                    foreach ($invoicearr as $key => $val) {
                        switch ($key) {
                            case "title":
                                $invoice[] = 'title:'.$val;
                                break;
                            case "registration_number":
                                $invoice[] = '税号:'.$val;
                                break;
                            case "content":
                                $invoice[] = '发票抬头:'.$val;
                                break;
                            case "company_address":
                                $invoice[] = '单位地址:'.$val;
                                break;
                            case "bankname":
                                $invoice[] = '开户银行:'.$val;
                                break;
                            case "bankaccount":
                                $invoice[] = '银行账户:'.$val;
                                break;
                            case "company_phone":
                                $invoice[] = '电话号码:'.$val;
                                break;
                        }
                    }
                }
                $invoiceStr = $invoice ? '"'.implode(';', $invoice).'"' : '---无---';

                foreach ($title as $k => $v) {
                    switch ($k) {
                        case 'order_id':
                            $lineData[$k] = "'".$value[$k]."'";
                            break;
                        case 'store_name':
                            $lineData[$k] = $storeData[$value['distributor_id']]['name'] ?? '';
                            break;
                        case 'mobile':
                            $lineData[$k] = "'".$value[$k]."'";
                            break;
                        case 'create_time':
                            $lineData[$k] = date('Y-m-d H:i:s', $value[$k]);
                            break;
                        case 'num':
                            $lineData[$k] = $numMap[$value['order_id']]['num'] ?? 0;
                            break;
                        case 'cost_fee':
                            $lineData[$k] = bcdiv($value[$k], 100, 2);
                            break;
                        case 'freight_fee':
                            $lineData[$k] = isset($supplierMap[$value['order_id']]['freight']) ? bcdiv($supplierMap[$value['order_id']]['freight'], 100, 2) : 0;
                            break;
                        case 'order_class':
                            $lineData[$k] = $orderClass[$value[$k]] ?? $value[$k];
                            break;
                        case 'order_status':
                            $lineData[$k] = $this->getOrderStatusMsg($value);
                            break;
                        case 'receipt_type':
                            $lineData[$k] = $value[$k];
                            break;
                        case 'ziti_status':
                            $lineData[$k] = $value[$k];
                            break;
                        case 'receiver_name':
                            $lineData[$k] = $value[$k];
                            break;
                        case 'receiver_mobile':
                            $lineData[$k] = $value[$k];
                            break;
                        case 'receiver_zip':
                            $lineData[$k] = $value[$k];
                            break;
                        case 'receiver_state':
                            $lineData[$k] = $value[$k];
                            break;
                        case 'receiver_city':
                            $lineData[$k] = $value[$k];
                            break;
                        case 'receiver_district':
                            $lineData[$k] = $value[$k];
                            break;
                        case 'receiver_address':
                            $lineData[$k] = $value[$k];
                            break;
                        case 'delivery_status':
                            $delivery_status = $value[$k];
                            switch ($delivery_status) {
                                case 'DONE':
                                    $delivery_status = '已发货';
                                    break;
                                case 'PARTAIL':
                                    $delivery_status = '部分发货';
                                    break;
                                default:
                                    $delivery_status = '未发货';
                            }
                            $lineData[$k] = $delivery_status;
                            break;
                        case 'delivery_time':
                            $lineData[$k] = $value[$k];
                            break;
                        case 'end_time':
                            $lineData[$k] = isset($value['end_time']) ? date('Y-m-d H:i:s', $value['end_time']) : ''; // 订单完成时间
                            break;
                        case 'delivery_code':
                            $lineData[$k] = $value[$k];
                            break;
                        case 'delivery_corp':
                            $lineData[$k] = $value[$k];
                            break;
                        case 'pay_type':
                            $lineData[$k] = $payTypes[$value[$k]] ?? $value[$k];
                            break;
                        case 'pay_time':
                            $lineData[$k] = isset($tradeList[$value['order_id']]['time_expire']) ? date('Y-m-d H:i:s', $tradeList[$value['order_id']]['time_expire']) : ''; // 支付时间
                            break;
                        case 'invoice':
                            $lineData[$k] = $invoiceStr;
                            break;
                        case 'remark':
                            $lineData[$k] = $value[$k];
                            break;
                        case 'pickup_address':
                            $lineData[$k] = isset($zitiData[$value['order_id']]) ? $zitiData[$value['order_id']]['province'].$zitiData[$value['order_id']]['city'].$zitiData[$value['order_id']]['area'].$zitiData[$value['order_id']]['address'] : '';
                            break;
                        case 'pickup_datetime':
                            $lineData[$k] = isset($zitiData[$value['order_id']]) ? $zitiData[$value['order_id']]['pickup_date'].' '.$zitiData[$value['order_id']]['pickup_time'][0].'~'.$zitiData[$value['order_id']]['pickup_time'][1] : '';
                            break;
                        default:
                            $lineData[$k] = $value[$k] ?? '--';
                            break;
                    }
                }
                $orderList[] = $lineData;
            }
//            dd($orderList);
            yield $orderList;
        }
    }

    private function getListsBak($filter, $count, $datapassBlock)
    {
        $title = $this->getTitle();
        $tradeState = [
            'PARTIAL_REFUND' => '部分退款',
            'FULL_REFUND' => '全额退款',
            'SUCCESS' => '支付完成',
        ];
        $orderClass = [
            'community' => '社区活动订单',
            'groups' => '拼团活动订单',
            'seckill' => '秒杀活动订单',
            'normal' => '普通订单',
            'drug' => '药品需求订单',
            'shopguide' => '代客下单订单',
            'pointsmall' => '积分商城订单',
            'bargain' => '砍价订单',
            'excard' => '兑换券订单',
            'shopadmin' => '门店订单',
        ];
        $payTypes = [
            'wxpay' => '微信支付',
            'wxpaypc' => '微信支付',
            'wxpayh5' => '微信支付',
            'wxpayjs' => '微信支付',
            'wxpayapp' => '微信支付',
            'wxpaypos' => '微信支付',
            'hfpay' => '微信支付',
            'adapay' => '微信支付',
            'alipay' => '支付宝',
            'alipayh5' => '支付宝',
            'alipayapp' => '支付宝',
            'alipaypos' => '支付宝',
            'point' => '积分支付',
            'deposit' => '余额支付',
            'pos' => '现金支付',
            'gat' => '关爱通支付',
            'offline' => '线下转账',
        ];
        $supplierOrderService = new SupplierOrderService();
        $distributorService = new DistributorService();
        $normalOrderService = new NormalOrderService();
        $orderDeliveryService = new OrderDeliveryService();

        $pageSize = 500;
        $orderBy = ['id' => 'DESC'];
        $totalPage = ceil($count / $pageSize);
        for ($page = 1; $page <= $totalPage; $page++) {
            $orderList = [];
            $rsOrder = $supplierOrderService->repository->getLists($filter, '*', $page, $pageSize, $orderBy);

            $deliveryInfo = [];
            $order_ids = array_column($rsOrder, 'order_id');
            $rsDelivery = $orderDeliveryService->ordersDeliveryRepository->getLists(['supplier_id' => $filter['supplier_id'], 'order_id' => $order_ids]);
            if ($rsDelivery) {
                foreach ($rsDelivery as $v) {
                    $deliveryInfo[$v['order_id']] = $v;
                }
            }

            $storeIds = array_column($rsOrder, 'distributor_id');
            if ($storeIds) {
                $sFilter = [
                    'company_id' => $filter['company_id'],
                    'distributor_id' => $storeIds,
                ];
                $storeList = $distributorService->getDistributorOriginalList($sFilter, 1, $pageSize);
                $storeData = array_column($storeList['list'], null, 'distributor_id');
            }

            foreach ($rsOrder as $value) {
                $order_id = $value['order_id'];
                // if ($datapassBlock) {
                //     $value['mobile'] = data_masking('mobile', (string) $value['mobile']);
                //     $value['receiver_name'] = data_masking('truename', (string) $value['receiver_name']);
                //     $value['receiver_mobile'] = data_masking('mobile', (string) $value['receiver_mobile']);
                //     $value['receiver_address'] = data_masking('address', (string) $value['receiver_address']);
                // }
                $lineData = [];

                if (isset($deliveryInfo[$order_id])) {
                    $value['delivery_corp'] = $deliveryInfo[$order_id]['delivery_corp'] ?? '';
                    $value['delivery_code'] = $deliveryInfo[$order_id]['delivery_code'] ?? '';
                    $value['delivery_time'] = $deliveryInfo[$order_id]['delivery_time'] ?? '';
                    if ($value['delivery_time']) {
                        $value['delivery_time'] = date('Y-m-d H:i:s', $value['delivery_time']);
                    }
                }

                $discountDesc = '';
                if ($value['discount_info']) {
                    $discountInfo = json_decode($value['discount_info'], true);
                    foreach ($discountInfo as $vv) {
                        $a = $this->getDiscountDesc($vv);
                        if ($a) {
                            $discountDesc .= $a;
                        }
                    }
                }

                foreach ($title as $k => $v) {
                    if (!isset($value[$k])) {
                        $lineData[$k] = '--';
                        continue;
                    }
                    if (!$value[$k]) {
                        $lineData[$k] = $value[$k];
                        continue;
                    }
                    switch ($k) {
                        case 'order_id':
                        case 'tradeId':
                            $lineData[$k] = "'".$value[$k]."'";
                            break;
                        case 'commission_fee':
                        case 'total_fee':
                        case 'freight_fee':
                        case 'item_fee':
                            $lineData[$k] = $value[$k] / 100;
                            break;
                        case 'create_time':
                        case 'timeExpire':
                            $lineData[$k] = date('Y-m-d H:i:s', $value[$k]);
                            break;
                        case 'store_name':
                            $lineData[$k] = $storeData[$value['distributor_id']]['name'] ?? '';
                            break;
                        case 'tradeState':
                            $lineData[$k] = $tradeState[$value[$k]] ?? $value[$k];
                            break;
                        case 'order_class':
                            $lineData[$k] = $orderClass[$value[$k]] ?? $value[$k];
                            break;
                        case 'pay_type':
                            $lineData[$k] = $payTypes[$value[$k]] ?? $value[$k];
                            break;
                        case 'discount_info':
                            $lineData[$k] = $discountDesc;
                            break;
                        case 'order_status':
                            $lineData[$k] = $this->getOrderStatusMsg($value);
                            break;
                        case 'delivery_status':
                            $delivery_status = $value[$k];
                            switch ($delivery_status) {
                                case 'DONE':
                                    $delivery_status = '已发货';
                                    break;
                                case 'PARTAIL':
                                    $delivery_status = '部分发货';
                                    break;
                                default:
                                    $delivery_status = '未发货';
                            }
                            $lineData[$k] = $delivery_status;
                            break;
                        default:
                            $lineData[$k] = $value[$k] ?? '--';
                            break;
                    }
                }
                $orderList[] = $lineData;
            }
//            dd($orderList);
            yield $orderList;
        }
    }

    private function getDiscountDesc($value)
    {
        if (!isset($value['type'])) {
            return '';
        }
        if ($value['discount_fee'] <= 0) {
            return '';
        }
        $value['discount_fee'] = bcdiv($value['discount_fee'], 100, 2)."元";
        switch ($value['type']) {
            case "full_gift":
                $value['rule'] = $value['rule'] ?? '';
                $value['rule'] = str_replace(',', ' ', $value['rule']);
                $discountDesc = "满赠：". $value['rule'] ."; ";
                break;
            case "full_discount":
                $discountDesc = "满折：".$value['discount_fee']."; ";
                break;
            case "full_minus":
                $discountDesc = "满减：".$value['discount_fee']."; ";
                break;
            case "coupon_discount":
                $discountDesc = "折扣优惠券：".$value['discount_fee']."; ";
                break;
            case "cash_discount":
                $discountDesc = "代金优惠券：".$value['discount_fee']."; ";
                break;
            case "limited_time_sale":
                $discountDesc = "限时特惠：".$value['discount_fee']."; ";
                break;
            case "seckill":
                $discountDesc = "秒杀：".$value['discount_fee']."; ";
                break;
            case "groups":
                $discountDesc = "拼团：".$value['discount_fee']."; ";
                break;
            case "member_price":
                $discountDesc = "会员价：".$value['discount_fee']."; ";
                break;
            case "member_tag_targeted_promotio：":
                $discountDesc = "定向促销：".$value['discount_fee']."; ";
                break;
            default:
                $discountDesc = '';
                break;
        }
        return $discountDesc;
    }

    public function getOrderStatusMsg(&$order)
    {
        switch ($order['order_status']) {
            case "WAIT_GROUPS_SUCCESS":
                $statusMsg = '等待成团';
                break;
            case "NOTPAY":
                $statusMsg = '待支付';
                break;
            case "WAIT_PAID_CONFIRM":
                $statusMsg = '支付待确认';
                break;
            case "PAYED":
                if ($order['delivery_status'] == 'PARTAIL') {
                    $statusMsg = '部分发货';
                } elseif ($order['delivery_status'] == 'DONE') {
                    $statusMsg = '待收货';
                } else {
                    $statusMsg = '待发货';
                }
                break;
            case 'REVIEW_PASS':
                if ($order['delivery_status'] == 'PARTAIL') {
                    $statusMsg = '部分出库';
                } else {
                    $statusMsg = '审核完成,待出库';
                }
                break;
            case "CANCEL":
                if ($order['delivery_status'] == 'DONE') {
                    $statusMsg = '已关闭';
                } else {
                    $statusMsg = '已取消';
                }
                break;
            case "WAIT_BUYER_CONFIRM":
                $statusMsg = '待收货';
                break;
            case "DONE":
                $statusMsg = '已完成';
                break;
            case "REFUND_PROCESS":
                $statusMsg = '退款处理中';
                break;
            case "REFUND_SUCCESS":
                $statusMsg = '已退款';
                break;
            case "PART_PAYMENT":
                $statusMsg = '部分付款';
                break;
            default:
                $statusMsg = '订单异常:' . $order['order_status'];
                break;
        }
        return $statusMsg;
    }
}
