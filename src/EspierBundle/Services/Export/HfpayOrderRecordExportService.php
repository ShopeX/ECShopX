<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
        $result = $exportService->exportCsv($fileName, $this->title, $datalist);

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
                            $recordData[$key][$k] = "\"'" . $value[$k] . "\"";
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
