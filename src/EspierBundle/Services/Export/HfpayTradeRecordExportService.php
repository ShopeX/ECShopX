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
