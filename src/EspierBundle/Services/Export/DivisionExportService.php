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

class DivisionExportService implements ExportFileInterface
{
    public function exportData($filter)
    {
        
        $divisionService = new ChinaumsPayDivisionService();
        $count = $divisionService->count($filter);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').$filter['company_id']."分账单";
        $title = $this->getTitle();
        $orderList = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $title, $orderList);
        return $result;
    }

    private function getTitle()
    {
        $title = [
            'id' => '指令ID',
            'total_fee' => '订单金额',
            'actual_fee' => '实际金额',
            'division_fee' => ' 分账金额',
            'backsucc_fee' => '回盘成功金额',
            'rate_fee' => '业务处理费',
            'back_status' => '回盘状态',
            'create_time' => '创建时间',
        ];
        return $title;
    }
    private function getLists($filter, $count)
    {
        $title = $this->getTitle();
        // 回盘状态 0:未处理、1:处理中、2:成功、3:部分成功、4:失败
        $backStatus = [
            '0' => '未处理',
            '1' => '处理中',
            '2' => '成功',
            '3' => '部分成功',
            '4' => '失败',
        ];
        
        $divisionService = new ChinaumsPayDivisionService();

        $limit = 500;
        $orderBy = ['id' => 'DESC'];
        $total = ceil($count / $limit);

        for ($i = 1; $i <= $total; $i++) {
            $dataList = [];
            $divisionList = $divisionService->getLists($filter, '*', $i, $limit, $orderBy);
            foreach ($divisionList as $key => $division) {
                foreach ($title as $k => $v) {
                    if (in_array($k, ['id']) && isset($division[$k])) {
                        $dataList[$key][$k] = "\t".$division[$k];
                    } elseif (in_array($k, ['total_fee', 'actual_fee', 'division_fee', 'backsucc_fee', 'rate_fee']) && isset($division[$k])) {
                        if (!$division[$k]) {
                            $division[$k] = 0;
                        }
                        $dataList[$key][$k] = $division[$k] / 100;
                    } elseif (in_array($k, ['create_time']) && isset($division[$k]) && $division[$k]) {
                        $dataList[$key][$k] = date('Y-m-d H:i:s', $division[$k]);
                    } elseif ($k == "back_status" && isset($division[$k])) {
                        $dataList[$key][$k] = $backStatus[$division[$k]] ?? '--';
                    } else {
                        $dataList[$key][$k] = '--';
                    }
                }
            }
            yield $dataList;
        }
    }
}
