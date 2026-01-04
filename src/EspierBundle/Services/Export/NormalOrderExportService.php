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

use AftersalesBundle\Services\AftersalesService;
use CommunityBundle\Services\CommunityActivityService;
use CommunityBundle\Services\CommunityOrderRelActivityService;
use EspierBundle\Interfaces\ExportFileInterface;
use GoodsBundle\Services\ItemsCategoryService;
use GoodsBundle\Services\ItemsService;
use OrdersBundle\Entities\NormalOrdersRelSupplier;
use OrdersBundle\Services\TradeService;
use OrdersBundle\Traits\GetOrderServiceTrait;
use EspierBundle\Services\ExportFileService;
use EspierBundle\Services\ExportExcelService;
use MembersBundle\Services\MemberService;
use DistributionBundle\Services\DistributorService;
use OrdersBundle\Services\NormalOrdersRelZitiService;
use CompanysBundle\Services\OperatorsService;
use SupplierBundle\Services\SupplierService;

class NormalOrderExportService implements ExportFileInterface
{
    use GetOrderServiceTrait;

    protected $order_class = '';

    public function exportData($filter)
    {
        // 是否需要数据脱敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        $orderService = $this->getOrderService('normal');
        /**
         * @var $orderService \OrdersBundle\Services\Orders\NormalOrderService
         */
        $count = $orderService->getOrderItemCount($filter);
        if (!$count) {
            return [];
        }

        $this->order_class = $filter['order_class'] ?? '';

        if (isset($filter['order_class']) && $filter['order_class'] == 'pointsmall') {
            $fileName = date('YmdHis') . $filter['company_id'] . "order积分商城";
            $title = $this->getPointsmallTitle();
            $orderList = $this->getPointsmallLists($filter, $count, $datapassBlock);
        } else {
            $fileName = date('YmdHis') . $filter['company_id'] . "supplier_order";
            if (isset($filter['supplier_id']) && $filter['supplier_id'] > 0) {
                $title = $this->getSupplierTitle();
                $orderList = $this->getSupplierLists($filter, $count, $datapassBlock);
            } else {
                $fileName = date('YmdHis') . $filter['company_id'] . "order";
                $title = $this->getTitle();
                $orderList = $this->getLists($filter, $count, $datapassBlock);
            }
        }

        $exportService = new ExportFileService();
        // 指定需要作为文本处理的数字字段，避免 Excel 显示为科学计数法
        $textFields = ['order_id', 'delivery_code', 'item_bn'];
        $result = $exportService->exportCsv($fileName, $title, $orderList, $textFields);
        return $result;
    }

    /**
     * 导出订单数据到Excel文件（支持百万级数据）
     * @param array $filter 过滤条件
     * @return array 导出结果
     */
    public function exportDataToExcel($filter)
    {
        // 是否需要数据脱敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);

        $orderService = $this->getOrderService('normal');
        /**
         * @var $orderService \OrdersBundle\Services\Orders\NormalOrderService
         */
        $count = $orderService->getOrderItemCount($filter);
        if (!$count) {
            return [];
        }

        $this->order_class = $filter['order_class'] ?? '';

        // 生成文件名和标题
        if (isset($filter['order_class']) && $filter['order_class'] == 'pointsmall') {
            $fileName = date('YmdHis') . $filter['company_id'] . "order积分商城";
            $title = $this->getPointsmallTitle();
            $dataGenerator = $this->getPointsmallLists($filter, $count, $datapassBlock);
        } else {
            $fileName = date('YmdHis') . $filter['company_id'] . "supplier_order";
            if (isset($filter['supplier_id']) && $filter['supplier_id'] > 0) {
                $title = $this->getSupplierTitle();
                $dataGenerator = $this->getSupplierLists($filter, $count, $datapassBlock);
            } else {
                $fileName = date('YmdHis') . $filter['company_id'] . "order";
                $title = $this->getTitle();
                $dataGenerator = $this->getLists($filter, $count, $datapassBlock);
            }
        }

        // 数据处理回调函数
        $dataProcessor = function($orderItem) {
            // 确保所有值都是字符串，避免Excel格式问题
            return array_map(function($value) {
                return (string) $value;
            }, $orderItem);
        };

        $excelService = new ExportExcelService();
        $result = $excelService->export($fileName, array_values($title), $dataGenerator, $dataProcessor);
        return $result;
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
                $discountDesc = "满折：".$value['discount_fee'].";";
                break;
            case "full_minus":
                $discountDesc = "满减：".$value['discount_fee'].";";
                break;
            case "coupon_discount":
                $discountDesc = "折扣优惠券：".$value['discount_fee'].";";
                break;
            case "cash_discount":
                $discountDesc = "代金优惠券：".$value['discount_fee'].";";
                break;
            case "limited_time_sale":
                $discountDesc = "限时特惠：".$value['discount_fee'].";";
                break;
            case "seckill":
                $discountDesc = "秒杀：".$value['discount_fee'].";";
                break;
            case "groups":
                $discountDesc = "拼团：".$value['discount_fee'].";";
                break;
            case "member_price":
                $discountDesc = "会员价：".$value['discount_fee'].";";
                break;
            case "member_tag_targeted_promotio：":
                $discountDesc = "定向促销：".$value['discount_fee'].";";
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

    public function getTitle()
    {
        $title = [
            'order_id' => '订单号',//
            'name' => '用户名',
            'id' => '子订单号',
            'supplier_name' => '所属供应商',
            'item_name' => '商品名称',
            'category_tree' => '管理分类',
            'price' => '商品销售单价',
            'cost_price' => '成本单价',
            'num' => '购买数量',
            'item_fee' => '销售总金额', 
            'commission_fee' => '商品佣金',
            'cost_fee' => '结算总价（¥）',
            'freight_fee' => '运费(总)', // 按供应商和自营分开
            'total_fee_total' => '实付金额(总)',
            'point_fee' => '积分抵扣（¥）',
            'total_fee' => '现金实付（¥）',
            'discount_fee' => '优惠总金额',
            'discount_info' => '优惠详情',
            'refund_num' => '退货数量',
            'refund_cost_price' => '退货成本', // 退货结算价=成本单价*退货数量
            'refunded_point_fee' => '退款积分',
            'refunded_fee' => '退款金额',
            'store_name' => '所属店铺',
            'store_code' => '店铺号',
            'mobile' => '会员手机号',//
            'user_name' => '会员昵称',
            'create_time' => '下单时间',//
            'pay_time' => '支付时间',
            'order_class' => '订单类型',//
            'order_status' => '订单状态',//
            'receipt_type' => '收货方式',//
            'ziti_status' => '自提状态',//
            'receiver_name' => '收货人姓名',//
            'receiver_mobile' => '收货人手机',//
            'receiver_zip' => '收货人邮编',//
            'receiver_state' => '收货人所在省份',//
            'receiver_city' => '收货人所在城市',//
            'receiver_district' => '收货人所在地区、县',//
            'receiver_address' => '收货地址',//
            'delivery_status' => '收货状态',
            'delivery_time' => '发货时间',
            'delivery_code' => '快递单号',
            'delivery_corp' => '快递公司',
            'end_time' => '订单完成时间',
            'pay_type' => '支付方式',
            'item_bn' => '商品货号',
            'aftersales_status' => '售后状态',
            'refund_time' => '退款时间',
            'item_spec_desc' => '规格描述',
            'remark' => '订单备注',
            'pickup_address' => '自提地址',
            'pickup_datetime' => '提货时间',
            'purchase_type' => '角色',
            'employee_name' => '员工姓名',
            'enterprise_name' => '所属企业',
        ];
        return $title;
    }

    public function getTitleBak()
    {
        $title = [
            // 'original_order_id' => '原订单号',
            'order_id' => '订单号',//
            'supplier_name' => '所属供应商',
            // 'trade_no' => '订单序号',//
            'id' => '子订单号',
            'item_name' => '商品名称',
            'item_fee' => '商品金额小计',
            'commission_fee' => '商品佣金',
            'price' => '商品售价',
            'cost_fee' => '成本价',
            'num' => '购买数量',
            'store_name' => '所属店铺',
            'store_code' => '店铺号',
            'mobile' => '会员手机号',//
            'user_name' => '会员昵称',
            'create_time' => '下单时间',//
            'freight_fee' => '运费(总)',//
            'total_fee' => '实付金额(总)',//
            'discount_fee' => '优惠金额',
            'discount_info' => '优惠详情',
            'refunded_fee' => '退款金额',
            'order_class' => '订单类型',//
            'order_status' => '订单状态',//
            'receipt_type' => '收货方式',//
            'ziti_status' => '自提状态',//
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
            'delivery_status' => '收货状态',
            'delivery_time' => '发货时间',
            'delivery_code' => '快递单号',
            'delivery_corp' => '快递公司',
            'pay_type' => '支付方式',
            'item_bn' => '商品货号',
            'aftersales_status' => '售后状态',
            'item_spec_desc' => '规格描述',
            'remark' => '订单备注',
            'pickup_address' => '自提地址',
            'pickup_datetime' => '提货时间',
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

    public function getSupplierTitle()
    {
        $title = [
            'order_id' => '订单号',//
            'supplier_name' => '所属供应商',
            'id' => '子订单号',
            'item_name' => '商品名称',
            'cost_price' => '成本单价',
            'num' => '购买数量',
            'cost_fee' => '结算总价（¥）',
            'freight_fee' => '运费（总）',
            'refund_num' => '退货数量',
            'refund_cost_price' => '退货结算价',
            // 'commission_fee' => '商品佣金',
            'store_name' => '所属店铺',
            'store_code' => '店铺号',
            'mobile' => '会员手机号',//
            'user_name' => '会员昵称',
            'create_time' => '下单时间',//
            'pay_time' => '支付时间',//
            'refunded_fee' => '退款金额',
            'refunded_point_fee' => '退款积分',
            'order_class' => '订单类型',//
            'order_status' => '订单状态',//
            'end_time' => '订单完成时间',
            'receipt_type' => '收货方式',//
            'ziti_status' => '自提状态',//
            'receiver_name' => '收货人姓名',//
            'receiver_mobile' => '收货人手机',//
            'receiver_zip' => '收货人邮编',//
            'receiver_state' => '收货人所在省份',//
            'receiver_city' => '收货人所在城市',//
            'receiver_district' => '收货人所在地区、县',//
            'receiver_address' => '收货地址',//
            'delivery_status' => '收货状态',
            'delivery_time' => '发货时间',
            'delivery_code' => '快递单号',
            'delivery_corp' => '快递公司',
            'pay_type' => '支付方式',
            'item_bn' => '商品货号',
            'aftersales_status' => '售后状态',
            'refund_time' => '退款时间',    
            'item_spec_desc' => '规格描述',
            'remark' => '订单备注',
            'pickup_address' => '自提地址',
            'pickup_datetime' => '提货时间',
        ];
        return $title;
    }

    public function getSupplierTitleBak()
    {
        $title = [
            // 'original_order_id' => '原订单号',
            'order_id' => '订单号',//
            'supplier_name' => '所属供应商',
            // 'trade_no' => '订单序号',//
            'id' => '子订单号',
            'item_name' => '商品名称',
            'item_fee' => '商品金额小计',
            'commission_fee' => '商品佣金',
            'price' => '商品售价',
            'num' => '购买数量',
            'store_name' => '所属店铺',
            'store_code' => '店铺号',
            'mobile' => '会员手机号',//
            'user_name' => '会员昵称',
            'create_time' => '下单时间',//
            // 'freight_fee' => '运费(总)',//
            // 'total_fee' => '实付金额(总)',//
            // 'discount_fee' => '优惠金额',
            // 'discount_info' => '优惠详情',
            'refunded_fee' => '退款金额',
            'order_class' => '订单类型',//
            'order_status' => '订单状态',//
            'receipt_type' => '收货方式',//
            'ziti_status' => '自提状态',//
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
            'delivery_status' => '收货状态',
            'delivery_time' => '发货时间',
            'delivery_code' => '快递单号',
            'delivery_corp' => '快递公司',
            'pay_type' => '支付方式',
            'item_bn' => '商品货号',
            'aftersales_status' => '售后状态',
            'item_spec_desc' => '规格描述',
            'remark' => '订单备注',
            'pickup_address' => '自提地址',
            'pickup_datetime' => '提货时间',
        ];
        if ($this->order_class == 'community') {
            $title['activity_status'] = '活动状态';
            $title['activity_delivery_status'] = '活动发货状态';
        }
        return $title;
    }

    public function getPointsmallTitle()
    {
        $title = [
            'order_id' => '主订单号',//
            'trade_no' => '订单序号',//
            'id' => '子订单号',
            'item_name' => '商品名称',
            'point' => '订单价格',
            'item_point' => '商品价格',
            'num' => '购买数量',
            'mobile' => '会员手机号',//
            'user_name' => '会员昵称',
            'create_time' => '下单时间',//
            'total_fee' => '实付金额(总)',//
            // 'refunded_fee' => '退款金额',
            'order_class' => '订单类型',//
            'order_status' => '订单状态',//
            'receipt_type' => '收货方式',//
            // 'ziti_status'=> '自提状态',//
            'receiver_name' => '收货人姓名',//
            'receiver_mobile' => '收货人手机',//
            'receiver_zip' => '收货人邮编',//
            'receiver_state' => '收货人所在省份',//
            'receiver_city' => '收货人所在城市',//
            'receiver_district' => '收货人所在地区、县',//
            'receiver_address' => '收货地址',//
            'delivery_status' => '收货状态',
            'delivery_time' => '发货时间',
            'delivery_code' => '快递单号',
            'delivery_corp' => '快递公司',
            'pay_type' => '支付方式',
            'item_bn' => '商品货号',
            'item_spec_desc' => '规格描述',
            'remark' => '订单备注'
        ];
        return $title;
    }

    public function getLists($filter, $totalCount = 10000, $datapassBlock)
    {
        $limit = 2000;
        $fileNum = ceil($totalCount / $limit);

        $memberService = new MemberService();
        $distributorService = new DistributorService();
        $orderService = $this->getOrderService('normal');
        $orderBy = ['distributor_id' => 'desc', 'order_id' => 'desc', 'create_time' => 'asc', 'id' => 'desc'];
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
        $orderStatus = [
            'NOTPAY' => '未支付',
            'CANCEL' => '已取消',
            'CANCEL_WAIT_PROCESS' => '取消待处理',
            'DONE' => '已完成',
            'PAYED' => '待发货',
            'REFUND_SUCCESS' => '退款完成',
            'WAIT_BUYER_CONFIRM' => '待收货',
            'REVIEW_PASS' => '审核通过待出库',
            'DADA_0' => '店铺待接单',
            'DADA_1' => '骑士待接单',
            'DADA_2' => '待取货',
            'DADA_100' => '骑士到店',
            'DADA_3' => '配送中',
            'DADA_9' => '未妥投',
            'DADA_10' => '妥投异常',
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
            'employee_purchase' => '内购订单',
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
        $tradeService = new TradeService();
        $aftersalesService = new AftersalesService();
        $normalOrdersRelSupplierRepository = app('registry')->getManager('default')->getRepository(NormalOrdersRelSupplier::class);
        $itemCategorySerivce = new ItemsCategoryService();
        $itemsService = new ItemsService();
        $supplierMap = []; // 订单按supplier_id运费
        $supplierHas = []; // 用于控制显示供应商数据在第一个
        $itemHas = []; // 用于控制显示自营数据在第一个
        for ($j = 1; $j <= $fileNum; $j++) {
            $orderdata = $orderService->getOrderItemList($filter, $j, $limit, $orderBy);
            // 用户信息
            $userIds = array_filter($orderdata['user_ids']);
            if ($userIds) {
                $uFilter = [
                    'company_id' => $filter['company_id'],
                    'user_id' => $userIds,
                ];
                $userList = $memberService->getMemberInfoList($uFilter, 1, $limit);
                $userData = array_column($userList['list'], null, 'user_id');
            }

            // 支付信息
            $orderIdList = array_column($orderdata['list'], 'order_id');
            $tradeList = $tradeService->getSuccTradeInfoByOrderIdList($filter['company_id'], $orderIdList);

            // 订单信息
            $orderListData = $orderService->getOrderList(['company_id' =>$filter['company_id'], 'order_id' => $orderIdList], 1, -1, [], false);
            $orderListMap = array_column($orderListData['list'], null, 'order_id');
                
            // 退款信息
            $refundMap = [];
            foreach ($orderdata['list'] as $k => $v) {
                $refundMap[$v['order_id']][$v['item_id']] = $aftersalesService->getAftersaleRefundInfoByOrderIdItemId($v['order_id'], $v['item_id'], $filter['company_id']);
            }
             // 售前退款，不会存在售后单
            $refundMapForward = $aftersalesService->getSuccAftersaleRefundInfoByOrderIdList($filter['company_id'], $orderIdList);

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

            // 店铺信息
            $storeIds = array_filter($orderdata['distributor_ids']);
            if ($storeIds) {
                $sFilter = [
                    'company_id' => $filter['company_id'],
                    'distributor_id' => $storeIds,
                ];
                $storeList = $distributorService->getDistributorOriginalList($sFilter, 1, $limit);
                $storeData = array_column($storeList['list'], null, 'distributor_id');
            }

            // 供应商信息
            $supplierIds = array_column($orderdata['list'], 'supplier_id');
            if ($supplierIds) {
                $supplierService = new SupplierService();
                $supplierList = $supplierService->lists(['company_id' => $filter['company_id'], 'operator_id' => $supplierIds]);
                $supplierData = array_column($supplierList['list'], null, 'operator_id');
            }

            // 供应商运费
            $supplierFreigthFilter = [
                'company_id' => $filter['company_id'],
                'order_id' => $orderIdList
            ];
            $supplierTotalFreight = 0; // 订单供应商总运费
            $supplierFreigthList = $normalOrdersRelSupplierRepository->getLists($supplierFreigthFilter);
            foreach ($supplierFreigthList as $k => $v) {
                $supplierMap[$v['order_id']][$v['supplier_id']] = $v['freight_fee'];
                $supplierTotalFreight += $v['freight_fee'];
            }

            // 分类信息
            $itemIdList  = array_column($orderdata['list'], 'item_id');
            $itemCategoryList = $itemsService->getAllItems(['item_id' => $itemIdList, 'company_id' => $filter['company_id']], ['item_id','item_category']);
            $itemPathName = [];
            if (!empty($itemCategoryList)) {
                foreach ($itemCategoryList as $k => $v) {
                    $itemPathName[$v['item_id']] = $itemCategorySerivce->getCategoryPathNameById($v['item_category'],$filter['company_id'], true);
                }
            }

            // 自提信息
            $ordersRelZitiService = new NormalOrdersRelZitiService();
            $zitiList = $ordersRelZitiService->getLists(['order_id' => $orderIdList]);
            $zitiData = array_column($zitiList, null, 'order_id');

            $orderList = [];
            foreach ($orderdata['list'] as $newData) {
                //兼容已自提订单的子订单的发货状态错误的问题，子订单发货状态错误的原因待查
                if ($newData['ziti_status'] == 'DONE' && $newData['order_delivery_status'] == 'DONE') {
                    $newData['delivery_status'] = $newData['order_delivery_status'];
                    $newData['delivery_time'] = $newData['order_delivery_time'];
                    $newData['delivery_code'] = $newData['order_delivery_corp'];
                    $newData['delivery_corp'] = $newData['order_delivery_code'];
                }

                // 处理达达订单状态
                $dada_status = $dadaRelList[$newData['order_id']]['dada_status'] ?? '';
                $dada_status = 'DADA_'.$dada_status;
                if ($newData['receipt_type'] == 'dada' && isset($orderStatus[$dada_status])) {
                    $newData['order_status'] = $dada_status;
                }
                app('log')->info('order_id:'.$newData['order_id'].',receipt_type:'.$newData['receipt_type'].',order_status:'.$newData['order_status'].',dada_status:'.$dada_status);
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
                $username = $userData[$newData['user_id']]['username'] ?? '';
                if ($datapassBlock) {
                    $newData['mobile'] = data_masking('mobile', (string) $newData['mobile']);
                    $username = data_masking('truename', (string) $username);
                    $newData['receiver_name'] = data_masking('truename', (string) $newData['receiver_name']);
                    $newData['receiver_mobile'] = data_masking('mobile', (string) $newData['receiver_mobile']);
                    $newData['receiver_address'] = data_masking('address', (string) $newData['receiver_address']);
                }
                $payType = $newData['pay_type'] ?? '';
                $purchase_type = $newData['orders_purchase_info']['type'] ?? '';
                $orderItem = [
                    'order_id' => $newData['order_id'],//
                    'name' => $userData[$newData['user_id']]['name'] ?? '',
                    'id' => $newData['id'],//
                    'supplier_name' => $supplierData[$newData['supplier_id']]['supplier_name'] ?? '',
                    'item_name' => str_replace('#', '', $newData['item_name']) ,//
                    'category_tree' => isset($itemPathName[$newData['item_id']]) ? implode('->', $itemPathName[$newData['item_id']]) : '',
                    'price' => bcdiv($newData['price'], 100, 2),
                    'cost_price' => bcdiv($newData['cost_price'], 100, 2),//
                    'num' => $newData['num'],
                    'item_fee' => bcdiv($newData['item_fee'], 100, 2),
                    'commission_fee' => bcdiv($newData['commission_fee'], 100, 2),
                    'cost_fee' => bcdiv($newData['cost_fee'], 100, 2),//, // 成本总金额
                    'freight_fee' => 0, // 运费
                    'total_fee_total' => bcdiv($newData['total_fee'] + $newData['point_fee'], 100, 2), // 实付金额(总)
                    'point_fee' => bcdiv($newData['point_fee'], 100, 2),
                    'total_fee' => bcdiv($newData['total_fee'], 100, 2),//
                    'discount_fee' => bcdiv($disountFee, 100, 2),
                    'discount_info' => $discountDesc,
                    'refund_num' => isset($refundMap[$newData['order_id']][$newData['item_id']]['num']) ? $refundMap[$newData['order_id']][$newData['item_id']]['num'] : 0, // 退货数量,
                    'refund_cost_price' => bcdiv($newData['cost_price'] * ($refundMap[$newData['order_id']][$newData['item_id']]['num'] ?? 0) , 100, 2), // 退货结算价=成本单价*退货数量
                    'refunded_point_fee' => isset($refundMap[$newData['order_id']][$newData['item_id']]['refund_point']) ? bcdiv($refundMap[$newData['order_id']][$newData['item_id']]['refund_point'], 100, 2) : 0,
                    'refunded_fee' => isset($refundMap[$newData['order_id']][$newData['item_id']]['refund_fee']) ? bcdiv($refundMap[$newData['order_id']][$newData['item_id']]['refund_fee'], 100, 2) : 0,
                    'store_name' => $storeData[$newData['distributor_id']]['name'] ?? '',
                    'store_code' => $storeData[$newData['distributor_id']]['shop_code'] ?? '',
                    'mobile' => $newData['mobile'],
                    'user_name' => $username,
                    'create_time' => date('Y-m-d H:i:s', $newData['create_time']),
                    'pay_time' => isset($tradeList[$newData['order_id']]['time_expire']) ? date('Y-m-d H:i:s', $tradeList[$newData['order_id']]['time_expire']) : '', // 支付时间
                    'order_class' => $orderClass[$newData['order_class']],//
                    'order_status'=> $newData['order_status_msg'],//
                    'receipt_type' => $receiptType[$newData['receipt_type']],//
                    'ziti_status' => ($newData['ziti_status'] == 'DONE') ? '已自提' : '',//
                    'receiver_name' => $this->clearSpecialChars($newData['receiver_name']),//
                    'receiver_mobile' => $newData['receiver_mobile'],//
                    'receiver_zip' => $newData['receiver_zip'],//
                    'receiver_state' => $newData['receiver_state'],//
                    'receiver_city' => $newData['receiver_city'],//
                    'receiver_district' => $newData['receiver_district'],//
                    'receiver_address' => $this->clearSpecialChars($newData['receiver_address']),//
                    'delivery_status' => ($newData['delivery_status'] == 'DONE') ? '已发货' : '未发货',
                    'delivery_time' => ($newData['delivery_status'] == 'DONE') ? date('Y-m-d H:i:s', $newData['delivery_time']) : '0',
                    'delivery_code' => ($newData['delivery_status'] == 'DONE') ? $newData['delivery_code'] : '',
                    'delivery_corp' => ($newData['delivery_status'] == 'DONE') ? $newData['delivery_corp'] : '',
                    'end_time' => isset($orderListMap[$newData['order_id']]['end_time']) ? date('Y-m-d H:i:s', $orderListMap[$newData['order_id']]['end_time']) : '', // 订单完成时间
                    'pay_type' => $payTypes[$payType] ?? $payType,
                    'item_bn' => $newData['item_bn'] ?? '',
                    'aftersales_status' => $aftersales_status[$newData['aftersales_status']] ?? '',
                    'refund_time' => isset($refundMap[$newData['order_id']][$newData['item_id']]['refund_success_time']) ? date('Y-m-d H:i:s', $refundMap[$newData['order_id']][$newData['item_id']]['refund_success_time']) : '', // 退款时间
                    'item_spec_desc' => isset($newData['item_spec_desc']) && $newData['item_spec_desc'] ? str_replace(',', '，', $newData['item_spec_desc']) : '',
//                    'invoice' => isset($newData['item_spec_desc']) && $newData['item_spec_desc'] ? str_replace(',', '，', $newData['item_spec_desc']) : '', // 发票内容
                    'remark' => $newData['remark'],
                    'pickup_address' => isset($zitiData[$newData['order_id']]) ? $zitiData[$newData['order_id']]['province'].$zitiData[$newData['order_id']]['city'].$zitiData[$newData['order_id']]['area'].$zitiData[$newData['order_id']]['address'] : '',
                    'pickup_datetime' => isset($zitiData[$newData['order_id']]) ? $zitiData[$newData['order_id']]['pickup_date'].' '.$zitiData[$newData['order_id']]['pickup_time'][0].'~'.$zitiData[$newData['order_id']]['pickup_time'][1] : '',
                    'purchase_type' => $purchaseType[$purchase_type] ?? '会员',
                    'employee_name' => $newData['orders_purchase_info']['employee_name'] ?? '',
                    'enterprise_name' => $newData['orders_purchase_info']['enterprise_name'] ?? '',
                ];
                // 退款方式处理, 1如果是支付取消，不走售后单，这样数据就原数据返回，如果走售后就取售后数据
                if (isset($orderListMap[$newData['order_id']]['order_status']) && $orderListMap[$newData['order_id']]['order_status'] == 'CANCEL' && $orderListMap[$newData['order_id']]['pay_status'] == 'PAYED') {
                    $orderItem['refund_num'] = $newData['num'];
                    $orderItem['refunded_point_fee'] = bcdiv($newData['point_fee'], 100, 2);
                    $orderItem['refunded_fee'] = bcdiv($newData['total_fee'], 100, 2);
                    $orderItem['refund_cost_price'] = bcdiv($newData['cost_price'] * $newData['num'], 100, 2);
                    $orderItem['refund_time'] =  isset($refundMapForward[$newData['order_id']]['refund_success_time']) ? date('Y-m-d H:i:s', $refundMapForward[$newData['order_id']]['refund_success_time']) : '';
                }

                // 运费都显示在第一行
                if (isset($supplierMap[$newData['order_id']][$newData['supplier_id']]) && !in_array($newData['order_id'].'_'.$newData['supplier_id'], $supplierHas) && $newData['supplier_id'] > 0) {
                    $orderItem['freight_fee'] = isset($supplierMap[$newData['order_id']][$newData['supplier_id']]) ? bcdiv($supplierMap[$newData['order_id']][$newData['supplier_id']], 100, 2) : 0;
                    $supplierHas[] = $newData['order_id'].'_'.$newData['supplier_id'];
                }else if(!in_array($newData['order_id'], $itemHas) && $newData['supplier_id'] == 0){
                    $orderItem['freight_fee'] = bcdiv($newData['freight_fee'] - $supplierTotalFreight, 100, 2);
                    $itemHas[] = $newData['order_id'];
                }

                $orderList[] = $orderItem;
            }
            yield $orderList;
        }
    }

    public function getListsBak($filter, $totalCount = 10000, $datapassBlock)
    {
        $limit = 2000;
        $fileNum = ceil($totalCount / $limit);

        $memberService = new MemberService();
        $distributorService = new DistributorService();
        $orderService = $this->getOrderService('normal');
        $orderBy = ['distributor_id' => 'desc', 'order_id' => 'desc', 'create_time' => 'asc', 'id' => 'desc'];
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
        $orderStatus = [
          'NOTPAY' => '未支付',
          'CANCEL' => '已取消',
          'CANCEL_WAIT_PROCESS' => '取消待处理',
          'DONE' => '已完成',
          'PAYED' => '待发货',
          'REFUND_SUCCESS' => '退款完成',
          'WAIT_BUYER_CONFIRM' => '待收货',
          'REVIEW_PASS' => '审核通过待出库',
          'DADA_0' => '店铺待接单',
          'DADA_1' => '骑士待接单',
          'DADA_2' => '待取货',
          'DADA_100' => '骑士到店',
          'DADA_3' => '配送中',
          'DADA_9' => '未妥投',
          'DADA_10' => '妥投异常',
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
          'employee_purchase' => '内购订单',
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
        $tradeService = new TradeService();
        for ($j = 1; $j <= $fileNum; $j++) {
            $orderdata = $orderService->getOrderItemList($filter, $j, $limit, $orderBy);

            $userIds = array_filter($orderdata['user_ids']);
            if ($userIds) {
                $uFilter = [
                  'company_id' => $filter['company_id'],
                  'user_id' => $userIds,
              ];
                $userList = $memberService->getMemberInfoList($uFilter, 1, $limit);
                $userData = array_column($userList['list'], null, 'user_id');
            }

            $orderIdList = array_column($orderdata['list'], 'order_id');
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

            $storeIds = array_filter($orderdata['distributor_ids']);
            if ($storeIds) {
                $sFilter = [
                  'company_id' => $filter['company_id'],
                  'distributor_id' => $storeIds,
              ];
                $storeList = $distributorService->getDistributorOriginalList($sFilter, 1, $limit);
                $storeData = array_column($storeList['list'], null, 'distributor_id');
            }

            $supplierIds = array_column($orderdata['list'], 'supplier_id');
            if ($supplierIds) {
                $supplierService = new SupplierService();
                $supplierList = $supplierService->lists(['company_id' => $filter['company_id'], 'operator_id' => $supplierIds]);
                $supplierData = array_column($supplierList['list'], null, 'operator_id');
            }

            $ordersRelZitiService = new NormalOrdersRelZitiService();
            $zitiList = $ordersRelZitiService->getLists(['order_id' => $orderIdList]);
            $zitiData = array_column($zitiList, null, 'order_id');

            $orderList = [];
            foreach ($orderdata['list'] as $newData) {

              //兼容已自提订单的子订单的发货状态错误的问题，子订单发货状态错误的原因待查
                if ($newData['ziti_status'] == 'DONE' && $newData['order_delivery_status'] == 'DONE') {
                    $newData['delivery_status'] = $newData['order_delivery_status'];
                    $newData['delivery_time'] = $newData['order_delivery_time'];
                    $newData['delivery_code'] = $newData['order_delivery_corp'];
                    $newData['delivery_corp'] = $newData['order_delivery_code'];
                }

                // 处理达达订单状态
                $dada_status = $dadaRelList[$newData['order_id']]['dada_status'] ?? '';
                $dada_status = 'DADA_'.$dada_status;
                if ($newData['receipt_type'] == 'dada' && isset($orderStatus[$dada_status])) {
                    $newData['order_status'] = $dada_status;
                }
                app('log')->info('order_id:'.$newData['order_id'].',receipt_type:'.$newData['receipt_type'].',order_status:'.$newData['order_status'].',dada_status:'.$dada_status);
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
                $username = $userData[$newData['user_id']]['username'] ?? '';
                if ($datapassBlock) {
                    $newData['mobile'] = data_masking('mobile', (string) $newData['mobile']);
                    $username = data_masking('truename', (string) $username);
                    $newData['receiver_name'] = data_masking('truename', (string) $newData['receiver_name']);
                    $newData['receiver_mobile'] = data_masking('mobile', (string) $newData['receiver_mobile']);
                    $newData['receiver_address'] = data_masking('address', (string) $newData['receiver_address']);
                }
                $payType = $newData['pay_type'] ?? '';
                $purchase_type = $newData['orders_purchase_info']['type'] ?? '';
                $orderItem = [
                  // 'original_order_id' => "\"'" . $newData['original_order_id'] . "\"",//
                  'order_id' => "\"'" . $newData['order_id'] . "\"",//
                  // 'trade_no' => $tradeIndex[$newData['order_id']] ?? '-',
                  'supplier_name' => $supplierData[$newData['supplier_id']]['supplier_name'] ?? '',
                  'id' => $newData['id'],//
                  'item_name' => str_replace('#', '', $newData['item_name']) ,//
                  'item_fee' => bcdiv($newData['item_fee'], 100, 2),
                  'commission_fee' => bcdiv($newData['commission_fee'], 100, 2),
                  'price' => bcdiv($newData['price'], 100, 2),
                  'cost_fee' => bcdiv($newData['cost_fee'], 100, 2),//
                  'num' => $newData['num'],
                  'store_name' => $storeData[$newData['distributor_id']]['name'] ?? '',
                  'store_code' => $storeData[$newData['distributor_id']]['shop_code'] ?? '',
                  'mobile' => $newData['mobile'],//
                  'user_name' => $username,
                  'create_time' => date('Y-m-d H:i:s', $newData['create_time']),
                  'freight_fee' => bcdiv($newData['freight_fee'], 100, 2),//
                  'total_fee' => bcdiv($newData['total_fee'], 100, 2),//
                  'discount_fee' => bcdiv($disountFee, 100, 2),
                  'discount_info' => $discountDesc,
                  'refunded_fee' => bcdiv($newData['refunded_fee'], 100, 2),
                  'order_class' => $orderClass[$newData['order_class']],//
                  'order_status'=> $newData['order_status_msg'],//
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
                  'delivery_code' => ($newData['delivery_status'] == 'DONE') ? "\"'".$newData['delivery_code']."\"" : '',
                  'delivery_corp' => ($newData['delivery_status'] == 'DONE') ? $newData['delivery_corp'] : '',
                  // 'kunnr' => $thirdParams['kunnr'] ?? '',
                  'pay_type' => $payTypes[$payType] ?? $payType,
                  'item_bn' => is_numeric($newData['item_bn']) ? "\"'".$newData['item_bn']."\"" : $newData['item_bn'],
                  'aftersales_status' => $aftersales_status[$newData['aftersales_status']] ?? '',
                  'item_spec_desc' => isset($newData['item_spec_desc']) && $newData['item_spec_desc'] ? str_replace(',', '，', $newData['item_spec_desc']) : '',
                  'remark' => $newData['remark'],
                  'pickup_address' => isset($zitiData[$newData['order_id']]) ? $zitiData[$newData['order_id']]['province'].$zitiData[$newData['order_id']]['city'].$zitiData[$newData['order_id']]['area'].$zitiData[$newData['order_id']]['address'] : '',
                  'pickup_datetime' => isset($zitiData[$newData['order_id']]) ? $zitiData[$newData['order_id']]['pickup_date'].' '.$zitiData[$newData['order_id']]['pickup_time'][0].'~'.$zitiData[$newData['order_id']]['pickup_time'][1] : '',
                  'purchase_type' => $purchaseType[$purchase_type] ?? '会员',
                  'employee_name' => $newData['orders_purchase_info']['employee_name'] ?? '',
                  'enterprise_name' => $newData['orders_purchase_info']['enterprise_name'] ?? '',
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
    public function getSupplierLists($filter, $totalCount = 10000, $datapassBlock)
    {
        $limit = 2000;
        $fileNum = ceil($totalCount / $limit);

        $memberService = new MemberService();
        $distributorService = new DistributorService();
        $orderService = $this->getOrderService('normal');
        $orderBy = ['distributor_id' => 'desc', 'order_id' => 'desc', 'create_time' => 'asc', 'id' => 'desc'];
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
        $orderStatus = [
          'NOTPAY' => '未支付',
          'CANCEL' => '已取消',
          'CANCEL_WAIT_PROCESS' => '取消待处理',
          'DONE' => '已完成',
          'PAYED' => '待发货',
          'REFUND_SUCCESS' => '退款完成',
          'WAIT_BUYER_CONFIRM' => '待收货',
          'REVIEW_PASS' => '审核通过待出库',
          'DADA_0' => '店铺待接单',
          'DADA_1' => '骑士待接单',
          'DADA_2' => '待取货',
          'DADA_100' => '骑士到店',
          'DADA_3' => '配送中',
          'DADA_9' => '未妥投',
          'DADA_10' => '妥投异常',
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
      ];
        $receiptType = ['logistics' => '快递配送', 'ziti' => '上门自提', 'dada' => '同城配'];

        $communityOrderRelService = new CommunityOrderRelActivityService();
        $communityActivityService = new CommunityActivityService();
        $tradeService = new TradeService();
        $aftersalesService = new AftersalesService();
        $normalOrdersRelSupplierRepository = app('registry')->getManager('default')->getRepository(NormalOrdersRelSupplier::class);

        for ($j = 1; $j <= $fileNum; $j++) {
            $orderdata = $orderService->getOrderItemList($filter, $j, $limit, $orderBy);

            // 用户信息
            $userIds = array_filter($orderdata['user_ids']);
            if ($userIds) {
                $uFilter = [
                  'company_id' => $filter['company_id'],
                  'user_id' => $userIds,
              ];
                $userList = $memberService->getMemberInfoList($uFilter, 1, $limit);
                $userData = array_column($userList['list'], null, 'user_id');
            }

            // 支付信息
            $orderIdList = array_column($orderdata['list'], 'order_id');
            $tradeList = $tradeService->getSuccTradeInfoByOrderIdList($filter['company_id'], $orderIdList);

            // 订单信息
            $orderListData = $orderService->getOrderList(['company_id' =>$filter['company_id'] , 'order_id' => $orderIdList], 1, -1, [], false);
            $orderListMap = array_column($orderListData['list'], null, 'order_id');

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

            // 店铺信息
            $storeIds = array_filter($orderdata['distributor_ids']);
            if ($storeIds) {
                $sFilter = [
                  'company_id' => $filter['company_id'],
                  'distributor_id' => $storeIds,
              ];
                $storeList = $distributorService->getDistributorOriginalList($sFilter, 1, $limit);
                $storeData = array_column($storeList['list'], null, 'distributor_id');
            }

            // 供应商信息
            $supplierIds = array_column($orderdata['list'], 'supplier_id');
            if ($supplierIds) {
                $supplierService = new SupplierService();
                $supplierList = $supplierService->lists(['company_id' => $filter['company_id'], 'operator_id' => $supplierIds]);
                $supplierData = array_column($supplierList['list'], null, 'operator_id');
            }

            // 退款信息
            $refundMap = [];
            foreach ($orderdata['list'] as $k => $v) {
                $refundMap[$v['order_id']][$v['item_id']] = $aftersalesService->getAftersaleRefundInfoByOrderIdItemId($v['order_id'], $v['item_id'], $filter['company_id'], $v['supplier_id']);
            }
            // 售前退款，不会存在售后单
            $refundMapForward = $aftersalesService->getSuccAftersaleRefundInfoByOrderIdList($filter['company_id'], $orderIdList);

            // 自提信息
            $ordersRelZitiService = new NormalOrdersRelZitiService();
            $zitiList = $ordersRelZitiService->getLists(['order_id' => $orderIdList]);
            $zitiData = array_column($zitiList, null, 'order_id');

            // 供应商运费
            $supplierFreigthFilter = [
                'company_id' => $filter['company_id'],
                'order_id' => $orderIdList,
                'supplier_id' => $filter['supplier_id'] ?? 0,
            ];
            $supplierMap = []; // 订单按supplier_id运费运费
            $supplierFreigthList = $normalOrdersRelSupplierRepository->getLists($supplierFreigthFilter);
            foreach ($supplierFreigthList as $k => $v) {
                if (!isset($supplierMap[$v['order_id']]['freight'])) {
                    $supplierMap[$v['order_id']]['freight'] = 0;
                }
                $supplierMap[$v['order_id']]['freight'] += $v['freight_fee'];
            }

            $orderList = [];
            foreach ($orderdata['list'] as $newData) {
              //兼容已自提订单的子订单的发货状态错误的问题，子订单发货状态错误的原因待查
                if ($newData['ziti_status'] == 'DONE' && $newData['order_delivery_status'] == 'DONE') {
                    $newData['delivery_status'] = $newData['order_delivery_status'];
                    $newData['delivery_time'] = $newData['order_delivery_time'];
                    $newData['delivery_code'] = $newData['order_delivery_corp'];
                    $newData['delivery_corp'] = $newData['order_delivery_code'];
                }

                // 处理达达订单状态
                $dada_status = $dadaRelList[$newData['order_id']]['dada_status'] ?? '';
                $dada_status = 'DADA_'.$dada_status;
                if ($newData['receipt_type'] == 'dada' && isset($orderStatus[$dada_status])) {
                    $newData['order_status'] = $dada_status;
                }
                app('log')->info('order_id:'.$newData['order_id'].',receipt_type:'.$newData['receipt_type'].',order_status:'.$newData['order_status'].',dada_status:'.$dada_status);
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
                $username = $userData[$newData['user_id']]['username'] ?? '';
                if ($datapassBlock) {
                    $newData['mobile'] = data_masking('mobile', (string) $newData['mobile']);
                    $username = data_masking('truename', (string) $username);
                    $newData['receiver_name'] = data_masking('truename', (string) $newData['receiver_name']);
                    $newData['receiver_mobile'] = data_masking('mobile', (string) $newData['receiver_mobile']);
                    $newData['receiver_address'] = data_masking('address', (string) $newData['receiver_address']);
                }
                $payType = $newData['pay_type'] ?? '';
                $orderItem = [
                  'order_id' => "\"'" . $newData['order_id'] . "\"",//
                  'supplier_name' => $supplierData[$newData['supplier_id']]['supplier_name'] ?? '',
                  'id' => $newData['id'],//
                  'item_name' => str_replace('#', '', $newData['item_name']) ,//
                  'cost_price' => bcdiv($newData['cost_price'], 100, 2),
                  'num' => $newData['num'],
                   'cost_fee' => bcdiv($newData['cost_fee'], 100, 2),
                   'freight_fee' => isset($supplierMap[$newData['order_id']]['freight']) ? bcdiv($supplierMap[$newData['order_id']]['freight'], 100, 2) : 0,
                   'refund_num' => isset($refundMap[$newData['order_id']][$newData['item_id']]['num']) ? $refundMap[$newData['order_id']][$newData['item_id']]['num'] : 0, 
                   'refund_cost_price' => bcdiv($newData['cost_price'] * ($refundMap[$newData['order_id']][$newData['item_id']]['num'] ?? 0), 100, 2),
                    //   'commission_fee' => bcdiv($newData['commission_fee'], 100, 2),
                  'store_name' => $storeData[$newData['distributor_id']]['name'] ?? '',
                  'store_code' => $storeData[$newData['distributor_id']]['shop_code'] ?? '',
                  'mobile' => $newData['mobile'],//
                  'user_name' => $username,
                  'create_time' => isset($newData['create_time']) ? date('Y-m-d H:i:s', $newData['create_time']) : '',
                  'pay_time' => isset($tradeList[$newData['order_id']]['time_expire']) ? date('Y-m-d H:i:s', $tradeList[$newData['order_id']]['time_expire']) : '', // 支付时间
                  'refunded_fee' => bcdiv($refundMap[$newData['order_id']][$newData['item_id']]['refund_fee'] ?? 0, 100, 2),
                  'refunded_point_fee' => bcdiv($refundMap[$newData['order_id']][$newData['item_id']]['refund_point'] ?? 0, 100, 2),
                  'order_class' => $orderClass[$newData['order_class']],//
                  'order_status'=> $newData['order_status_msg'],//
                  'end_time' => isset($orderListMap[$newData['order_id']]['end_time']) ? date('Y-m-d H:i:s', $orderListMap[$newData['order_id']]['end_time']) : '', // 订单完成时间
                  'receipt_type' => $receiptType[$newData['receipt_type']],//
                  'ziti_status' => ($newData['ziti_status'] == 'DONE') ? '已自提' : '',//
                  'receiver_name' => $this->clearSpecialChars($newData['receiver_name']),//
                  'receiver_mobile' => $newData['receiver_mobile'],//
                  'receiver_zip' => $newData['receiver_zip'],//
                  'receiver_state' => $newData['receiver_state'],//
                  'receiver_city' => $newData['receiver_city'],//
                  'receiver_district' => $newData['receiver_district'],//
                  'receiver_address' => $this->clearSpecialChars($newData['receiver_address']),//
                  'delivery_status' => ($newData['delivery_status'] == 'DONE') ? '已发货' : '未发货',
                  'delivery_time' => ($newData['delivery_status'] == 'DONE') ? date('Y-m-d H:i:s', $newData['delivery_time']) : '0',
                  'delivery_code' => ($newData['delivery_status'] == 'DONE') ? "\"'".$newData['delivery_code']."\"" : '',
                  'delivery_corp' => ($newData['delivery_status'] == 'DONE') ? $newData['delivery_corp'] : '',
                  'pay_type' => $payTypes[$payType] ?? $payType,
                  'item_bn' => is_numeric($newData['item_bn']) ? "\"'".$newData['item_bn']."\"" : $newData['item_bn'],
                  'aftersales_status' => $aftersales_status[$newData['aftersales_status']] ?? '',
                  'refund_time' => isset($refundMap[$newData['order_id']][$newData['item_id']]['refund_success_time']) ? date('Y-m-d H:i:s', $refundMap[$newData['order_id']][$newData['item_id']]['refund_success_time']) : '',
                  'item_spec_desc' => isset($newData['item_spec_desc']) && $newData['item_spec_desc'] ? str_replace(',', '，', $newData['item_spec_desc']) : '',
                  'remark' => $newData['remark'],
                  'pickup_address' => isset($zitiData[$newData['order_id']]) ? $zitiData[$newData['order_id']]['province'].$zitiData[$newData['order_id']]['city'].$zitiData[$newData['order_id']]['area'].$zitiData[$newData['order_id']]['address'] : '',
                  'pickup_datetime' => isset($zitiData[$newData['order_id']]) ? $zitiData[$newData['order_id']]['pickup_date'].' '.$zitiData[$newData['order_id']]['pickup_time'][0].'~'.$zitiData[$newData['order_id']]['pickup_time'][1] : '',
                ];
                // 退款方式处理, 1如果是支付取消，不走售后单，这样数据就原数据返回，如果走售后就取售后数据
                if (isset($orderListMap[$newData['order_id']]['order_status']) && $orderListMap[$newData['order_id']]['order_status'] == 'CANCEL' && $orderListMap[$newData['order_id']]['pay_status'] == 'PAYED') {
                    $orderItem['refund_num'] = $newData['num'];
                    $orderItem['refunded_point_fee'] = bcdiv($newData['point_fee'], 100, 2);
                    $orderItem['refunded_fee'] = bcdiv($newData['total_fee'], 100, 2);
                    $orderItem['refund_cost_price'] = bcdiv($newData['cost_price'] * $newData['num'], 100, 2);
                    $orderItem['refund_time'] =  isset($refundMapForward[$newData['order_id']]['refund_success_time']) ? date('Y-m-d H:i:s', $refundMapForward[$newData['order_id']]['refund_success_time']) : '';
                }

                $orderList[] = $orderItem;
            }
            yield $orderList;
        }
    }

    public function getSupplierListsBak($filter, $totalCount = 10000, $datapassBlock)
    {
        $limit = 2000;
        $fileNum = ceil($totalCount / $limit);

        $memberService = new MemberService();
        $distributorService = new DistributorService();
        $orderService = $this->getOrderService('normal');
        $orderBy = ['distributor_id' => 'desc', 'order_id' => 'desc', 'create_time' => 'asc', 'id' => 'desc'];
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
        $orderStatus = [
            'NOTPAY' => '未支付',
            'CANCEL' => '已取消',
            'CANCEL_WAIT_PROCESS' => '取消待处理',
            'DONE' => '已完成',
            'PAYED' => '待发货',
            'REFUND_SUCCESS' => '退款完成',
            'WAIT_BUYER_CONFIRM' => '待收货',
            'REVIEW_PASS' => '审核通过待出库',
            'DADA_0' => '店铺待接单',
            'DADA_1' => '骑士待接单',
            'DADA_2' => '待取货',
            'DADA_100' => '骑士到店',
            'DADA_3' => '配送中',
            'DADA_9' => '未妥投',
            'DADA_10' => '妥投异常',
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
        ];
        $receiptType = ['logistics' => '快递配送', 'ziti' => '上门自提', 'dada' => '同城配'];

        $communityOrderRelService = new CommunityOrderRelActivityService();
        $communityActivityService = new CommunityActivityService();
        $tradeService = new TradeService();
        for ($j = 1; $j <= $fileNum; $j++) {
            $orderdata = $orderService->getOrderItemList($filter, $j, $limit, $orderBy);

            $userIds = array_filter($orderdata['user_ids']);
            if ($userIds) {
                $uFilter = [
                    'company_id' => $filter['company_id'],
                    'user_id' => $userIds,
                ];
                $userList = $memberService->getMemberInfoList($uFilter, 1, $limit);
                $userData = array_column($userList['list'], null, 'user_id');
            }

            $orderIdList = array_column($orderdata['list'], 'order_id');
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

            $storeIds = array_filter($orderdata['distributor_ids']);
            if ($storeIds) {
                $sFilter = [
                    'company_id' => $filter['company_id'],
                    'distributor_id' => $storeIds,
                ];
                $storeList = $distributorService->getDistributorOriginalList($sFilter, 1, $limit);
                $storeData = array_column($storeList['list'], null, 'distributor_id');
            }

            $supplierIds = array_column($orderdata['list'], 'supplier_id');
            if ($supplierIds) {
                $operatorsService = new OperatorsService();
                $supplierList = $operatorsService->lists(['company_id' => $filter['company_id'], 'operator_id' => $supplierIds, 'operator_type' => 'supplier']);
                $supplierData = array_column($supplierList['list'], null, 'operator_id');
            }

            $ordersRelZitiService = new NormalOrdersRelZitiService();
            $zitiList = $ordersRelZitiService->getLists(['order_id' => $orderIdList]);
            $zitiData = array_column($zitiList, null, 'order_id');

            $orderList = [];
            foreach ($orderdata['list'] as $newData) {

                //兼容已自提订单的子订单的发货状态错误的问题，子订单发货状态错误的原因待查
                if ($newData['ziti_status'] == 'DONE' && $newData['order_delivery_status'] == 'DONE') {
                    $newData['delivery_status'] = $newData['order_delivery_status'];
                    $newData['delivery_time'] = $newData['order_delivery_time'];
                    $newData['delivery_code'] = $newData['order_delivery_corp'];
                    $newData['delivery_corp'] = $newData['order_delivery_code'];
                }

                // 处理达达订单状态
                $dada_status = $dadaRelList[$newData['order_id']]['dada_status'] ?? '';
                $dada_status = 'DADA_'.$dada_status;
                if ($newData['receipt_type'] == 'dada' && isset($orderStatus[$dada_status])) {
                    $newData['order_status'] = $dada_status;
                }
                app('log')->info('order_id:'.$newData['order_id'].',receipt_type:'.$newData['receipt_type'].',order_status:'.$newData['order_status'].',dada_status:'.$dada_status);
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
                $username = $userData[$newData['user_id']]['username'] ?? '';
                if ($datapassBlock) {
                    $newData['mobile'] = data_masking('mobile', (string) $newData['mobile']);
                    $username = data_masking('truename', (string) $username);
                    $newData['receiver_name'] = data_masking('truename', (string) $newData['receiver_name']);
                    $newData['receiver_mobile'] = data_masking('mobile', (string) $newData['receiver_mobile']);
                    $newData['receiver_address'] = data_masking('address', (string) $newData['receiver_address']);
                }
                $payType = $newData['pay_type'] ?? '';
                $orderItem = [
                    // 'original_order_id' => "\"'" . $newData['original_order_id'] . "\"",//
                    'order_id' => $newData['order_id'],//
                    // 'trade_no' => $tradeIndex[$newData['order_id']] ?? '-',
                    'supplier_name' => $supplierData[$newData['supplier_id']]['username'] ?? '',
                    'id' => $newData['id'],//
                    'item_name' => str_replace('#', '', $newData['item_name']) ,//
                    'item_fee' => bcdiv($newData['item_fee'], 100, 2),
                    'commission_fee' => bcdiv($newData['commission_fee'], 100, 2),
                    'price' => bcdiv($newData['price'], 100, 2),
                    'num' => $newData['num'],
                    'store_name' => $storeData[$newData['distributor_id']]['name'] ?? '',
                    'store_code' => $storeData[$newData['distributor_id']]['shop_code'] ?? '',
                    'mobile' => $newData['mobile'],//
                    'user_name' => $username,
                    'create_time' => date('Y-m-d H:i:s', $newData['create_time']),
                    // 'freight_fee' => bcdiv($newData['freight_fee'], 100, 2),//
                    // 'total_fee' => bcdiv($newData['total_fee'], 100, 2),//
                    // 'discount_fee' => bcdiv($disountFee, 100, 2),
                    // 'discount_info' => $discountDesc,
                    'refunded_fee' => bcdiv($newData['refunded_fee'], 100, 2),
                    'order_class' => $orderClass[$newData['order_class']],//
                    'order_status'=> $newData['order_status_msg'],//
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
                    'delivery_code' => ($newData['delivery_status'] == 'DONE') ? $newData['delivery_code'] : '',
                    'delivery_corp' => ($newData['delivery_status'] == 'DONE') ? $newData['delivery_corp'] : '',
                    // 'kunnr' => $thirdParams['kunnr'] ?? '',
                    'pay_type' => $payTypes[$payType] ?? $payType,
                    'item_bn' => $newData['item_bn'] ?? '',
                    'aftersales_status' => $aftersales_status[$newData['aftersales_status']] ?? '',
                    'item_spec_desc' => isset($newData['item_spec_desc']) && $newData['item_spec_desc'] ? str_replace(',', '，', $newData['item_spec_desc']) : '',
                    'remark' => $newData['remark'],
                    'pickup_address' => isset($zitiData[$newData['order_id']]) ? $zitiData[$newData['order_id']]['province'].$zitiData[$newData['order_id']]['city'].$zitiData[$newData['order_id']]['area'].$zitiData[$newData['order_id']]['address'] : '',
                    'pickup_datetime' => isset($zitiData[$newData['order_id']]) ? $zitiData[$newData['order_id']]['pickup_date'].' '.$zitiData[$newData['order_id']]['pickup_time'][0].'~'.$zitiData[$newData['order_id']]['pickup_time'][1] : '',
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

    public function getPointsmallLists($filter, $totalCount = 10000, $datapassBlock)
    {
        $limit = 2000;
        $fileNum = ceil($totalCount / $limit);

        $memberService = new MemberService();
        $distributorService = new DistributorService();
        $orderService = $this->getOrderService('normal');
        $orderBy = ['distributor_id' => 'desc', 'order_id' => 'desc', 'create_time' => 'asc', 'id' => 'desc'];
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
        $orderStatus = [
            'NOTPAY' => '未支付',
            'CANCEL' => '已取消',
            'CANCEL_WAIT_PROCESS' => '取消待处理',
            'DONE' => '已完成',
            'PAYED' => '已支付审核中',
            'REFUND_SUCCESS' => '退款完成',
            'WAIT_BUYER_CONFIRM' => '待收货',
            'REVIEW_PASS' => '审核通过待出库',
        ];
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
        $receiptType = ['logistics' => '快递配送', 'ziti' => '上门自提', 'dada' => '同城配'];

        $tradeService = new TradeService();
        for ($j = 1; $j <= $fileNum; $j++) {
            $orderdata = $orderService->getOrderItemList($filter, $j, $limit, $orderBy);
            $orderIdList = array_column($orderdata['list'], 'order_id');
            $tradeIndex = $tradeService->getTradeIndexByOrderIdList($filter['company_id'], $orderIdList);

            $userIds = array_filter($orderdata['user_ids']);
            if ($userIds) {
                $uFilter = [
                    'company_id' => $filter['company_id'],
                    'user_id' => $userIds,
                ];
                $userList = $memberService->getMemberInfoList($uFilter, 1, $limit);
                $userData = array_column($userList['list'], null, 'user_id');
            }

            $orderList = [];
            foreach ($orderdata['list'] as $newData) {
                $username = $userData[$newData['user_id']]['username'] ?? '';
                if ($datapassBlock) {
                    $newData['mobile'] = data_masking('mobile', (string) $newData['mobile']);
                    $username = data_masking('truename', (string) $username);
                    $newData['receiver_name'] = data_masking('truename', (string) $newData['receiver_name']);
                    $newData['receiver_mobile'] = data_masking('mobile', (string) $newData['receiver_mobile']);
                    $newData['receiver_address'] = data_masking('address', (string) $newData['receiver_address']);
                }
                $payType = $newData['pay_type'] ?? '';
                $orderList[] = [
                    'order_id' => $newData['order_id'],//
                    'trade_no' => $tradeIndex[$newData['order_id']] ?? '-',
                    'id' => $newData['id'],//
                    'item_name' => str_replace('#', '', $newData['item_name']),//
                    'item_point' => $newData['item_point'] . '积分',
                    'point' => $newData['point'] . '积分',
                    'num' => $newData['num'],
                    'mobile' => $newData['mobile'],//
                    'user_name' => $username,
                    'create_time' => date('Y-m-d H:i:s', $newData['create_time']),
                    'total_fee' => bcdiv($newData['total_fee'], 100, 2),//
                    // 'refunded_fee' => $newData['refunded_fee'],
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
                    'delivery_code' => ($newData['delivery_status'] == 'DONE') ? $newData['delivery_code'] : '',
                    'delivery_corp' => ($newData['delivery_status'] == 'DONE') ? $newData['delivery_corp'] : '',
                    'pay_type' => $payTypes[$payType] ?? $payType,
                    'item_bn' => $newData['item_bn'] ?? '',
                    'item_spec_desc' => $newData['item_spec_desc'] ?? '',
                    'remark' => $newData['remark'],
                ];
            }
            yield $orderList;
        }
    }

}
