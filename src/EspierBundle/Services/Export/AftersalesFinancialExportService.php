<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services\Export;

use AftersalesBundle\Services\AftersalesService;
use EspierBundle\Services\ExportFileService;
use EspierBundle\Interfaces\ExportFileInterface;

class AftersalesFinancialExportService implements ExportFileInterface
{
    private $title = [
        'refund_bn' => '退款单号',
        'aftersales_bn' => '售后单号',
        'order_id' => '订单号',
        'refund_status' => '退款状态',
        'refund_fee' => '退款金额',
        'refund_point' => '退款积分',
        'create_time' => '创建时间',
        'refund_success_time' => '退款成功时间',
    ];

    public function exportData($filter)
    {
        $aftersalesService = new AftersalesService();
        $count = $aftersalesService->count($filter);

        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').'_退款单';
        $datalist = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        // 指定需要作为文本处理的数字字段，避免 Excel 显示为科学计数法
        $textFields = ['order_id', 'refund_bn', 'aftersales_bn'];
        $result = $exportService->exportCsv($fileName, $this->title, $datalist, $textFields);
        return $result;
    }

    private function getLists($filter, $count)
    {
        $title = $this->title;

        if ($count > 0) {
            $aftersalesService = new AftersalesService();

            $limit = 500;
            $fileNum = ceil($count / $limit);

            for ($page = 1; $page <= $fileNum; $page++) {
                $recordData = [];
                $data = $aftersalesService->exportFinancialAftersalesList($filter, $page, $limit, ["create_time" => "DESC"]);
                foreach ($data['list'] as $key => $value) {
                    foreach ($title as $k => $v) {
                        if ($k == 'refund_success_time' || $k == 'create_time') {
                            $recordData[$key][$k] = date('Y-m-d H:i:s', $value[$k]);
                        } elseif (in_array($k, ['order_id', 'refund_bn', 'aftersales_bn']) && isset($value[$k])) {
                            // 直接赋值，不再添加引号，由 ExportFileService 统一处理
                            $recordData[$key][$k] = $value[$k];
                        } elseif ($k == 'refund_fee') {
                            $recordData[$key][$k] = $value[$k] / 100;
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
