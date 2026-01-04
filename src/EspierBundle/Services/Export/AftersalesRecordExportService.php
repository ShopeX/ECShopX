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
use EspierBundle\Services\ExportFileService;
use EspierBundle\Interfaces\ExportFileInterface;
use OrdersBundle\Services\TradeService;

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
        'refund_fee' => '退款金额',
        'progress' => '处理进度',
        'description' => '申请描述',
        'reason' => '申请售后原因',
        'refuse_reason' => '拒绝原因',
        'memo' => '售后备注'
    ];

    public function exportData($filter)
    {
        $aftersalesService = new AftersalesService();
        $count = $aftersalesService->count($filter);

        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').'_售后列表';
        $datalist = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        // 指定需要作为文本处理的数字字段，避免 Excel 显示为科学计数法
        $textFields = ['order_id', 'aftersales_bn', 'item_bn'];
        $result = $exportService->exportCsv($fileName, $this->title, $datalist, $textFields);
        return $result;
    }

    private function getLists($filter, $count)
    {
        $title = $this->title;

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

        if ($count > 0) {
            $aftersalesService = new AftersalesService();
            $tradeService = new TradeService();

            $limit = 500;
            $fileNum = ceil($count / $limit);

            for ($page = 1; $page <= $fileNum; $page++) {
                $recordData = [];
                $data = $aftersalesService->exportAftersalesList($filter, $page, $limit, ["create_time" => "DESC"]);

                $orderIdList = array_column($data['list'], 'order_id');
                $tradeIndex = $tradeService->getTradeIndexByOrderIdList($filter['company_id'], $orderIdList);

                foreach ($data['list'] as $key => $value) {
                    $value['trade_no'] = $tradeIndex[$value['order_id']] ?? '-';
                    foreach ($title as $k => $v) {
                        if ($k == 'create_time') {
                            $recordData[$key][$k] = date('Y-m-d H:i:s', $value[$k]);
                        } elseif (in_array($k, ['order_id', 'aftersales_bn']) && isset($value[$k])) {
                            // 直接赋值，不再添加引号，由 ExportFileService 统一处理
                            $recordData[$key][$k] = $value[$k];
                        } elseif ($k == 'refund_fee') {
                            $recordData[$key][$k] = $value[$k] / 100;
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
