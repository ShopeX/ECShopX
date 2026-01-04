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

namespace EspierBundle\Services\Export;

use CommunityBundle\Services\CommunityActivityService;
use CommunityBundle\Services\CommunityOrderRelActivityService;
use EspierBundle\Interfaces\ExportFileInterface;
use OrdersBundle\Services\OrderAssociationService;
use OrdersBundle\Services\TradeService;
use OrdersBundle\Traits\GetOrderServiceTrait;
use EspierBundle\Services\ExportFileService;
use MembersBundle\Services\MemberService;
use DistributionBundle\Services\DistributorService;

use AftersalesBundle\Services\AftersalesService;
use PopularizeBundle\Services\BrokerageService;
use OrdersBundle\Services\NormalOrdersRelZitiService;
use CompanysBundle\Services\OperatorsService;
use SalespersonBundle\Services\SalespersonService;
use SupplierBundle\Services\SupplierService;
use OrdersBundle\Traits\GetOrderIdTrait;

class NormalMasterOrderExportService implements ExportFileInterface
{
    use GetOrderServiceTrait;
    use GetOrderIdTrait;

    protected $order_class = '';

    public function exportData($filter)
    {
        // 是否需要数据脱敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        // 数云模式
        if (config('common.oem-shuyun')) {
            // 数云模式，根据推广员身份、推广员手机号，进行查询
            $filter = $this->getOrderIdByPromoter($filter);
        }
        $orderService = $this->getOrderService('normal');
        $count = $orderService->countOrderNum($filter);
        if (!$count) {
            return [];
        }

        $this->order_class = $filter['order_class'] ?? '';

        if (isset($filter['order_class']) && $filter['order_class'] == 'pointsmall') {
            $fileName = date('YmdHis') . $filter['company_id'] . "master积分商城";
            $title = $this->getPointsmallTitle();
            $dataList = $this->getPointsmallLists($filter, $count, $datapassBlock);
        } else {
            $fileName = date('YmdHis') . $filter['company_id'] . "master";
            if (isset($filter['supplier_id']) && $filter['supplier_id'] > 0) {
                $title = $this->getSupplierTitle();
                $dataList = $this->getSupplierLists($filter, $count, $datapassBlock);
            } else {
                $title = $this->getTitle();
                $dataList = $this->getLists($filter, $count, $datapassBlock);
            }
        }

        $exportService = new ExportFileService();
        // 指定需要作为文本处理的数字字段，避免 Excel 显示为科学计数法
        $textFields = ['order_id', 'original_order_id'];
        $result = $exportService->exportCsv($fileName, $title, $dataList, $textFields);

        return $result;
    }

    private function getTitle()
    {
        $title = [
            'order_id' => '订单号',//
            'name' => '用户名',
            'create_time' => '下单时间',//
            'order_holder' => '订单分类',
            'total_fee_total' => '订单总金额(¥)',//
            'total_fee' => '现金实付(¥)',//
            'point_fee' => '积分抵扣',
            'cost_fee' => '成本价(¥)',
            'freight_fee' => '运费(总)',//
            'commission_fee' => '佣金(总)',//
            'discount_fee' => '优惠金额',
            'discount_info' => '优惠详情',
            'order_class' => '订单类型',
            'order_status' => '订单状态',//
            'end_time' => '订单完成时间',
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
            'pay_time' => '支付时间',
            'invoice' => '发票内容',
            'remark' => '订单备注',
            'pickup_address' => '自提地址',
            'pickup_datetime' => '提货时间',
            'source_from' => '订单来源',
            'purchase_type' => '角色',
            'employee_name' => '员工姓名',
            'enterprise_name' => '所属企业',
        ];

        return $title;
    }

    private function getTitleShuyun()
    {
        $title = [
            'order_id' => '订单号',//
            'name' => '用户名称',
            'create_time' => '下单时间',//
            'order_holder' => '订单分类',
            'total_fee_total' => '订单总金额(¥)',//
            'total_fee' => '现金实付(¥)',//
            'point_fee' => '积分抵扣',
            'cost_fee' => '成本价(¥)',
            'freight_fee' => '运费(总)',//
            'commission_fee' => '佣金(总)',//
            'discount_fee' => '优惠金额',
            'discount_info' => '优惠详情',
            'order_class' => '订单类型',
            'order_status' => '订单状态',//
            'end_time' => '订单完成时间',
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
            'pay_time' => '支付时间',
            'invoice' => '发票内容',
            // 'promoter_user_mobile' => '推广员手机号',
            'promoter_name' => '推广员',
            'promoter_mobile' => '推广员手机号',
            // 'rebate' => '分佣金额',
            'order_total_rebate' => '总佣金（¥）',
            'order_first_rebate' => '销售佣金（¥）',
            'order_second_rebate' => '推荐人佣金（¥）',
            'promoter_identity' => '推广员身份',
            'p_promoter_name' => '上级推广员',
            'p_promoter_mobile' => '上级推广员手机号',
            'promoter_is_close' => '是否结算',
            'remark' => '订单备注',
            'pickup_address' => '自提地址',
            'pickup_datetime' => '提货时间',
            'source_from' => '订单来源',
            'purchase_type' => '角色',
            'employee_name' => '员工姓名',
            'enterprise_name' => '所属企业',
        ];

        return $title;
    }

    public function getLists($filter, $totalCount = 10000, $datapassBlock)
    {
        // 数云模式
        if (config('common.oem-shuyun')) {
            $title = $this->getTitleShuyun();
        } else {
            $title = $this->getTitle();
        }
        $limit = 2000;
        $fileNum = ceil($totalCount / $limit);
        $memberService = new MemberService();
        $distributorService = new DistributorService();
        $brokerageService = new BrokerageService();
        $orderAssociationService = new OrderAssociationService();
        $ordersRelZitiService = new NormalOrdersRelZitiService();
        $orderService = $this->getOrderService('normal');

        $orderBy = ['distributor_id' => 'desc', 'order_id' => 'desc', 'create_time' => 'asc'];
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
            'employee_purchase' => '内购订单',
        ];
        $aftersales_status = [
            'WAIT_SELLER_AGREE' => '等待商家处理',
            'WAIT_BUYER_RETURN_GOODS' => '商家接受申请，等待消费者回寄',
            'WAIT_SELLER_CONFIRM_GOODS' => '消费者回寄，等待商家收货确认',
            'SELLER_REFUSE_BUYER' => '售后驳回',
            'SELLER_SEND_GOODS' => '卖家重新发货 换货完成',
            'REFUND_SUCCESS' => '退款成功',
            'REFUND_CLOSED' => '退款关闭',
            'CLOSED' => '售后关闭',
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
            'offline_pay' => '线下支付',
        ];
        $receiptType = [
            'merchant' => '商家自配',
            'logistics' => '快递配送',
            'ziti' => '上门自提',
            'dada' => '同城配',
        ];
        $purchaseType = [
            'employee' => '员工',
            'relative' => '亲友',
        ];
        $orderHolder = [
            'self' => '自营订单',
            'distributor' => '商家订单',
            'supplier' => '供应商订单',
            'self_supplier' => '自营和供应商订单',
            'distributor_supplier' => '商家和供应商订单'
        ];
        $communityOrderRelService = new CommunityOrderRelActivityService();
        $communityActivityService = new CommunityActivityService();
        $orderList = [];
        $tradeService = new TradeService();
        for ($j = 1; $j <= $fileNum; $j++) {
            $orderdata = $orderService->getOrderList($filter, $j, $limit, $orderBy, false)['list'];

            $orderIdList = array_column($orderdata, 'order_id');
            $tradeList = $tradeService->getSuccTradeInfoByOrderIdList($filter['company_id'], $orderIdList);

            $communityOrderRelData = [];
            if ($this->order_class == 'community' && !empty($orderIdList)) {
                $communityOrderRelData = $communityOrderRelService->getLists(['order_id' => $orderIdList]);
                $communityActivityData = [];
                $activityIds = array_values(array_unique(array_column($communityOrderRelData, 'activity_id')));
                if (!empty($activityIds)) {
                    $communityActivityData = $communityActivityService->getLists(['activity_id' => $activityIds]);
                    $communityActivityData = array_column($communityActivityData, null, 'activity_id');
                }
                $communityOrderRelData = array_column($communityOrderRelData, null, 'order_id');
                foreach ($communityOrderRelData as $key => $value) {
                    if (isset($communityActivityData[$value['activity_id']])) {
                        $communityOrderRelData[$key]['activity_data'] = $communityActivityData[$value['activity_id']];
                    }
                }
            }

            $userIds = array_column($orderdata, 'user_id');
            if ($userIds) {
                $uFilter = [
                    'company_id' => $filter['company_id'],
                    'user_id' => $userIds,
                ];
                $userList = $memberService->getMemberInfoList($uFilter, 1, $limit);
                $userData = array_column($userList['list'], null, 'user_id');
            }

            $storeIds = array_column($orderdata, 'distributor_id');
            if ($storeIds) {
                $sFilter = [
                    'company_id' => $filter['company_id'],
                    'distributor_id' => $storeIds,
                ];
                $storeList = $distributorService->getDistributorOriginalList($sFilter, 1, $limit);
                $storeData = array_column($storeList['list'], null, 'distributor_id');
            }

            $supplierIds = array_column($orderdata, 'supplier_id');
            if ($supplierIds) {
                $operatorsService = new OperatorsService();
                $supplierList = $operatorsService->lists(['company_id' => $filter['company_id'], 'operator_id' => $supplierIds, 'operator_type' => 'supplier']);
                $supplierData = array_column($supplierList['list'], null, 'operator_id');
            }

            $zitiList = $ordersRelZitiService->getLists(['order_id' => $orderIdList]);
            $zitiData = array_column($zitiList, null, 'order_id');

            $orderList = [];
            foreach ($orderdata as $newData) {
                $disountFee = 0;
                if ($newData['member_discount'] && $newData['coupon_discount']) {
                    $disountFee = ((int)$newData['member_discount'] + (int)$newData['coupon_discount']);
                } else {
                    $disountFee = (int)$newData['discount_fee'];
                }
                $discountDesc = '';
                if ($newData['discount_info']) {
                    $discountInfo = json_decode($newData['discount_info'], true);
                    foreach ($discountInfo as $value) {
                        $a = $this->getDiscountDesc($value);
                        if ($a) {
                            $discountDesc .= $a;
                        }
                    }
                }

                $invoice = [];
                if (isset($newData['invoice'])) {
                    $invoicearr = is_array($newData['invoice']) ? $newData['invoice'] : json_decode($newData['invoice'], true);
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
//
//                $orderAssInfo = $orderAssociationService->getOrder($filter['company_id'], $newData['order_id']);
//                $bFilter = [
//                    'company_id' => $filter['company_id'],
//                    'user_id' => $orderAssInfo['promoter_user_id'],
//                    'order_id' => $newData['order_id'],
//                    'brokerage_type' => 'first_level',
//                ];
//                $brokerageInfo = $brokerageService->getInfo($bFilter);
//                $user_mobile = $memberService->getMobileByUserId($orderAssInfo['promoter_user_id'], $filter['company_id']);

                $payType = $newData['pay_type'] ?? '';
                $username = $userData[$newData['user_id']]['username'] ?? '';
                if ($datapassBlock) {
                    $newData['mobile'] = data_masking('mobile', (string) $newData['mobile']);
                    $username = data_masking('truename', (string) $username);
                    $newData['receiver_name'] = data_masking('truename', (string) $newData['receiver_name']);
                    $newData['receiver_mobile'] = data_masking('mobile', (string) $newData['receiver_mobile']);
                    $newData['receiver_address'] = data_masking('address', (string) $newData['receiver_address']);
                }
                // 数云模式
                if (config('common.oem-shuyun')) {
                    $newData['p_promoter_mobile'] = $newData['promoter_info']['p_promoter_mobile'] ?? '';
                    if ($newData['p_promoter_mobile'] == "" || $newData['p_promoter_mobile'] == '0') {
                        $newData['p_promoter_mobile'] = '--';
                    }
                    $newData['promoter_name'] = $newData['promoter_info']['promoter_name'] ?? '--';
                    $newData['promoter_mobile'] = $newData['promoter_info']['promoter_mobile'] ?? '--';
                    $newData['order_total_rebate'] = ($newData['promoter_info']['order_total_rebate'] ?? '') == '' ? '-' :bcdiv($newData['promoter_info']['order_total_rebate'], 100, 2);
                    $newData['order_first_rebate'] = ($newData['promoter_info']['order_first_rebate'] ?? '') == '' ? '-' : bcdiv($newData['promoter_info']['order_first_rebate'], 100, 2);
                    $newData['order_second_rebate'] = ($newData['promoter_info']['order_second_rebate'] ?? '') == '' ? '-' : bcdiv($newData['promoter_info']['order_second_rebate'], 100, 2);
                    $newData['promoter_identity'] = $newData['promoter_info']['promoter_identity'] ?? '-';
                    $newData['p_promoter_name'] = $newData['promoter_info']['p_promoter_name'] ?? '-';
                    $newData['p_promoter_mobile'] = $newData['p_promoter_mobile'];
                    $newData['promoter_is_close'] = ($newData['promoter_info']['promoter_is_close'] ?? 0) == 1 ? '已结算' : '未结算';
                }
                // 内购新增字段
                $newData['purchase_type'] = $newData['orders_purchase_info']['type'] ?? '';
                $newData['employee_name'] = $newData['orders_purchase_info']['employee_name'] ?? '';
                $newData['enterprise_name'] = $newData['orders_purchase_info']['enterprise_name'] ?? '';
                $orderItem = [];
                foreach ($title as $colName => $label) {
                    // if (!isset($newData[$colName])) {
                    //     $orderItem[$colName] = '--';
                    //     continue;
                    // }
                    switch ($colName) {
                        case 'order_id':
                            // 直接赋值，不再添加引号，由 ExportFileService 统一处理
                            $orderItem[$colName] = $newData[$colName];
                            break;
                        case 'name':
                            $orderItem[$colName]  = $userData[$newData['user_id']]['name'] ?? '';
                            break;
                        case 'create_time':
                            $orderItem[$colName] = date('Y-m-d H:i:s', $newData['create_time']);
                            break;
                        case 'order_holder':
                            $orderItem[$colName] = $orderHolder[$newData['order_holder']] ?? '未知订单类型';
                            break;
                        case 'total_fee_total':
                            $orderItem[$colName] = bcdiv($newData['item_fee'] + $newData['freight_fee'], 100, 2);
                            break;
                        case 'total_fee':
                            if ($payType == 'dhpoint') {
                                $orderItem[$colName] = $newData['total_fee'];
                            } else {
                                $orderItem[$colName] = bcdiv($newData['total_fee'], 100, 2);
                            }
                            break;
                        case 'point_fee':
                        case 'cost_fee':
                        case 'freight_fee':
                        case 'commission_fee':
                            $orderItem[$colName] = bcdiv($newData[$colName], 100, 2);
                            break;
                        case 'discount_fee':
                            $orderItem[$colName] = bcdiv($disountFee, 100, 2);
                            break;
                        case 'discount_info':
                            $orderItem[$colName] = $discountDesc;
                            break;
                        case 'order_class':
                            $orderItem[$colName] = $orderClass[$newData['order_class']];
                            break;
                        case 'order_status':
                            $orderItem[$colName] = $newData['order_status_msg'];
                            break;
                        case 'end_time':
                            $orderItem[$colName] = date('Y-m-d H:i:s', $newData['end_time']);
                            break;
                        case 'receipt_type':
                            $orderItem[$colName] = $receiptType[$newData['receipt_type']];
                            break;
                        case 'ziti_status':
                            $orderItem[$colName] = ($newData['ziti_status'] == 'DONE') ? '已自提' : '';
                            break;
                        case 'receiver_name':
                            $orderItem[$colName] = $this->clearSpecialChars($newData[$colName]);
                            break;
                        case 'receiver_mobile':
                            $orderItem[$colName] = $newData['receiver_mobile'];
                            break;
                        case 'receiver_zip':
                            $orderItem[$colName] = $newData['receiver_zip'];
                            break;
                        case'receiver_state':
                            $orderItem[$colName] = $newData['receiver_state'];
                            break;
                        case 'receiver_city':
                            $orderItem[$colName] = $newData['receiver_city'];
                            break;
                        case 'receiver_district':
                            $orderItem[$colName] = $newData['receiver_district'];
                            break;
                        case 'receiver_address':
                            $orderItem[$colName] = $this->clearSpecialChars($newData[$colName]);
                            break;
                        case 'delivery_status':
                            $orderItem[$colName] = ($newData['delivery_status'] == 'DONE') ? '已发货' : '未发货';
                            break;
                        case 'delivery_time':
                            $orderItem[$colName] = ($newData['delivery_status'] == 'DONE') ? date('Y-m-d H:i:s', $newData['delivery_time']) : '0';
                            break;
                        case 'delivery_code':
                            $orderItem[$colName] = ($newData['delivery_status'] == 'DONE') ? $newData['delivery_code']. "\t" : '';
                            break;
                        case 'delivery_corp':
                            $orderItem[$colName] = ($newData['delivery_status'] == 'DONE') ? $newData['delivery_corp'] : '';
                            break;
                        case 'pay_type':
                            $orderItem[$colName] = $payTypes[$payType] ?? $payType;
                            break;
                        case 'pay_time':
                            $orderItem[$colName] = isset($tradeList[$newData['order_id']]['time_expire']) ? date('Y-m-d H:i:s', $tradeList[$newData['order_id']]['time_expire']) : ''; // 支付时间
                            break;
                        case 'invoice':
                            $orderItem[$colName] = $invoiceStr;
                            break;
                        case 'remark':
                            $orderItem[$colName] = $newData['remark'];
                            break;
                        case 'pickup_address':
                            $orderItem[$colName] = isset($zitiData[$newData['order_id']]) ? $zitiData[$newData['order_id']]['province'].$zitiData[$newData['order_id']]['city'].$zitiData[$newData['order_id']]['area'].$zitiData[$newData['order_id']]['address'] : '';
                            break;
                        case 'pickup_datetime':
                            $orderItem[$colName] = isset($zitiData[$newData['order_id']]) ? $zitiData[$newData['order_id']]['pickup_date'].' '.$zitiData[$newData['order_id']]['pickup_time'][0].'~'.$zitiData[$newData['order_id']]['pickup_time'][1] : '';
                            break;
                        case 'source_from':
                            $orderItem[$colName] = $newData['source_from'] ?? '';
                            break;
                        case 'purchase_type':
                            $orderItem[$colName] = $purchaseType[$newData['purchase_type']] ?? '会员';
                            break;
                        case 'employee_name':
                            $orderItem[$colName] = $newData['employee_name'] ?? '';
                            break;
                        case 'enterprise_name':
                            $orderItem[$colName] = $newData['enterprise_name']?? '';
                            break;
                        case '':
                            break;
                        default:
                            $orderItem[$colName] = $newData[$colName] ?? '--';
                    }
                }
                $orderList[] = $orderItem;
            }
            yield $orderList;
        }
    }

    private function getTitleBak()
    {
        $title = [
            // 'original_order_id' => '原订单号',
            'order_id' => '订单号',//
            // 'supplier_name' => '所属供应商',
            // 'trade_no'=> '订单序号',//
            'item_fee' => '订单价格',
            // 'store_name' => '所属店铺',
            // 'store_code' => '店铺号',
            // 'mobile' => '会员手机号',//
            // 'user_name' => '会员昵称',
            'create_time' => '下单时间',//
            'freight_fee' => '运费(总)',//
            'total_fee' => '实付金额(总)',//
            'commission_fee' => '佣金(总)',//
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
            // 'subdistrict_parent' => '街道',
            // 'subdistrict' => '居委',
            // 'building_number' => '楼号',
            // 'house_number' => '房号',
            'delivery_status' => '发货状态',
            'delivery_time' => '发货时间',
            'delivery_code' => '快递单号',
            'delivery_corp' => '快递公司',
            'pay_type' => '支付方式',
            'invoice' => '发票内容',
            // 'promoter_user_mobile' => '推广员手机号',
            // 'rebate' => '分佣金额',
            'remark' => '订单备注',
            'pickup_address' => '自提地址',
            'pickup_datetime' => '提货时间',
            'source_from' => '订单来源',
            'purchase_type' => '角色',
            'employee_name' => '员工姓名',
            'enterprise_name' => '所属企业',
        ];
        if ($this->order_class == 'community') {
            $title['activity_status'] = '活动状态';
            $title['activity_delivery_status'] = '活动发货状态';
        }
        return $title;
    }

    public function getListsBak($filter, $totalCount = 10000, $datapassBlock)
    {
        $title = $this->getTitle();
        $limit = 2000;
        $fileNum = ceil($totalCount / $limit);
        $memberService = new MemberService();
        $distributorService = new DistributorService();
        $brokerageService = new BrokerageService();
        $orderAssociationService = new OrderAssociationService();
        $ordersRelZitiService = new NormalOrdersRelZitiService();
        $orderService = $this->getOrderService('normal');

        $orderBy = ['distributor_id' => 'desc', 'order_id' => 'desc', 'create_time' => 'asc'];
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
            'employee_purchase' => '内购订单',
        ];
        $aftersales_status = [
            'WAIT_SELLER_AGREE' => '等待商家处理',
            'WAIT_BUYER_RETURN_GOODS' => '商家接受申请，等待消费者回寄',
            'WAIT_SELLER_CONFIRM_GOODS' => '消费者回寄，等待商家收货确认',
            'SELLER_REFUSE_BUYER' => '售后驳回',
            'SELLER_SEND_GOODS' => '卖家重新发货 换货完成',
            'REFUND_SUCCESS' => '退款成功',
            'REFUND_CLOSED' => '退款关闭',
            'CLOSED' => '售后关闭',
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
            'offline_pay' => '线下支付',
        ];
        $receiptType = [
            'merchant' => '商家自配',
            'logistics' => '快递配送',
            'ziti' => '上门自提',
            'dada' => '同城配',
        ];
        $purchaseType = [
            'employee' => '员工',
            'relative' => '亲友',
        ];

        $communityOrderRelService = new CommunityOrderRelActivityService();
        $communityActivityService = new CommunityActivityService();
        $orderList = [];
        $tradeService = new TradeService();
        for ($j = 1; $j <= $fileNum; $j++) {
            $orderdata = $orderService->getOrderList($filter, $j, $limit, $orderBy, false)['list'];

            $orderIdList = array_column($orderdata, 'order_id');
            $tradeIndex = $tradeService->getTradeIndexByOrderIdList($filter['company_id'], $orderIdList);

            $communityOrderRelData = [];
            if ($this->order_class == 'community' && !empty($orderIdList)) {
                $communityOrderRelData = $communityOrderRelService->getLists(['order_id' => $orderIdList]);
                $communityActivityData = [];
                $activityIds = array_values(array_unique(array_column($communityOrderRelData, 'activity_id')));
                if (!empty($activityIds)) {
                    $communityActivityData = $communityActivityService->getLists(['activity_id' => $activityIds]);
                    $communityActivityData = array_column($communityActivityData, null, 'activity_id');
                }
                $communityOrderRelData = array_column($communityOrderRelData, null, 'order_id');
                foreach ($communityOrderRelData as $key => $value) {
                    if (isset($communityActivityData[$value['activity_id']])) {
                        $communityOrderRelData[$key]['activity_data'] = $communityActivityData[$value['activity_id']];
                    }
                }
            }

            $userIds = array_column($orderdata, 'user_id');
            if ($userIds) {
                $uFilter = [
                    'company_id' => $filter['company_id'],
                    'user_id' => $userIds,
                ];
                $userList = $memberService->getMemberInfoList($uFilter, 1, $limit);
                $userData = array_column($userList['list'], null, 'user_id');
            }

            $storeIds = array_column($orderdata, 'distributor_id');
            if ($storeIds) {
                $sFilter = [
                    'company_id' => $filter['company_id'],
                    'distributor_id' => $storeIds,
                ];
                $storeList = $distributorService->getDistributorOriginalList($sFilter, 1, $limit);
                $storeData = array_column($storeList['list'], null, 'distributor_id');
            }

            $supplierIds = array_column($orderdata, 'supplier_id');
            if ($supplierIds) {
                $supplierService = new SupplierService();
                $supplierList = $supplierService->lists(['company_id' => $filter['company_id'], 'operator_id' => $supplierIds]);
                $supplierData = array_column($supplierList['list'], null, 'operator_id');
            }

            $zitiList = $ordersRelZitiService->getLists(['order_id' => $orderIdList]);
            $zitiData = array_column($zitiList, null, 'order_id');

            $orderList = [];
            foreach ($orderdata as $newData) {
                $disountFee = 0;
                if ($newData['member_discount'] && $newData['coupon_discount']) {
                    $disountFee = ((int)$newData['member_discount'] + (int)$newData['coupon_discount']);
                } else {
                    $disountFee = (int)$newData['discount_fee'];
                }
                $discountDesc = '';
                if ($newData['discount_info']) {
                    $discountInfo = json_decode($newData['discount_info'], true);
                    foreach ($discountInfo as $value) {
                        $a = $this->getDiscountDesc($value);
                        if ($a) {
                            $discountDesc .= $a;
                        }
                    }
                }
                $invoice = [];
                if (isset($newData['invoice'])) {
                    $invoicearr = is_array($newData['invoice']) ? $newData['invoice'] : json_decode($newData['invoice'], true);
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
                $invoiceStr = $invoice ? implode('; ', $invoice) : '---无---';

                // $orderAssInfo = $orderAssociationService->getOrder($filter['company_id'], $newData['order_id']);

                // $bFilter = [
                //     'company_id' => $filter['company_id'],
                //     'user_id' => $orderAssInfo['promoter_user_id'],
                //     'order_id' => $newData['order_id'],
                //     'brokerage_type' => 'first_level',
                // ];
                // $brokerageInfo = $brokerageService->getInfo($bFilter);

                // $user_mobile = $memberService->getMobileByUserId($orderAssInfo['promoter_user_id'], $filter['company_id']);

                $payType = $newData['pay_type'] ?? '';
                $username = $userData[$newData['user_id']]['username'] ?? '';
                if ($datapassBlock) {
                    $newData['mobile'] = data_masking('mobile', (string) $newData['mobile']);
                    $username = data_masking('truename', (string) $username);
                    $newData['receiver_name'] = data_masking('truename', (string) $newData['receiver_name']);
                    $newData['receiver_mobile'] = data_masking('mobile', (string) $newData['receiver_mobile']);
                    $newData['receiver_address'] = data_masking('address', (string) $newData['receiver_address']);
                }
                // 内购新增字段
                $newData['purchase_type'] = $newData['orders_purchase_info']['type'] ?? '';
                $newData['employee_name'] = $newData['orders_purchase_info']['employee_name'] ?? '';
                $newData['enterprise_name'] = $newData['orders_purchase_info']['enterprise_name'] ?? '';
                $orderItem = [];
                foreach ($title as $colName => $label) {
                    if (!isset($newData[$colName])) {
                        $orderItem[$colName] = '--';
                        continue;
                    }
                    switch ($colName) {
                        case 'order_id':
                            // 直接赋值，不再添加引号，由 ExportFileService 统一处理
                            $orderItem[$colName] = $newData[$colName];
                            break;
                        case 'supplier_name':
                            $orderItem[$colName] = $supplierData[$newData['supplier_id']]['supplier_name'] ?? '';
                            break;
                        case 'commission_fee':
                        case 'freight_fee':
                        case 'item_fee':
                            $orderItem[$colName] = bcdiv($newData[$colName], 100, 2);
                            break;
                        case 'create_time':
                            $orderItem[$colName] = $newData['create_date'];
                            break;
                        // case 'store_name':
                        //     $orderItem[$colName] = $storeData[$newData['distributor_id']]['name'] ?? '';
                        //     break;
                        // case 'store_code':
                        //     $orderItem[$colName] = $storeData[$newData['distributor_id']]['shop_code'] ?? '';
                        //     break;
                        case 'total_fee':
                            if ($payType == 'dhpoint') {
                                $orderItem[$colName] = $newData['total_fee'];
                            } else {
                                $orderItem[$colName] = bcdiv($newData['total_fee'], 100, 2);
                            }
                            break;
                        case 'discount_fee':
                            $orderItem[$colName] = bcdiv($disountFee, 100, 2);
                            break;
                        case 'discount_info':
                            $orderItem[$colName] = $discountDesc;
                            break;
                        case 'order_class':
                            $orderItem[$colName] = $orderClass[$newData['order_class']];
                            break;
                        case 'order_status':
                            $orderItem[$colName] = $newData['order_status_msg'];
                            break;
                        case 'receipt_type':
                            $orderItem[$colName] = $receiptType[$newData['receipt_type']];
                            break;
                        case 'ziti_status':
                            $orderItem[$colName] = ($newData['ziti_status'] == 'DONE') ? '已自提' : '';
                            break;
                        case 'receiver_name':
                        case 'receiver_address':
                            $orderItem[$colName] = $this->clearSpecialChars($newData[$colName]);
                            break;
                        case 'delivery_status':
                            $orderItem[$colName] = ($newData['delivery_status'] == 'DONE') ? '已发货' : '未发货';
                            break;
                        case 'delivery_time':
                            $orderItem[$colName] = ($newData['delivery_status'] == 'DONE') ? date('Y-m-d H:i:s', $newData['delivery_time']) : '0';
                            break;
                        case 'delivery_code':
                            $orderItem[$colName] = ($newData['delivery_status'] == 'DONE') ? $newData['delivery_code']. "\t" : '';
                            break;
                        case 'delivery_corp':
                            $orderItem[$colName] = ($newData['delivery_status'] == 'DONE') ? $newData['delivery_corp'] : '';
                            break;
                        case 'pay_type':
                            $orderItem[$colName] = $payTypes[$payType] ?? $payType;
                            break;
                        case 'invoice':
                            $orderItem[$colName] = $invoiceStr;
                            break;
                        case 'pickup_address':
                            $orderItem[$colName] = isset($zitiData[$newData['order_id']]) ? $zitiData[$newData['order_id']]['province'].$zitiData[$newData['order_id']]['city'].$zitiData[$newData['order_id']]['area'].$zitiData[$newData['order_id']]['address'] : '';
                            break;
                        case 'pickup_datetime':
                            $orderItem[$colName] = isset($zitiData[$newData['order_id']]) ? $zitiData[$newData['order_id']]['pickup_date'].' '.$zitiData[$newData['order_id']]['pickup_time'][0].'~'.$zitiData[$newData['order_id']]['pickup_time'][1] : '';
                            break;
                        case 'purchase_type':
                            $orderItem[$colName] = $purchaseType[$newData['purchase_type']] ?? '会员';
                            break;
                        default:
                            $orderItem[$colName] = $newData[$colName] ?? '--';
                    }
                }

                if ($this->order_class == 'community') {
                    $orderItem['activity_status'] = '';
                    $orderItem['activity_delivery_status'] = '';
                    if (isset($communityOrderRelData[$newData['order_id']]['activity_data'])) {
                        $activity_status = $communityOrderRelData[$newData['order_id']]['activity_data']['activity_status'] ?? '';
                        $activity_delivery_status = $communityOrderRelData[$newData['order_id']]['activity_data']['delivery_status'] ?? '';
                        $orderItem['activity_status'] = CommunityActivityService::activity_status[$activity_status] ?? '';
                        $orderItem['activity_delivery_status'] = CommunityActivityService::activity_delivery_status[$activity_delivery_status] ?? '';
                    }
                }
                $orderList[] = $orderItem;
            }
            yield $orderList;
        }
    }

    private function getSupplierTitle()
    {
        $title = [
            'original_order_id' => '原订单号',
            'order_id' => '订单号',//
            'supplier_name' => '所属供应商',
            // 'trade_no'=> '订单序号',//
            'cost_fee' => '结算金额',
            'store_name' => '所属店铺',
            'store_code' => '店铺号',
            'mobile' => '会员手机号',//
            'user_name' => '会员昵称',
            'create_time' => '下单时间',//
            'freight_fee' => '运费(总)',//
            // 'total_fee' => '实付金额(总)',//
            // 'discount_fee' => '优惠金额',
            // 'discount_info' => '优惠详情',
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
            // 'subdistrict_parent' => '街道',
            // 'subdistrict' => '居委',
            // 'building_number' => '楼号',
            // 'house_number' => '房号',
            'delivery_status' => '发货状态',
            'delivery_time' => '发货时间',
            'delivery_code' => '快递单号',
            'delivery_corp' => '快递公司',
            'pay_type' => '支付方式',
            // 'invoice' => '发票内容',
            'promoter_user_mobile' => '推广员手机号',
            // 'rebate' => '分佣金额',
            'remark' => '订单备注',
            'pickup_address' => '自提地址',
            'pickup_datetime' => '提货时间',
            'source_from' => '订单来源',
            'is_salesman_order' => '是业务员订单',
        ];
        if ($this->order_class == 'community') {
            $title['activity_status'] = '活动状态';
            $title['activity_delivery_status'] = '活动发货状态';
        }
        return $title;
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

    public function clearSpecialChars($str)
    {
        if (!$str) {
            return $str;
        }
        return str_replace(["\r", "\n", ','], ' ', $str);
    }

    public function getSupplierLists($filter, $totalCount = 10000, $datapassBlock)
    {
        $limit = 2000;
        $fileNum = ceil($totalCount / $limit);
        $memberService = new MemberService();
        $distributorService = new DistributorService();
        $brokerageService = new BrokerageService();
        $orderAssociationService = new OrderAssociationService();
        $ordersRelZitiService = new NormalOrdersRelZitiService();
        $orderService = $this->getOrderService('normal');

        $orderBy = ['distributor_id' => 'desc', 'order_id' => 'desc', 'create_time' => 'asc'];
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
        $aftersales_status = [
            'WAIT_SELLER_AGREE' => '等待商家处理',
            'WAIT_BUYER_RETURN_GOODS' => '商家接受申请，等待消费者回寄',
            'WAIT_SELLER_CONFIRM_GOODS' => '消费者回寄，等待商家收货确认',
            'SELLER_REFUSE_BUYER' => '售后驳回',
            'SELLER_SEND_GOODS' => '卖家重新发货 换货完成',
            'REFUND_SUCCESS' => '退款成功',
            'REFUND_CLOSED' => '退款关闭',
            'CLOSED' => '售后关闭',
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
        ];
        $receiptType = ['logistics' => '快递配送', 'ziti' => '上门自提', 'dada' => '同城配'];

        $communityOrderRelService = new CommunityOrderRelActivityService();
        $communityActivityService = new CommunityActivityService();
        $orderList = [];
        $tradeService = new TradeService();
        for ($j = 1; $j <= $fileNum; $j++) {
            $orderdata = $orderService->getOrderList($filter, $j, $limit, $orderBy, false)['list'];

            $orderIdList = array_column($orderdata, 'order_id');
            $tradeIndex = $tradeService->getTradeIndexByOrderIdList($filter['company_id'], $orderIdList);

            $communityOrderRelData = [];
            if ($this->order_class == 'community' && !empty($orderIdList)) {
                $communityOrderRelData = $communityOrderRelService->getLists(['order_id' => $orderIdList]);
                $communityActivityData = [];
                $activityIds = array_values(array_unique(array_column($communityOrderRelData, 'activity_id')));
                if (!empty($activityIds)) {
                    $communityActivityData = $communityActivityService->getLists(['activity_id' => $activityIds]);
                    $communityActivityData = array_column($communityActivityData, null, 'activity_id');
                }
                $communityOrderRelData = array_column($communityOrderRelData, null, 'order_id');
                foreach ($communityOrderRelData as $key => $value) {
                    if (isset($communityActivityData[$value['activity_id']])) {
                        $communityOrderRelData[$key]['activity_data'] = $communityActivityData[$value['activity_id']];
                    }
                }
            }

            $userIds = array_column($orderdata, 'user_id');
            if ($userIds) {
                $uFilter = [
                    'company_id' => $filter['company_id'],
                    'user_id' => $userIds,
                ];
                $userList = $memberService->getMemberInfoList($uFilter, 1, $limit);
                $userData = array_column($userList['list'], null, 'user_id');
            }

            $storeIds = array_column($orderdata, 'distributor_id');
            if ($storeIds) {
                $sFilter = [
                    'company_id' => $filter['company_id'],
                    'distributor_id' => $storeIds,
                ];
                $storeList = $distributorService->getDistributorOriginalList($sFilter, 1, $limit);
                $storeData = array_column($storeList['list'], null, 'distributor_id');
            }

            $supplierIds = array_column($orderdata, 'supplier_id');
            if ($supplierIds) {
                $operatorsService = new OperatorsService();
                $supplierList = $operatorsService->lists(['company_id' => $filter['company_id'], 'operator_id' => $supplierIds, 'operator_type' => 'supplier']);
                $supplierData = array_column($supplierList['list'], null, 'operator_id');
            }

            $zitiList = $ordersRelZitiService->getLists(['order_id' => $orderIdList]);
            $zitiData = array_column($zitiList, null, 'order_id');

            $orderList = [];
            foreach ($orderdata as $newData) {
                $disountFee = 0;
                if ($newData['member_discount'] && $newData['coupon_discount']) {
                    $disountFee = ((int)$newData['member_discount'] + (int)$newData['coupon_discount']);
                } else {
                    $disountFee = (int)$newData['discount_fee'];
                }
                $discountDesc = '';
                if ($newData['discount_info']) {
                    $discountInfo = json_decode($newData['discount_info'], true);
                    foreach ($discountInfo as $value) {
                        $a = $this->getDiscountDesc($value);
                        if ($a) {
                            $discountDesc .= $a;
                        }
                    }
                }
                $invoice = [];
                if (isset($newData['invoice'])) {
                    $invoicearr = is_array($newData['invoice']) ? $newData['invoice'] : json_decode($newData['invoice'], true);
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
                $invoiceStr = $invoice ? implode('; ', $invoice) : '---无---';

                $orderAssInfo = $orderAssociationService->getOrder($filter['company_id'], $newData['order_id']);

                $bFilter = [
                    'company_id' => $filter['company_id'],
                    'user_id' => $orderAssInfo['promoter_user_id'],
                    'order_id' => $newData['order_id'],
                    'brokerage_type' => 'first_level',
                ];
                $brokerageInfo = $brokerageService->getInfo($bFilter);

                $user_mobile = $memberService->getMobileByUserId($orderAssInfo['promoter_user_id'], $filter['company_id']);

                $is_salesman_order = '';
                if($orderAssInfo['promoter_user_id']){
                    $salespersonService = new SalespersonService();
                    $saleFilter = [
                        'company_id' => $filter['company_id'],
                        'user_id' => $orderAssInfo['promoter_user_id'],
                    ];
                    $list = $salespersonService->getSalespersonList($saleFilter, ['created_time' => 'DESC'], 1000, 1);
                    //    var_dump($filter_user,  $list);
                    $user_ids = array_column($list['list'],'user_id');
                    $is_salesman_order = !empty($user_ids) ? '是':'否';

                }

                $payType = $newData['pay_type'] ?? '';
                $username = $userData[$newData['user_id']]['username'] ?? '';
                if ($datapassBlock) {
                    $newData['mobile'] = data_masking('mobile', (string) $newData['mobile']);
                    $username = data_masking('truename', (string) $username);
                    $newData['receiver_name'] = data_masking('truename', (string) $newData['receiver_name']);
                    $newData['receiver_mobile'] = data_masking('mobile', (string) $newData['receiver_mobile']);
                    $newData['receiver_address'] = data_masking('address', (string) $newData['receiver_address']);
                }
                $p_promoter_mobile = $newData['promoter_info']['p_promoter_mobile'] ?? '';
                if ($p_promoter_mobile == "" || $p_promoter_mobile == '0') {
                    $p_promoter_mobile = '-';
                }
                
                $orderItem = [
                    'original_order_id'=> $newData['original_order_id'] ? $newData['original_order_id'] : '-',//
                    'order_id'=> $newData['order_id'],//
                    'supplier_name' => $supplierData[$newData['supplier_id']]['username'] ?? '',
                    // 'trade_no' => $tradeIndex[$newData['order_id']] ?? '-',
                    'cost_fee' => bcdiv($newData['cost_fee'], 100, 2),
                    'store_name' => $storeData[$newData['distributor_id']]['name'] ?? '',
                    'store_code' => $storeData[$newData['distributor_id']]['shop_code'] ?? '',
                    'mobile' => $newData['mobile'],//
                    'user_name' => $username,
                    'create_time' => $newData['create_date'],
                    'freight_fee' => bcdiv($newData['freight_fee'], 100, 2),//
                    // 'total_fee' => ($payType == 'dhpoint') ? $newData['total_fee'] : bcdiv($newData['total_fee'], 100, 2),//
                    // 'discount_fee' => bcdiv($disountFee, 100, 2),
                    // 'discount_info' => $discountDesc,
                    'order_class' => $orderClass[$newData['order_class']],//
                    'order_status' => $newData['order_status_msg'],//
                    'receipt_type' => $receiptType[$newData['receipt_type']],//
                    'ziti_status' => ($newData['ziti_status'] == 'DONE') ? '已自提' : '',//
                    'receiver_name' => $this->clearSpecialChars($newData['receiver_name']),//
                    'receiver_mobile' => $newData['receiver_mobile'],//
                    'receiver_zip' => $newData['receiver_zip'],//
                    'receiver_state' => $newData['receiver_state'],//
                    'receiver_city' => $newData['receiver_city'],//
                    'receiver_district' => $newData['receiver_district'],//
                    'receiver_address' => $this->clearSpecialChars($newData['receiver_address']),//
                    // 'subdistrict_parent' => $newData['subdistrict_parent'],
                    // 'subdistrict' => $newData['subdistrict'],
                    // 'building_number' => $newData['building_number'],
                    // 'house_number' => $newData['house_number'],
                    'delivery_status' => ($newData['delivery_status'] == 'DONE') ? '已发货' : '未发货',
                    'delivery_time' => ($newData['delivery_status'] == 'DONE') ? date('Y-m-d H:i:s', $newData['delivery_time']) : '0',
                    'delivery_code' => ($newData['delivery_status'] == 'DONE') ? $newData['delivery_code']. "\t" : '',
                    'delivery_corp' => ($newData['delivery_status'] == 'DONE') ? $newData['delivery_corp'] : '',
                    'pay_type' => $payTypes[$payType] ?? $payType,
                    'invoice' => $invoiceStr,
                    // 'promoter_user_mobile' => $user_mobile ?? '',
                    'promoter_name' => $newData['promoter_info']['promoter_name'] ?? '-',
                    'promoter_mobile' => $newData['promoter_info']['promoter_mobile'] ?? '-',
                    // 'rebate' => isset($brokerageInfo['rebate']) ? bcdiv($brokerageInfo['rebate'], 100, 2) : 0,
                    'order_total_rebate' => ($newData['promoter_info']['order_total_rebate'] ?? '') == '' ? '-' :bcdiv($newData['promoter_info']['order_total_rebate'], 100, 2),
                    'order_first_rebate' => ($newData['promoter_info']['order_first_rebate'] ?? '') == '' ? '-' : bcdiv($newData['promoter_info']['order_first_rebate'], 100, 2),
                    'order_second_rebate' => ($newData['promoter_info']['order_second_rebate'] ?? '') == '' ? '-' : bcdiv($newData['promoter_info']['order_second_rebate'], 100, 2),
                    'promoter_identity' => $newData['promoter_info']['promoter_identity'] ?? '-',
                    'p_promoter_name' => $newData['promoter_info']['p_promoter_name'] ?? '-',
                    'p_promoter_mobile' => $p_promoter_mobile,
                    'promoter_is_close' => ($newData['promoter_info']['promoter_is_close'] ?? 0) == 1 ? '已结算' : '未结算',
                    'remark' => $newData['remark'],
                    'pickup_address' => isset($zitiData[$newData['order_id']]) ? $zitiData[$newData['order_id']]['province'].$zitiData[$newData['order_id']]['city'].$zitiData[$newData['order_id']]['area'].$zitiData[$newData['order_id']]['address'] : '',
                    'pickup_datetime' => isset($zitiData[$newData['order_id']]) ? $zitiData[$newData['order_id']]['pickup_date'].' '.$zitiData[$newData['order_id']]['pickup_time'][0].'~'.$zitiData[$newData['order_id']]['pickup_time'][1] : '',
                    'source_from' => $newData['source_from'] ?? '',
                    
                ];
                if ($this->order_class == 'community') {
                    $orderItem['activity_status'] = '';
                    $orderItem['activity_delivery_status'] = '';
                    if (isset($communityOrderRelData[$newData['order_id']]['activity_data'])) {
                        $activity_status = $communityOrderRelData[$newData['order_id']]['activity_data']['activity_status'] ?? '';
                        $activity_delivery_status = $communityOrderRelData[$newData['order_id']]['activity_data']['delivery_status'] ?? '';
                        $orderItem['activity_status'] = CommunityActivityService::activity_status[$activity_status] ?? '';
                        $orderItem['activity_delivery_status'] = CommunityActivityService::activity_delivery_status[$activity_delivery_status] ?? '';
                    }
                }
                $orderList[] = $orderItem;
            }
            yield $orderList;
        }
    }

    public function getAftersaleList($filter)
    {
        $aftersalesService = new AftersalesService();
        $list = $aftersalesService->getAftersalesList($filter, 1, -1);
        $data = [];
        foreach ($list['list'] as $value) {
            $data[$value['order_id']] = [
                'aftersales_status' => $value['aftersales_status'],
                'aftersales_type' => $value['aftersales_type'],
            ];
        }
        return $data;
    }

    private function getPointsmallTitle()
    {
        $title = [
            'order_id' => '订单号',//
            'trade_no' => '订单序号',//
            'item_point' => '订单价格',
            'mobile' => '会员手机号',//
            'user_name' => '会员昵称',
            'create_time' => '下单时间',//
            'freight_fee' => '运费(总)',//
            'total_fee' => '实付金额(总)',//
            'order_class' => '订单类型',
            'order_status' => '订单状态',//
            'receipt_type' => '收货方式',//
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
            'remark' => '订单备注'
        ];
        return $title;
    }

    public function getPointsmallLists($filter, $totalCount = 10000, $datapassBlock)
    {
        $limit = 2000;
        $fileNum = ceil($totalCount / $limit);
        $memberService = new MemberService();
        $distributorService = new DistributorService();
        $brokerageService = new BrokerageService();
        $orderAssociationService = new OrderAssociationService();
        $orderService = $this->getOrderService('normal');

        $orderBy = ['distributor_id' => 'desc', 'order_id' => 'desc', 'create_time' => 'asc'];
        $orderClass = [
            'community' => '社区活动订单',
            'groups' => '拼团活动订单',
            'seckill' => '秒杀活动订单',
            'normal' => '普通订单',
            'drug' => '药品需求订单',
            'shopguide' => '代客下单订单',
            'pointsmall' => '积分商城订单',
            'excard' => '兑换券订单',
            'shopadmin' => '门店订单',
            'employee_purchase' => '内购订单',
        ];
        $aftersales_status = [
            'WAIT_SELLER_AGREE' => '等待商家处理',
            'WAIT_BUYER_RETURN_GOODS' => '商家接受申请，等待消费者回寄',
            'WAIT_SELLER_CONFIRM_GOODS' => '消费者回寄，等待商家收货确认',
            'SELLER_REFUSE_BUYER' => '售后驳回',
            'SELLER_SEND_GOODS' => '卖家重新发货 换货完成',
            'REFUND_SUCCESS' => '退款成功',
            'REFUND_CLOSED' => '退款关闭',
            'CLOSED' => '售后关闭',
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
        ];
        $receiptType = ['logistics' => '快递配送', 'ziti' => '上门自提'];

        $tradeService = new TradeService();
        for ($j = 1; $j <= $fileNum; $j++) {
            $orderdata = $orderService->getOrderList($filter, $j, $limit, $orderBy, false)['list'];
            $userIds = array_column($orderdata, 'user_id');
            if ($userIds) {
                $uFilter = [
                    'company_id' => $filter['company_id'],
                    'user_id' => $userIds,
                ];
                $userList = $memberService->getMemberInfoList($uFilter, 1, $limit);
                $userData = array_column($userList['list'], null, 'user_id');
            }

            $orderIdList = array_column($orderdata, 'order_id');
            $tradeIndex = $tradeService->getTradeIndexByOrderIdList($filter['company_id'], $orderIdList);

            $storeIds = array_column($orderdata, 'distributor_id');
            if ($storeIds) {
                $sFilter = [
                    'company_id' => $filter['company_id'],
                    'distributor_id' => $storeIds,
                ];
                $storeList = $distributorService->getDistributorOriginalList($sFilter, 1, $limit);
                $storeData = array_column($storeList['list'], null, 'distributor_id');
            }

            $orderList = [];
            foreach ($orderdata as $newData) {
                $payType = $newData['pay_type'] ?? '';
                if ($newData['freight_type'] == 'point') {
                    $freight_fee = $newData['freight_fee'] . '积分';
                } else {
                    $freight_fee = bcdiv($newData['freight_fee'], 100, 2);
                }
                if ($payType == 'dhpoint') {
                    $total_fee = $newData['total_fee'];
                } elseif ($payType == 'point') {
                    $total_fee = $newData['point'] . '积分';
                } else {
                    $total_fee = $newData['item_point'] . '积分 + ' . bcdiv($newData['total_fee'], 100, 2);
                }
                $username = $userData[$newData['user_id']]['username'] ?? '';
                if ($datapassBlock) {
                    $newData['mobile'] = data_masking('mobile', (string) $newData['mobile']);
                    $username = data_masking('truename', (string) $username);
                    $newData['receiver_name'] = data_masking('truename', (string) $newData['receiver_name']);
                    $newData['receiver_mobile'] = data_masking('mobile', (string) $newData['receiver_mobile']);
                    $newData['receiver_address'] = data_masking('address', (string) $newData['receiver_address']);
                }
                $orderList[] = [
                    'order_id' => $newData['order_id'],
                    'trade_no' => $tradeIndex[$newData['order_id']] ?? '-',
                    'item_point' => $newData['item_point'] . '积分',
                    'mobile' => $newData['mobile'],//
                    'user_name' => $username,
                    'create_time' => $newData['create_date'],
                    'freight_fee' => $freight_fee,//
                    'total_fee' => $total_fee,//
                    'order_class' => $orderClass[$newData['order_class']],//
                    'order_status' => $newData['order_status_msg'],//
                    'receipt_type' => $receiptType[$newData['receipt_type']],//
                    'receiver_name' => $this->clearSpecialChars($newData['receiver_name']),//
                    'receiver_mobile' => $newData['receiver_mobile'],//
                    'receiver_zip' => $newData['receiver_zip'],//
                    'receiver_state' => $newData['receiver_state'],//
                    'receiver_city' => $newData['receiver_city'],//
                    'receiver_district' => $newData['receiver_district'],//
                    'receiver_address' => $this->clearSpecialChars($newData['receiver_address']),//
                    'delivery_status' => ($newData['delivery_status'] == 'DONE') ? '已发货' : '未发货',
                    'delivery_time' => ($newData['delivery_status'] == 'DONE') ? date('Y-m-d H:i:s', $newData['delivery_time']) : '0',
                    'delivery_code' => ($newData['delivery_status'] == 'DONE') ? $newData['delivery_code'] . "\t" : '',
                    'delivery_corp' => ($newData['delivery_status'] == 'DONE') ? $newData['delivery_corp'] : '',
                    'pay_type' => $payTypes[$payType] ?? $payType,
                    'remark' => str_replace("\n", ' ', trim($newData['remark'])),
                ];
            }
            yield $orderList;
        }
    }

}
