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

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;
use OrdersBundle\Services\StatementsService;
use DistributionBundle\Services\DistributorService;
use MerchantBundle\Services\MerchantService;
use SupplierBundle\Services\SupplierService;

class StatementsExportService implements ExportFileInterface
{
    private $title = [
        'statement_no' => '结算单号',
        'merchant_name' => '商家',
        'distributor_name' => '店铺',
        'order_num' => '订单数量',
        'total_fee' => '订单实付',
        'freight_fee' => '运费',
        'intra_city_freight_fee' => '同城配',
        'refund_fee' => '退款金额',
        'statement_fee' => '结算金额',
        'statement_period' => '结算周期',
        'confirm_time' => '确认时间',
        'statement_time' => '结算时间',
        'statement_status' => '结算状态',
    ];

    private $supplierTitle = [
        'statement_no' => '结算单号',
        'supplier_name' => '供应商',
        'order_num' => '订单数量',
        'total_total_fee' => '订单实付总金额(￥)',
        'total_fee' => '现金实付（￥）',
        'point_fee' => '积分抵扣',
        'freight_fee' => '运费（总）',
        'refund_num' => '退货数量',
        'refund_fee' => '退款金额',
        'refund_point' => '退款积分',
        'refund_cost_fee' => '退货成本',
        'statement_fee' => '结算金额（￥）',
        'statement_period' => '结算周期',
        'confirm_time' => '确认时间',
        'statement_time' => '结算时间',
        'statement_status' => '结算状态',
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
        $statementsService = new StatementsService();
        $count = $statementsService->count($filter);
        if (!$count) {
            return [];
        }

        if (isset($filter['merchant_type']) && $filter['merchant_type'] == 'supplier') {
            $fileName = date('YmdHis').$filter['company_id'].'supplier_statements';
            $list = $this->getSupplierList($filter, $count);
        }else {
            $fileName = date('YmdHis').$filter['company_id'].'statements';
            $list = $this->getList($filter, $count);
        }

        $exportService = new ExportFileService();
        // 指定需要作为文本处理的数字字段，避免 Excel 显示为科学计数法
        $textFields = ['statement_no'];
        $result = $exportService->exportCsv($fileName, $this->getTitle($filter), $list, $textFields);
        return $result;
    }

    private function getList($filter, $count)
    {
        $statementsService = new StatementsService();
        $distributorService = new DistributorService();
        $merchantService = new MerchantService();
        $supplierService = new SupplierService();

        $limit = 500;
        $orderBy = ['created' => 'DESC'];
        $title = $this->getTitle($filter);
        $pageNum = ceil($count / $limit);
        for ($page = 1; $page <= $pageNum; $page++) {
            $result = [];
            $list = $statementsService->getLists($filter, '*', $page, $limit, $orderBy);

            if (count($list) > 0) {
                $distributorList = $distributorService->getLists(['distributor_id' => array_column($list, 'distributor_id')], 'distributor_id,name');
                $distributorName = array_column($distributorList, 'name', 'distributor_id');

                $merchantList = $merchantService->getLists(['id' => array_column($list, 'merchant_id')], 'id,merchant_name');
                $merchantName = array_column($merchantList, 'merchant_name', 'id');

                //供应商名称转换
                $supplierData = $supplierService->repository->getLists(['id' => array_column($list, 'supplier_id')], 'id, supplier_name');
                $supplierNames = array_column($supplierData, 'name', 'id');
            }

            foreach ($list as $key => $value) {
                foreach ($title as $k => $v) {
                    switch ($k) {
                        case 'statement_no':
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
                            if ($value['supplier_id']) {
                                $result[$key][$k] = $supplierNames[$value['supplier_id']] ?? '-';
                            } else {
                                $result[$key][$k] = $merchantName[$value['merchant_id']] ?? '-';
                            }
                            break;
                        case 'distributor_name':
                            $result[$key][$k] = $distributorName[$value['distributor_id']] ?? '-';
                            break;
                        case 'statement_period':
                            $result[$key][$k] = date('Y-m-d H:i:s', $value['start_time']).'~'.date('Y-m-d H:i:s', $value['end_time']);
                            break;
                        case 'confirm_time':
                        case 'statement_time':
                            if ($value['statement_time']) {
                                $result[$key][$k] = date('Y-m-d H:i:s', $value[$k]);
                            } else {
                                $result[$key][$k] = '-';
                            }
                            break;
                        case 'statement_status':
                            if ($value['statement_status'] == 'done') {
                                $result[$key][$k] = '已结算';
                            } elseif ($value['statement_status'] == 'confirmed') {
                                $result[$key][$k] = '待平台结算';
                            } else {
                                $result[$key][$k] = '待商家确认';
                            }
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
        $statementsService = new StatementsService();
        $supplierService = new SupplierService();

        $limit = 500;
        $orderBy = ['created' => 'DESC'];
        $title = $this->getTitle($filter);
        $pageNum = ceil($count / $limit);
        for ($page = 1; $page <= $pageNum; $page++) {
            $result = [];
            $list = $statementsService->getLists($filter, '*', $page, $limit, $orderBy);

            if (count($list) > 0) {
                $supplierData = $supplierService->repository->getLists(['id' => array_column($list, 'supplier_id')], 'id, supplier_name');
                $supplierNames = array_column($supplierData, 'name', 'id');
            }

            foreach ($list as $key => $value) {
                foreach ($title as $k => $v) {
                    switch ($k) {
                        case 'statement_no':
                            // 直接赋值，不再添加引号，由 ExportFileService 统一处理
                            $result[$key][$k] = $value[$k];
                            break;
                        case 'supplier_name':
                            $result[$key][$k] = $supplierNames[$value['supplier_id']] ?? '-';
                            break;
                        case 'order_num':
                            $result[$key][$k] = $value['order_num'] ?? 0;
                            break;
                        case 'total_total_fee':
                            $result[$key][$k] = bcdiv($value['total_fee'] + $value['point_fee'], 100, 2);
                            break;
                        case 'total_fee':
                        case 'point_fee':
                        case 'freight_fee':
                            $result[$key][$k] = bcdiv($value[$k], 100, 2);
                            break;
                        case 'refund_num':
                            $result[$key][$k] = $value['refund_num'] ?? 0;
                            break;
                        case 'refund_fee':
                            $result[$key][$k] = bcdiv($value['refund_fee'], 100, 2);
                            break;
                        case 'refund_point':
                            $result[$key][$k] = bcdiv($value['refund_point'], 100, 2);
                            break;
                        case 'refund_cost_fee':
                            $result[$key][$k] = bcdiv($value['refund_cost_fee'], 100, 2);
                            break;    
                        case 'statement_fee':
                            $result[$key][$k] = bcdiv($value['statement_fee'], 100, 2);
                            break;
                        case 'statement_period':
                            $result[$key][$k] = date('Y-m-d H:i:s', $value['start_time']).'~'.date('Y-m-d H:i:s', $value['end_time']);
                            break;
                        case 'confirm_time':
                        case 'statement_time':
                            if ($value['statement_time']) {
                                $result[$key][$k] = date('Y-m-d H:i:s', $value[$k]);
                            } else {
                                $result[$key][$k] = '-';
                            }
                            break;
                        case 'statement_status':
                            if ($value['statement_status'] == 'done') {
                                $result[$key][$k] = '已结算';
                            } elseif ($value['statement_status'] == 'confirmed') {
                                $result[$key][$k] = '待平台结算';
                            } else {
                                $result[$key][$k] = '待商家确认';
                            }
                            break;
                        default:
                            $result[$key][$k] = $value[$k] ?? '-';
                            break;
                    }
                }
            }
            yield $result;
        }
    }

}
