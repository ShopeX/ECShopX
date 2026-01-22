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
use AftersalesBundle\Services\AftersalesRefundService;
use EspierBundle\Services\ExportFileService;
use EspierBundle\Interfaces\ExportFileInterface;
use OrdersBundle\Services\TradeService;
use OrdersBundle\Services\Orders\NormalOrderService;
use SupplierBundle\Services\SupplierService;
use SalespersonBundle\Services\SalespersonService;
use CompanysBundle\Ego\CompanysActivationEgo;
use CompanysBundle\Repositories\OperatorsRepository;

class AftersalesRecordExportService implements ExportFileInterface
{
    private $title = [
        'distributor_name' => '店铺名称',
        'shop_code' => '店铺号',
        'aftersales_bn' => '售后单号',
        'order_id' => '订单号',
        'trade_no' => '订单序号',
        'item_bn' => '商品编号',
        'item_name' => '商品名称',
        'num' => '数量',
        'aftersales_type' => '售后类型',
        'aftersales_status' => '售后状态',
        'create_time' => '创建时间',
        'refund_fee' => '退款商品金额',
        'refund_point' => '退款抵扣积分',
        'refund_freight_fee' => '退款运费金额（¥）',
        'refund_freight_point' => '退款运费（积分）',
        'refunded_fee' => '实退金额',
        'refunded_point' => '实退积分',
        'progress' => '处理进度',
        'description' => '申请描述',
        'reason' => '申请售后原因',
        'refuse_reason' => '拒绝原因',
        'memo' => '售后备注',
        'salesman_name' => '导购',
        'order_holder' => '订单分类',
        'supplier_name' => '来源供应商',
        'self_delivery_operator_name' => '配送员'
    ];

    public function exportData($filter)
    {
        $aftersalesService = new AftersalesService();
        $count = $aftersalesService->count($filter);

        if (!$count) {
            return [];
        }
        
        // 获取公司产品模式，动态调整title
        $companysActivationEgo = new CompanysActivationEgo();
        $companyInfo = $companysActivationEgo->check($filter['company_id']);
        $productModel = $companyInfo['product_model'] ?? config('common.product_model', 'platform');
        
        // 根据product_model调整title，仅在standard时显示导购字段
        $title = $this->title;
        if ($productModel != 'standard') {
            unset($title['salesman_name']);
        }
        
        $fileName = date('YmdHis').'_售后列表';
        $datalist = $this->getLists($filter, $count, $productModel);

        $exportService = new ExportFileService();
        // 指定需要作为文本处理的数字字段，避免 Excel 显示为科学计数法
        $textFields = ['order_id', 'aftersales_bn', 'item_bn'];
        $result = $exportService->exportCsv($fileName, $title, $datalist, $textFields);
        return $result;
    }

    private function getLists($filter, $count, $productModel = null)
    {
        // 根据product_model调整title，仅在standard时显示导购字段
        $title = $this->title;
        if ($productModel && $productModel != 'standard') {
            unset($title['salesman_name']);
        }

        $aftersales_type = [
            'ONLY_REFUND' => '仅退款',
            'REFUND_GOODS' => '退货退款',
            'EXCHANGING_GOODS' => '换货',
        ];

        $aftersales_status = [
            0 => '待处理',
            1 => '处理中',
            2 => '已处理',
            3 => '已驳回',
            4 => '已关闭',
        ];

        $progress = [
            0 => '等待商家处理',
            1 => '商家接受申请，等待消费者回寄',
            2 => '消费者回寄，等待商家收货确认',
            3 => '已驳回',
            4 => '已处理',
            5 => '退款驳回',
            6 => '退款完成',
            7 => '售后关闭',
            8 => '商家确认收货,等待审核退款',
            9 => '退款处理中',
        ];

        $orderHolder = [
            'self' => '自营订单',
            'distributor' => '商家订单',
            'supplier' => '供应商订单',
            'self_supplier' => '自营+供应商订单',
            'distributor_supplier' => '商家+供应商订单'
        ];

        if ($count > 0) {
            $aftersalesService = new AftersalesService();
            $tradeService = new TradeService();
            $aftersalesRefundService = new AftersalesRefundService();
            $normalOrderService = new NormalOrderService();
            $supplierService = new SupplierService();
            $salespersonService = new SalespersonService();
            $companysActivationEgo = new CompanysActivationEgo();
            $operatorsRepository = app('registry')->getManager('default')->getRepository(\CompanysBundle\Entities\Operators::class);

            // 获取公司产品模式（参照api/company/activate接口的实现）
            $companyInfo = $companysActivationEgo->check($filter['company_id']);
            $productModel = $companyInfo['product_model'] ?? config('common.product_model', 'platform');
            app('log')->debug('AftersalesRecordExportServic::company_id:'.$filter['company_id'].'companyInfo===>'.json_encode($companyInfo));
            $limit = 500;
            $fileNum = ceil($count / $limit);

            for ($page = 1; $page <= $fileNum; $page++) {
                $recordData = [];
                $data = $aftersalesService->exportAftersalesList($filter, $page, $limit, ["create_time" => "DESC"]);

                $orderIdList = array_column($data['list'], 'order_id');
                $tradeIndex = $tradeService->getTradeIndexByOrderIdList($filter['company_id'], $orderIdList);

                // 获取订单信息（order_holder, salesman_id, self_delivery_operator_id）
                $orderData = [];
                if (!empty($orderIdList)) {
                    $orderRs = $normalOrderService->normalOrdersRepository->getList(
                        ['company_id' => $filter['company_id'], 'order_id' => $orderIdList], 
                        0, 
                        -1, 
                        null, 
                        'order_id, order_holder, salesman_id, self_delivery_operator_id'
                    );
                    if ($orderRs) {
                        $orderData = array_column($orderRs, null, 'order_id');
                    }
                }

                // 获取售后单的supplier_id和self_delivery_operator_id（从aftersales表获取）
                $aftersalesBns = array_column($data['list'], 'aftersales_bn');
                $aftersalesSupplierData = [];
                $aftersalesOperatorData = [];
                if (!empty($aftersalesBns)) {
                    $conn = app('registry')->getConnection('default');
                    $aftersalesQuery = $conn->createQueryBuilder();
                    $aftersalesQuery->select('aftersales_bn, supplier_id, self_delivery_operator_id')
                        ->from('aftersales', 'a')
                        ->where($aftersalesQuery->expr()->in('a.aftersales_bn', ':bns'));
                    $aftersalesQuery->setParameter('bns', $aftersalesBns, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                    $aftersalesRs = $aftersalesQuery->execute()->fetchAll();
                    $aftersalesSupplierData = array_column($aftersalesRs, 'supplier_id', 'aftersales_bn');
                    $aftersalesOperatorData = array_column($aftersalesRs, 'self_delivery_operator_id', 'aftersales_bn');
                }

                // 获取供应商信息
                $supplierData = [];
                $supplierIds = array_filter(array_unique(array_values($aftersalesSupplierData)));
                if (!empty($supplierIds)) {
                    $rs = $supplierService->repository->getLists(['operator_id' => $supplierIds], 'operator_id, supplier_name');
                    $supplierData = array_column($rs, 'supplier_name', 'operator_id');
                }

                // 获取导购信息（仅当product_model为standard时）
                $salespersonList = [];
                if ($productModel == 'standard') {
                    $salespersonIds = array_filter(array_unique(array_column($orderRs ?? [], 'salesman_id')));
                    if (!empty($salespersonIds)) {
                        $filterSalesperson['company_id'] = $filter['company_id'];
                        $filterSalesperson['salesperson_id'] = $salespersonIds;
                        $filterSalesperson['salesperson_type'] = 'shopping_guide';
                        $salespersonListTmp = $salespersonService->getLists($filterSalesperson, 'salesperson_id,name,work_userid', 1, -1);
                        $salespersonList = array_column($salespersonListTmp, null, 'salesperson_id');
                    }
                }

                // 获取配送员信息（从售后单的self_delivery_operator_id获取）
                $selfDeliveryOperator = [];
                $selfDeliveryOperatorIds = array_filter(array_unique(array_values($aftersalesOperatorData)));
                if (!empty($selfDeliveryOperatorIds)) {
                    $operatorListResult = $operatorsRepository->lists(['operator_id' => $selfDeliveryOperatorIds]);
                    $operatorList = $operatorListResult['list'] ?? [];
                    $selfDeliveryOperator = array_column($operatorList, 'username', 'operator_id');
                }

                // 获取售后退款数据
                $aftersalesBnData = [];
                if (!empty($aftersalesBns)) {
                    $aftersalesBnTmp = $aftersalesRefundService->getRefundsList(['company_id' => $filter['company_id'], 'aftersales_bn' => $aftersalesBns], 0, -1);
                    $aftersalesBnData = array_column($aftersalesBnTmp['list'], null, 'aftersales_bn');
                }

                foreach ($data['list'] as $key => $value) {
                    $value['trade_no'] = $tradeIndex[$value['order_id']] ?? '-';
                    // 获取退款数据
                    $refundData = $aftersalesBnData[$value['aftersales_bn']] ?? [];
                    
                    // 获取订单信息
                    $orderInfo = $orderData[$value['order_id']] ?? [];
                    
                    // 获取供应商ID
                    $supplierId = $aftersalesSupplierData[$value['aftersales_bn']] ?? 0;
                    
                    // 获取售后单的配送员ID
                    $aftersalesOperatorId = $aftersalesOperatorData[$value['aftersales_bn']] ?? 0;
                    
                    foreach ($title as $k => $v) {
                        if ($k == 'create_time') {
                            $recordData[$key][$k] = date('Y-m-d H:i:s', $value[$k]);
                        } elseif (in_array($k, ['order_id', 'aftersales_bn']) && isset($value[$k])) {
                            // 直接赋值，不再添加引号，由 ExportFileService 统一处理
                            $recordData[$key][$k] = $value[$k];
                        } elseif ($k == 'salesman_name') {
                            // 导购：仅在product_model为standard时显示
                            if ($productModel == 'standard') {
                                $salesmanId = $orderInfo['salesman_id'] ?? 0;
                                $recordData[$key][$k] = $salespersonList[$salesmanId]['work_userid'] ?? '';
                            } else {
                                $recordData[$key][$k] = '';
                            }
                        } elseif ($k == 'order_holder') {
                            // 订单分类：转换为中文
                            $orderHolderValue = $orderInfo['order_holder'] ?? '';
                            $recordData[$key][$k] = $orderHolder[$orderHolderValue] ?? ($orderHolderValue ?: '');
                        } elseif ($k == 'supplier_name') {
                            // 来源供应商
                            $recordData[$key][$k] = $supplierData[$supplierId] ?? '';
                        } elseif ($k == 'self_delivery_operator_name') {
                            // 配送员（从售后单的self_delivery_operator_id获取）
                            $recordData[$key][$k] = $selfDeliveryOperator[$aftersalesOperatorId] ?? '';
                        } elseif ($k == 'refund_fee') {
                            $recordData[$key][$k] = $value[$k] / 100;
                        } elseif ($k == 'refund_point') {
                            // 退款抵扣积分：有退款记录但值为0时显示0，无退款记录时显示--
                            if (!empty($refundData) && array_key_exists('refund_point', $refundData)) {
                                $recordData[$key][$k] = $refundData['refund_point'];
                            } else {
                                $recordData[$key][$k] = '--';
                            }
                        } elseif ($k == 'refund_freight_fee') {
                            // 退款运费金额（¥），根据freight_type判断，如果是cash需要转换为元，没有的则显示0
                            if (!empty($refundData) && isset($refundData['freight_type']) && $refundData['freight_type'] == 'cash' && isset($refundData['freight'])) {
                                $recordData[$key][$k] = $refundData['freight'] / 100;
                            } else {
                                $recordData[$key][$k] = 0;
                            }
                        } elseif ($k == 'refund_freight_point') {
                            // 退款运费（积分），根据freight_type判断，如果是point不用转换，没有的则显示0
                            if (!empty($refundData) && isset($refundData['freight_type']) && $refundData['freight_type'] == 'point' && isset($refundData['freight'])) {
                                $recordData[$key][$k] = $refundData['freight'];
                            } else {
                                $recordData[$key][$k] = 0;
                            }
                        } elseif ($k == 'refunded_fee') {
                            // 实退金额，需转换为元：有退款记录但值为0时显示0，无退款记录时显示--
                            if (!empty($refundData) && array_key_exists('refunded_fee', $refundData)) {
                                $recordData[$key][$k] = $refundData['refunded_fee'] / 100;
                            } else {
                                $recordData[$key][$k] = '--';
                            }
                        } elseif ($k == 'refunded_point') {
                            // 实退积分：有退款记录但值为0时显示0，无退款记录时显示--
                            if (!empty($refundData) && array_key_exists('refunded_point', $refundData)) {
                                $recordData[$key][$k] = $refundData['refunded_point'];
                            } else {
                                $recordData[$key][$k] = '--';
                            }
                        } elseif ($k == "aftersales_type") {
                            $recordData[$key][$k] = $aftersales_type[$value[$k]] ?? '--';
                        } elseif ($k == "aftersales_status") {
                            $recordData[$key][$k] = $aftersales_status[$value[$k]] ?? '--';
                        } elseif ($k == "progress") {
                            $recordData[$key][$k] = $progress[$value[$k]] ?? '--';
                        } elseif ($k == 'item_bn') {
                            // 直接赋值，不再判断是否为数字，不再添加引号，由 ExportFileService 统一处理
                            $recordData[$key][$k] = $value[$k] ?? '';
                        } else {
                            $recordData[$key][$k] = $value[$k] ?? '';
                        }
                    }
                }
                yield $recordData;
            }
        }
    }
}
