<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services\Export;

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;
use HfPayBundle\Services\HfpayDistributorTransactionStatisticsService;

class HfpayTradeRecordExportService implements ExportFileInterface
{
    private $title = [
        'distributor_name' => '店铺名称',
        'withdrawal_balance' => '可提现金额',
        'order_count' => '交易总笔数',
        'order_total_fee' => '总交易金额',
        'order_refund_count' => '已退款总笔数',
        'order_refund_total_fee' => '退款总金额',
        'order_refunding_count' => '在退总笔数',
        'order_refunding_total_fee' => '在退总金额',
        'order_profit_sharing_charge' => '已结算手续费总额',
        'order_un_profit_sharing_charge' => '未结算手续费总额',
    ];

    public function exportData($filter)
    {
        $service = new HfpayDistributorTransactionStatisticsService();
        $count = $service->transactionCount($filter);

        $fileName = date('YmdHis') . '_汇付分账交易';
        $datalist = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $datalist);

        return $result;
    }

    private function getLists($filter, $count)
    {
        $title = $this->title;

        $service = new HfpayDistributorTransactionStatisticsService();
        $limit = 500;
        $fileNum = ceil($count / $limit);
        for ($page = 1; $page <= $fileNum; $page++) {
            $recordData = [];
            $data = $service->transactionList($filter, $page, $limit);
            if (!empty($data['list'])) {
                foreach ($data['list'] as $key => $value) {
                    foreach ($title as $k => $v) {
                        if ($k == "withdrawal_balance" || $k == "order_total_fee" || $k == "order_refund_total_fee" || $k == "order_refunding_total_fee" || $k == "order_profit_sharing_charge" || $k == "order_un_profit_sharing_charge") {
                            $recordData[$key][$k] = bcdiv($value[$k], 100, 2);
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
