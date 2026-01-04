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

use ChinaumsPayBundle\Services\ChinaumsPayDivisionService;

class DivisionDetailExportService implements ExportFileInterface
{
    public function exportData($filter)
    {
        
        $divisionService = new ChinaumsPayDivisionService();
        $count = $divisionService->getDetailCount($filter);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').$filter['company_id']."分账单明细";
        $title = $this->getTitle();
        $orderList = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $title, $orderList);
        return $result;
    }

    private function getTitle()
    {
        $title = [
            'division_id' => '指令ID',
            'order_id' => '订单号',
            'total_fee' => '订单金额',
            'actual_fee' => '实际金额',
            'commission_rate_fee' => '收单手续费',
            'division_fee' => '分账金额',
            'create_time' => '创建时间',
        ];
        return $title;
    }
    private function getLists($filter, $count)
    {
        $title = $this->getTitle();
        
        $divisionService = new ChinaumsPayDivisionService();

        $limit = 500;
        $orderBy = ['id' => 'DESC'];
        $total = ceil($count / $limit);

        for ($i = 1; $i <= $total; $i++) {
            $dataList = [];
            $divisionDetailList = $divisionService->getDetailList($filter, '*', $i, $limit, $orderBy);
            foreach ($divisionDetailList['list'] as $key => $divisionDetail) {
                foreach ($title as $k => $v) {
                    if (in_array($k, ['division_id', 'order_id']) && isset($divisionDetail[$k])) {
                        $dataList[$key][$k] = "\t".$divisionDetail[$k];
                    } elseif (in_array($k, ['total_fee', 'actual_fee', 'division_fee', 'commission_rate_fee']) && isset($divisionDetail[$k])) {
                        if (!$divisionDetail[$k]) {
                            $divisionDetail[$k] = 0;
                        }
                        $dataList[$key][$k] = $divisionDetail[$k] / 100;
                    } elseif (in_array($k, ['create_time']) && isset($divisionDetail[$k]) && $divisionDetail[$k]) {
                        $dataList[$key][$k] = date('Y-m-d H:i:s', $divisionDetail[$k]);
                    } elseif ($k == "back_status" && isset($divisionDetail[$k])) {
                        $dataList[$key][$k] = $backStatus[$divisionDetail[$k]] ?? '--';
                    } else {
                        $dataList[$key][$k] = '--';
                    }
                }
            }
            yield $dataList;
        }
    }
}
