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
use HfPayBundle\Services\HfpayCashRecordService;

class HfpayWithdrawRecordExportService implements ExportFileInterface
{
    private $title = [
        'created_at' => '日期',
        'order_id' => '提现订单号',
        'bind_card_id' => '到账银行卡号',
        'trans_amt' => '提现金额',
        'distributor_name' => '店铺名称',
        'login_name' => '操作人',
        'cash_status' => '订单状态',
        'resp_desc' => '备注',
    ];

    public function exportData($filter)
    {
        $hfpayCashRecordService = new HfpayCashRecordService();
        $count = $hfpayCashRecordService->count($filter);

        $fileName = date('YmdHis') . '_店铺提现记录';
        $datalist = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        // 指定需要作为文本处理的数字字段，避免 Excel 显示为科学计数法
        $textFields = ['order_id', 'bind_card_id'];
        $result = $exportService->exportCsv($fileName, $this->title, $datalist, $textFields);

        app('log')->debug('队列导出: '. var_export($result, 1));

        return $result;
    }

    private function getLists($filter, $count)
    {
        $title = $this->title;
        $cash_status = [
            2 => '提现成功',
            3 => '提现失败',
        ];

        $hfpayCashRecordService = new HfpayCashRecordService();
        $limit = 500;
        $fileNum = ceil($count / $limit);
        for ($page = 1; $page <= $fileNum; $page++) {
            $recordData = [];
            $data = $hfpayCashRecordService->lists($filter, $page, $limit, '*', ["created_at" => "DESC"]);
            if (!empty($data['list'])) {
                foreach ($data['list'] as $key => $value) {
                    foreach ($title as $k => $v) {
                        if ($k == 'order_id' || $k == 'bind_card_id') {
                            // 直接赋值，不再添加引号，由 ExportFileService 统一处理
                            $recordData[$key][$k] = $value[$k];
                        } elseif ($k == 'cash_status') {
                            if (in_array($value[$k], [0, 1])) {
                                $recordData[$key][$k] = '提现中';
                            } else {
                                $recordData[$key][$k] = $cash_status[$value[$k]] ?? '--';
                            }
                        } elseif ($k == "trans_amt") {
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
