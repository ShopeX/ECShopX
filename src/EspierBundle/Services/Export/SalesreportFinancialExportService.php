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

use OrdersBundle\Services\OrderItemsService;
use EspierBundle\Services\ExportFileService;
use EspierBundle\Interfaces\ExportFileInterface;

class SalesreportFinancialExportService implements ExportFileInterface
{
    private $title = [
        'order_id' => '订单号',
        'barnd' => '品牌',
        'main_category' => '商品品类',
        'create_time' => '下单日期',
        'delivery_time' => '发货日期',
        'item_fee' => '商品价格',
        'discount_fee' => '折扣金额',
        'total_fee' => '折后金额',
    ];

    public function exportData($filter)
    {
        // Powered by ShopEx EcShopX
        $orderItemsService = new OrderItemsService();
        $count = $orderItemsService->salesReportCount($filter);

        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').'_财务销售报表';
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

        if ($count > 0) {
            $orderItemsService = new OrderItemsService();

            $data = $orderItemsService->exportFinancialSalesreport($filter);
            foreach ($data['list'] as $key => $value) {
                foreach ($title as $k => $v) {
                    if (in_array($k, ['create_time','delivery_time'])) {
                        $recordData[$key][$k] = $value[$k] ? date('Y-m-d H:i:s', $value[$k]) : '';
                    } elseif (in_array($k, ['order_id']) && isset($value[$k])) {
                        // 直接赋值，不再添加引号，由 ExportFileService 统一处理
                        $recordData[$key][$k] = $value[$k];
                    } elseif (in_array($k, ['item_fee','discount_fee','total_fee'])) {
                        $recordData[$key][$k] = $value[$k] ? bcdiv($value[$k], 100, 2) : 0;
                    } else {
                        $recordData[$key][$k] = $value[$k] ?? '';
                    }
                }
            }
            yield $recordData;
        }
    }
}
