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
use HfPayBundle\Services\HfpayStatisticsService;

class HfpayOrderRecordExportService implements ExportFileInterface
{
    private $title = [
        'create_time' => '时间',
        'order_id' => '订单号',
        'profitsharing_status' => '结算状态',
        'total_fee' => '交易金额',
        'charge' => '平台手续费',
        'distributor_name' => '店铺名称',
        'refund_fee' => '退款金额',
        'order_status' => '订单状态',
    ];

    public function exportData($filter)
    {
        $aftersalesService = new HfpayStatisticsService();
        $count = $aftersalesService->getOrderCount($filter['company_id'], $filter);

        $fileName = date('YmdHis') . '_汇付订单交易';
        $datalist = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        // 指定需要作为文本处理的数字字段，避免 Excel 显示为科学计数法
        $textFields = ['order_id'];
        $result = $exportService->exportCsv($fileName, $this->title, $datalist, $textFields);

        return $result;
    }

    private function getLists($filter, $count)
    {
        $title = $this->title;
        $profitsharing_status = [
            1 => '未结算',
            2 => '已结算',
        ];

        $hfpay_trade_record_service = new HfpayStatisticsService();
        $limit = 500;
        $fileNum = ceil($count / $limit);
        for ($page = 1; $page <= $fileNum; $page++) {
            $recordData = [];
            $data = $hfpay_trade_record_service->getOrderList($filter['company_id'], $filter, $page, $limit, ["create_time" => "DESC"]);
            if (!empty($data['list'])) {
                foreach ($data['list'] as $key => $value) {
                    foreach ($title as $k => $v) {
                        if ($k == 'order_id') {
                            // 直接赋值，不再添加引号，由 ExportFileService 统一处理
                            $recordData[$key][$k] = $value[$k];
                        } elseif ($k == 'profitsharing_status') {
                            $recordData[$key][$k] = $profitsharing_status[$value[$k]] ?? '--';
                        } elseif ($k == "total_fee") {
                            $recordData[$key][$k] = bcdiv($value[$k], 100, 2);
                        } elseif ($k == "charge") {
                            $recordData[$key][$k] = bcdiv($value[$k], 100, 2);
                        } elseif ($k == "refund_fee") {
                            $recordData[$key][$k] = bcdiv($value[$k], 100, 2);
                        } elseif ($k == "order_status") {
                            $recordData[$key][$k] = config('order.hfpayOrderStatus')[$value[$k]] ?? $value[$k];
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
