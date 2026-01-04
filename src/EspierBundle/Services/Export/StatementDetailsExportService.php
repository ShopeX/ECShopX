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

use EspierBundle\Services\ExportFileService;
use MerchantBundle\Services\MerchantService;
use OrdersBundle\Services\Orders\NormalOrderService;
use OrdersBundle\Services\StatementsService;
use SupplierBundle\Services\SupplierService;
use EspierBundle\Interfaces\ExportFileInterface;
use OrdersBundle\Services\StatementDetailsService;
use DistributionBundle\Services\DistributorService;

class StatementDetailsExportService implements ExportFileInterface
{
    private $title = [
        'order_id' => '订单号',
        'merchant_name' => '商户名称：供应商 or 店铺',//distributor_name
        'total_fee' => '订单实付',
        'freight_fee' => '运费',
        'intra_city_freight_fee' => '同城配',
        'refund_fee' => '退款金额',
        'statement_fee' => '结算金额',
        'created' => '创建时间',
        'pay_type' => '支付方式',
    ];
    
     private $supplierTitle = [
        'order_id' => '订单号',
        'supplier_name' => '供应商',
        'num' => '购买数量',
        'total_total_fee' => '订单实付总金额(￥)',
        'total_fee' => '现金实付（￥）',
        'point_fee' => '积分抵扣',
        'freight_fee' => '运费（总）',
        'cost_fee' => '总结算金额',
        'refund_num' => '退货数量',
        'refund_fee' => '退款金额',
        'refund_point' => '退款积分',
        'refund_cost_fee' => '退货结算价',
        'statement_fee' => '结算实付',
        'created' => '创建时间',
        'end_time' => '订单完成时间',
        'statement_time' => '结算时间',
        'pay_type' => '支付方式',
    ];

    public function getTitle($filter)
    {
        $title = $this->title;
        if (isset($filter['merchant_type']) && $filter['merchant_type'] == 'supplier') {
            $title = $this->supplierTitle;  
        }
        return $title;
    }

    public function exportData($filter)
    {
        $detailsService = new StatementDetailsService();
        $count = $detailsService->count($filter);
        if (!$count) {
            return [];
        }

        if (isset($filter['merchant_type']) && $filter['merchant_type'] == 'supplier') {
            $fileName = date('YmdHis').$filter['company_id'].'supplier_statement_details';
            $list = $this->getSupplierList($filter, $count);
        }else {
            $fileName = date('YmdHis').$filter['company_id'].'statement_details';
            $list = $this->getList($filter, $count);
        }
       
        $exportService = new ExportFileService();
        // 指定需要作为文本处理的数字字段，避免 Excel 显示为科学计数法
        $textFields = ['order_id'];
        $result = $exportService->exportCsv($fileName, $this->getTitle($filter), $list, $textFields);
        return $result;
    }

    private function getList($filter, $count)
    {
        $detailsService = new StatementDetailsService();
        $distributorService = new DistributorService();
        $supplierService = new SupplierService();
        // $merchantService = new MerchantService();

        $limit = 500;
        $orderBy = ['created' => 'DESC'];
        $title = $this->getTitle($filter);
        $pageNum = ceil($count / $limit);

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
        ];

        for ($page = 1; $page <= $pageNum; $page++) {
            $result = [];
            $list = $detailsService->getLists($filter, '*', $page, $limit, $orderBy);

            if (count($list) > 0) {
                $distributorList = $distributorService->getLists(['distributor_id' => array_column($list, 'distributor_id')], 'distributor_id,name');
                $distributorName = array_column($distributorList, 'name', 'distributor_id');
                
                //供应商名称转换
                $supplierData = $supplierService->repository->getLists(['id' => array_column($list, 'supplier_id')], 'id, supplier_name');
                $supplierNames = array_column($supplierData, 'name', 'id');

                // $merchantList = $merchantService->getLists(['id' => array_column($list, 'merchant_id')], 'id,merchant_name');
                // $merchantName = array_column($merchantList, 'merchant_name', 'id');
            }

            foreach ($list as $key => $value) {
                foreach ($title as $k => $v) {
                    switch ($k) {
                        case 'order_id':
                            // 直接赋值，不再添加引号，由 ExportFileService 统一处理
                            $result[$key][$k] = $value[$k];
                            break;
                        case 'total_fee':
                        case 'freight_fee':
                        case 'intra_city_freight_fee':
                        case 'refund_fee':
                        case 'statement_fee':
                            $result[$key][$k] = bcdiv($value[$k], 100, 2);
                            break;
                        case 'merchant_name':
                            if ($value['distributor_id']) {
                                $result[$key][$k] = $distributorName[$value['distributor_id']] ?? '-';
                            } else {
                                $result[$key][$k] = $supplierNames[$value['supplier_id']] ?? '-';
                            }
                            break;
                        case 'created':
                            $result[$key][$k] = date('Y-m-d H:i:s', $value['created']);
                            break;
                        case 'pay_type':
                            $result[$key][$k] = $payTypes[$value[$k]];
                            break;
                        default:
                            $result[$key][$k] = $value[$k];
                            break;
                    }
                }
            }
            yield $result;
        }
    }

    private function getSupplierList($filter, $count)
    {
        $detailsService = new StatementDetailsService();
        $distributorService = new DistributorService();
        $supplierService = new SupplierService();
        // $merchantService = new MerchantService();
        $statementsService = new StatementsService();
        $normalOrderService = new NormalOrderService();

        $limit = 500;
        $orderBy = ['created' => 'DESC'];
        $title = $this->getTitle($filter);
        $pageNum = ceil($count / $limit);

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
        ];

        for ($page = 1; $page <= $pageNum; $page++) {
            $result = [];
            $list = $detailsService->getLists($filter, '*', $page, $limit, $orderBy);

            if (count($list) > 0) {
                $distributorList = $distributorService->getLists(['distributor_id' => array_column($list, 'distributor_id')], 'distributor_id,name');
                $distributorName = array_column($distributorList, 'name', 'distributor_id');
                
                //供应商名称转换
                $supplierData = $supplierService->repository->getLists(['id' => array_column($list, 'supplier_id')], 'id, supplier_name');
                $supplierNames = array_column($supplierData, 'name', 'id');
                
                //获取结算信息
                $statementsInfo = $statementsService->getInfo(['company_id' => $filter['company_id'], 'id' => $filter['statement_id']]);
                
                //获取订单信息
                $orderListData = $normalOrderService->getOrderList(['company_id' => $filter['company_id'], 'order_id' => array_column($list, 'order_id')]);
                $orderListMap  = array_column($orderListData, 'end_time', 'order_id');

                // $merchantList = $merchantService->getLists(['id' => array_column($list, 'merchant_id')], 'id,merchant_name');
                // $merchantName = array_column($merchantList, 'merchant_name', 'id');
            }

            foreach ($list as $key => $value) {
                foreach ($title as $k => $v) {
                    switch ($k) {
                        case 'order_id':
                            // 直接赋值，不再添加引号，由 ExportFileService 统一处理
                            $result[$key][$k] = $value[$k];
                            break;
                        case 'supplier_name':
                            $result[$key][$k] = $supplierNames[$value['supplier_id']] ?? '-';
                            break;
                        case 'num':
                            $result[$key][$k] = $value['num'] ?? 0;
                            break;
                        case 'total_total_fee':   
                            $result[$key][$k] = bcdiv($value['total_fee'] + $value['point_fee'], 100, 2);
                            break; 
                        case 'total_fee':
                        case 'point_fee':
                        case 'freight_fee':
                        case 'cost_fee':
                            $result[$key][$k] = bcdiv($value[$k], 100, 2);
                            break;
                        case 'refund_num':
                            $result[$key][$k] = $value['refund_num'] ?? 0;  
                            break;
                        case 'refund_fee':  
                        case 'refund_point':
                            $result[$key][$k] = bcdiv($value[$k], 100, 2);
                            break;
                        case 'refund_cost_fee':
                            $result[$key][$k] = bcdiv($value[$k], 100, 2);
                            break;    
                        case 'statement_fee':
                            $result[$key][$k] = bcdiv($value[$k], 100, 2);
                            break;
                        case 'created':
                            $result[$key][$k] = date('Y-m-d H:i:s', $value['created']);
                            break;
                        case 'end_time':
                            $result[$key][$k] = date('Y-m-d H:i:s', $orderListMap[$value['order_id']]['end_time'] ?? 0);
                            break;  
                        case 'statement_time':  
                            if (isset($statementsInfo['statement_time'])) {
                                $result[$key][$k] = date('Y-m-d H:i:s', $statementsInfo['statement_time']);
                            } else {
                                $result[$key][$k] = '-';
                            }
                            break;
                        case 'pay_type':
                            $result[$key][$k] = $payTypes[$value[$k]];
                            break;
                        default:
                            $result[$key][$k] = $value[$k] ?? '--';
                            break;
                    }
                }
            }
            yield $result;
        }
    }

}
