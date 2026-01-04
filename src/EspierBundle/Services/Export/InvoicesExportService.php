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
use GoodsBundle\Services\ItemsCategoryService;
use GoodsBundle\Services\ItemsService;
use MembersBundle\Services\MemberService;
use OrdersBundle\Traits\GetOrderServiceTrait;
use EspierBundle\Services\ExportFileService;
use OrdersBundle\Services\TradeService;
use SupplierBundle\Services\SupplierOrderService;
use OrdersBundle\Services\OrderInvoiceService;

class InvoicesExportService implements ExportFileInterface
{
    use GetOrderServiceTrait;
    protected $order_class = '';
    protected $orderInvoiceService;
    public function __construct()
    {
        $this->orderInvoiceService = new OrderInvoiceService();
    }

    public function exportData($filter)
    {
        // dd($filter);
        $this->order_class = $filter['order_class'] ?? '';

        $supplier_id = $filter['supplier_id'] ?? 0;
        if ($supplier_id) {
            $orderService = new SupplierOrderService();
            $count = $orderService->countOrderNum($filter);
            if (!$count) {
                return [];
            }
            $fileName = date('YmdHis').$filter['company_id']."supplier_invoice";
            $title = $this->getSupplierTitle();
            $orderList = $this->getSupplierList($filter, $count);
        } else {
            $orderService = $this->getOrderService('normal');
            $count = $orderService->countOrderNum($filter);
            if (!$count) {
                return [];
            }
            $fileName = date('YmdHis').$filter['company_id']."invoice";
            $title = $this->getTitle();
            $orderList = $this->getLists($filter, $count);
        }

        $exportService = new ExportFileService();
        // 指定需要作为文本处理的数字字段，避免 Excel 显示为科学计数法
        $textFields = ['order_id'];
        $result = $exportService->exportCsv($fileName, $title, $orderList, $textFields);
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


    private function getTitle()
    {
        return [
            'order_id' => '订单号',
            'trade_no'=> '订单序号',
            'name' => '用户名',
            'order_status' => '订单状态',
            'end_time' => '订单完成时间',
            'item_name' => '商品名称',
            'item_spec_desc' => '规格',
            'category_tree' => '管理分类',
            'price' => '商品销售单价',
            'cost_price' => '成本/计算单价',
            'item_num' => '购买数量',
            'item_fee' => '销售总金额（¥）',
            'commission_fee' => '商品佣金',
            'cost_fee' => '成本总金额（¥）',
            'freight_fee' => '运费（总）',
            'total_fee_total' => '实付金额（总）',
            'point_fee' => '积分抵扣（¥）',
            'total_fee' => '现金实付（¥）',//
            'discount_fee' => '优惠总金额',
            'discount_info' => '优惠详情',
            'content' => '发票抬头',
            'registration_number' => '税号',
            'company_address' => '单位地址',
            'company_phone' => '电话号码',
            'bankname' => '开户银行',
            'bankaccount' => '银行账户',
            'email' => '收票邮箱',
            'invoice_status' => '发票状态',
            'invoice_amount' => '发票金额',
            'invoice_type' => '发票类型',
            'invoice_type_code' => '发票类型编码',
            'invoice_method' => '发票方式',
            'invoice_source' => '发票来源',
            'remark' => '备注',
        ];
    }

    private function getSupplierTitle()
    {
        return [
            'order_id' => '订单号',
            'trade_no'=> '订单序号',
            'name' => '用户名',
            'order_status' => '订单状态',
            'end_time' => '订单完成时间',
            'item_name' => '商品名称',
            'item_spec_desc' => '规格',
            'category_tree' => '管理分类',
            'cost_price' => '成本/计算单价',
            'item_num' => '购买数量',
            'cost_fee' => '成本总金额（¥）',
            'freight_fee' => '运费（总）',
            'content' => '发票抬头',
            'registration_number' => '税号',
            'company_address' => '单位地址',
            'company_phone' => '电话号码',
            'bankname' => '开户银行',
            'bankaccount' => '银行账户',
            'email' => '收票邮箱',
        ];
    }

    private function getTitleBak()
    {
        return [
            'order_id' => '订单号',
            'trade_no'=> '订单序号',
            'item_name' => '商品名称',
            'item_attr' => '规格',
            'item_num' => '数量',
            'total_price' => '总价（元）',
            'content' => '发票抬头',
            'bankname' => '开户银行',
            'bankaccount' => '银行账户',
            'company_phone' => '电话号码',
            'company_address' => '单位地址',
            'registration_number' => '税号',
        ];
    }

    public function getLists($filter, $totalCount = 10000)
    {
        $limit = 2000;
        $fileNum = ceil($totalCount / $limit);

        $memberService = new MemberService();
        $orderService = $this->getOrderService('normal');
        $orderBy = ['distributor_id' => 'desc', 'order_id' => 'desc', 'create_time' => 'asc', 'id' => 'desc'];

        $communityOrderRelService = new CommunityOrderRelActivityService();
        $communityActivityService = new CommunityActivityService();
        $tradeService = new TradeService();
        $itemCategorySerivce = new ItemsCategoryService();
        $itemsService = new ItemsService();
        for ($j = 1; $j <= $fileNum; $j++) {
            app('log')->info(':'.__CLASS__.':'.__FUNCTION__.':'.__LINE__.':filter:'.json_encode($filter));
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


            //{"comment":"开票状态：待开票：pending，开票中：inProgress，开票成功：success，已作废：waste，开票失败：failed", "default":"pending"})
            // 发票信息 待开票，开票中，开票成功
            $filter_invoice = [ 
                'company_id' => $filter['company_id'],
                'order_id' => $orderIdList,
                'invoice_status' => ['pending','success','inProgress','waste','failed'],
            ];
            $invoiceList = $this->orderInvoiceService->getInvoiceList($filter_invoice,1,10000);
            $invoiceListMap = array_column($invoiceList['list'], null, 'order_id');
            //

            // 社区拼团订单关联信息
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

            // 分类信息
            $itemIdList  = array_column($orderdata['list'], 'item_id');
            $itemCategoryList = $itemsService->getAllItems(['item_id' => $itemIdList, 'company_id' => $filter['company_id']], ['item_id','item_category']);
            $itemPathName = [];
            if (!empty($itemCategoryList)) {
                foreach ($itemCategoryList as $k => $v) {
                    $itemPathName[$v['item_id']] = $itemCategorySerivce->getCategoryPathNameById($v['item_category'],$filter['company_id'], true);
                }
            }
            //

            $orderList = [];
            foreach ($orderdata['list'] as $newData) {
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
                // 发票信息
                if (isset($orderListMap[$newData['order_id']]['invoice'])) {
                    $invoice = $orderListMap[$newData['order_id']]['invoice'];
                    $invoicearr = is_array($invoice) ? $invoice : json_decode($invoice, true);
                    $newData = array_merge($newData, $invoicearr);
                }




                $orderItem = [
                    'order_id' => $newData['order_id'],//
                    'trade_no' => $tradeList[$newData['order_id']]['trade_no'] ?? '',
                    'name' => $userData[$newData['user_id']]['name'] ?? '',
                    'order_status'=> $newData['order_status_msg'],//
                    'end_time' => isset($orderListMap[$newData['order_id']]['end_time']) ? date('Y-m-d H:i:s', $orderListMap[$newData['order_id']]['end_time']) : '', // 订单完成时间
                    'item_name' => str_replace('#', '', $newData['item_name']) ,//
                    'item_spec_desc' => isset($newData['item_spec_desc']) && $newData['item_spec_desc'] ? str_replace(',', '，', $newData['item_spec_desc']) : '',
                    'category_tree' => isset($itemPathName[$newData['item_id']]) ? implode('->', $itemPathName[$newData['item_id']]) : '',
                    'price' => bcdiv($newData['price'], 100, 2),
                    'cost_price' => bcdiv($newData['cost_price'], 100, 2),//
                    'item_num' => $newData['num'],
                    'item_fee' => bcdiv($newData['item_fee'], 100, 2),
                    'commission_fee' => bcdiv($newData['commission_fee'], 100, 2),
                    'cost_fee' => bcdiv($newData['cost_fee'], 100, 2),//, // 成本总金额
                    'freight_fee' => $orderListMap[$newData['order_id']]['freight_fee'] ?? 0, // 运费
                    'total_fee_total' => bcdiv($newData['total_fee'] + $newData['freight_fee'] + $newData['point_fee'], 100, 2), // 实付金额(总)
                    'point_fee' => bcdiv($newData['point_fee'], 100, 2),
                    'total_fee' => bcdiv($newData['total_fee'], 100, 2),//
                    'discount_fee' => bcdiv($disountFee, 100, 2),
                    'discount_info' => $discountDesc,
                    'content' => $newData['content'] ?? '',
                    'registration_number' => $newData['registration_number'] ?? '',
                    'company_address' => $newData['company_address'] ?? '',
                    'company_phone' => $newData['company_phone'] ?? '',
                    'bankname' => $newData['bankname'] ?? '',
                    'bankaccount' => empty($newData['bankaccount']) ? '' : "\t".$newData['bankaccount']."\t",
                    'email' => $newData['email'] ?? '',
                ];

                // invoiceListMap
                if(isset($invoiceListMap[$newData['order_id']])){
                    $invoice = $invoiceListMap[$newData['order_id']];
                    $invoicearr = is_array($invoice) ? $invoice : json_decode($invoice, true);
                    // 'content' => '发票抬头',
                    // 'registration_number' => '税号',
                    // 'company_address' => '单位地址',
                    // 'company_phone' => '电话号码',
                    // 'bankname' => '开户银行',
                    // 'bankaccount' => '银行账户',
                    // 'email' => '收票邮箱',
                    $orderItem['content'] = $invoicearr['company_title'] ?? '';
                    $orderItem['registration_number'] = $invoicearr['company_tax_number'] ?? '';
                    $orderItem['company_address'] = $invoicearr['company_address'] ?? '';
                    $orderItem['company_phone'] = $invoicearr['company_telephone'] ?? '';
                    $orderItem['bankname'] = $invoicearr['bank_name'] ?? '';
                    $orderItem['bankaccount'] = $invoicearr['bank_account'] ?? '';
                    $orderItem['email'] = $invoicearr['email'] ?? '';
                    $orderItem['invoice_status'] = $invoicearr['invoice_status'] ?? '';
                    $orderItem['invoice_amount'] = $invoicearr['invoice_amount'] ?? '';
                    $orderItem['invoice_type'] = $invoicearr['invoice_type'] ?? '';
                    $orderItem['invoice_type_code'] = $invoicearr['invoice_type_code'] ?? '';
                    $orderItem['invoice_method'] = $invoicearr['invoice_method'] ?? '';
                    $orderItem['invoice_source'] = $invoicearr['invoice_source'] ?? '';
                    $orderItem['remark'] = $invoicearr['remark'] ?? '';
                }


                $orderList[] = $orderItem;
            }
            yield $orderList;
        }
    }

    public function getListsBak($filter, $totalCount = 10000)
    {
        $limit = 1000;
        $fileNum = ceil($totalCount / $limit);
        $supplier_id = $filter['supplier_id'] ?? 0;
        /**
         * @var \OrdersBundle\Services\Orders\AbstractNormalOrder $orderService
         */
        $orderService = $this->getOrderService('normal');
        $orderBy = ['distributor_id' => 'desc', 'order_id' => 'desc', 'create_time' => 'asc'];
        $orderList = [];
        $tradeService = new TradeService();
        for ($j = 1; $j <= $fileNum; $j++) {
            $page = $j;
            if ($supplier_id) {
                $supplierOrderService = new SupplierOrderService();
                $rs = $supplierOrderService->repository->getLists($filter, '*', $j, $limit, $orderBy);
                $orderIds = array_column($rs, 'order_id');
                $filter = [
                    'company_id' => $filter['company_id'],
                    'order_id' => $orderIds,
                    'supplier_id' => $supplier_id,
                ];
                $page = 1;
            }
            $orderdata = $orderService->getOrderList($filter, $page, $limit, $orderBy, false)['list'];
            $orderIdList = array_column($orderdata, 'order_id');
            $tradeIndex = $tradeService->getTradeIndexByOrderIdList($filter['company_id'], $orderIdList);

            foreach ($orderdata as $newData) {
                if (isset($newData['invoice'])) {
                    $invoicearr = is_array($newData['invoice']) ? $newData['invoice'] : json_decode($newData['invoice'], true);
                    $newData = array_merge($newData, $invoicearr);
                }

                foreach ($newData['items'] as $item) {
                    $orderList[] = [
                        'order_id'=> $newData['order_id'],
                        'trade_no' => $tradeIndex[$newData['order_id']] ?? '-',
                        'item_name' => $item['item_name'],
                        'item_attr' => $item['item_spec_desc'],
                        'item_num' => $item['num'],
                        'total_price' => bcdiv($item['total_fee'], 100, 2),
                        'content' => $newData['content'] ?? '',
                        'bankname' => $newData['bankname'] ?? '',
                        'bankaccount' => empty($newData['bankaccount']) ? '' : "\t".$newData['bankaccount']."\t",
                        'company_phone' => $newData['company_phone'] ?? '',
                        'company_address' => $newData['company_address'] ?? '',
                        'registration_number' => $newData['registration_number'] ?? '',
                    ];
                }
            }
            yield $orderList;
        }
    }

    public function getSupplierList($filter, $totalCount = 10000)
    {
        $limit = 2000;
        $fileNum = ceil($totalCount / $limit);

        $memberService = new MemberService();
        $orderService = $this->getOrderService('normal');
        $orderBy = ['distributor_id' => 'desc', 'order_id' => 'desc', 'create_time' => 'asc', 'id' => 'desc'];

        $communityOrderRelService = new CommunityOrderRelActivityService();
        $communityActivityService = new CommunityActivityService();
        $tradeService = new TradeService();
        $itemCategorySerivce = new ItemsCategoryService();
        $itemsService = new ItemsService();
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
            $orderListData = $orderService->getOrderList(['company_id' => $filter['company_id'], 'order_id' => $orderIdList], 1, -1, [], false);
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

            // 分类信息
            $itemIdList = array_column($orderdata['list'], 'item_id');
            $itemCategoryList = $itemsService->getAllItems(['item_id' => $itemIdList, 'company_id' => $filter['company_id']], ['item_id', 'item_category']);
            $itemPathName = [];
            if (!empty($itemCategoryList)) {
                foreach ($itemCategoryList as $k => $v) {
                    $itemPathName[$v['item_id']] = $itemCategorySerivce->getCategoryPathNameById($v['item_category'], $filter['company_id'], true);
                }
            }

            $orderList = [];
            foreach ($orderdata['list'] as $newData) {
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
                // 发票信息
                if (isset($orderListMap[$newData['order_id']]['invoice'])) {
                    $invoice = $orderListMap[$newData['order_id']]['invoice'];
                    $invoicearr = is_array($invoice) ? $invoice : json_decode($invoice, true);
                    $newData = array_merge($newData, $invoicearr);
                }
                $orderItem = [
                    'order_id' => $newData['order_id'],//
                    'trade_no' => $tradeList[$newData['order_id']]['trade_no'] ?? '',
                    'name' => $userData[$newData['user_id']]['name'] ?? '',
                    'order_status' => $newData['order_status_msg'],//
                    'end_time' => isset($orderListMap[$newData['order_id']]['end_time']) ? date('Y-m-d H:i:s', $orderListMap[$newData['order_id']]['end_time']) : '', // 订单完成时间
                    'item_name' => str_replace('#', '', $newData['item_name']),//
                    'item_spec_desc' => isset($newData['item_spec_desc']) && $newData['item_spec_desc'] ? str_replace(',', '，', $newData['item_spec_desc']) : '',
                    'category_tree' => isset($itemPathName[$newData['item_id']]) ? implode('->', $itemPathName[$newData['item_id']]) : '',
                    'cost_price' => bcdiv($newData['cost_price'], 100, 2),//
                    'item_num' => $newData['num'],
                    'cost_fee' => bcdiv($newData['cost_fee'], 100, 2),//, // 成本总金额
                    'freight_fee' => $orderListMap[$newData['order_id']]['freight_fee'] ?? 0, // 运费
                    'content' => $newData['content'] ?? '',
                    'registration_number' => $newData['registration_number'] ?? '',
                    'company_address' => $newData['company_address'] ?? '',
                    'company_phone' => $newData['company_phone'] ?? '',
                    'bankname' => $newData['bankname'] ?? '',
                    'bankaccount' => empty($newData['bankaccount']) ? '' : "\t" . $newData['bankaccount'] . "\t",
                    'email' => $newData['email'] ?? '',
                ];

                $orderList[] = $orderItem;
            }
            yield $orderList;
        }
    }

}
