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

namespace BsPayBundle\Services\Export;

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;
use BsPayBundle\Services\WithdrawApplyService;

class WithdrawExportService implements ExportFileInterface
{
    public function exportData($filter)
    {
        app('log')->info('bspay::WithdrawExportService::exportData====>'.json_encode($filter));
        // 1. 获取数据总数
        $withdrawApplyService = new WithdrawApplyService();
        $count = $withdrawApplyService->count($filter);
        app('log')->info('bspay::WithdrawExportService::count====>'.$count);
        if (!$count) {
            return [];
        }

        // 2. 准备文件名和标题
        $fileName = date('YmdHis').$filter['company_id']."斗拱提现列表";
        $title = $this->getTitle();
        
        // 3. 获取数据列表
        $dataList = $this->getLists($filter, $count);
        app('log')->info('bspay::WithdrawExportService::dataList====>'.json_encode($dataList));
        // 4. 导出CSV文件
        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $title, $dataList);
        return $result;
    }

    private function getTitle()
    {
        return [
            '申请ID',
            '申请时间',
            '申请人类型',
            '申请人账号',
            '申请店铺名称',
            '申请店铺ID',
            '申请商户名称',
            '申请商户ID',
            '提现类型',
            '提现金额（元）',
            '发票',
            '审核时间',
            '审核备注',
            '审核人账号',
            '提现状态',
            '处理结果',
            '最后更新时间'
        ];
    }

    private function escapeCSV($str) 
    {
        if (empty($str)) {
            return '';
        }
        // 如果字符串包含逗号、双引号或换行符，需要用双引号包裹
        if (strpos($str, ',') !== false || strpos($str, '"') !== false || strpos($str, "\n") !== false) {
            // 将字符串中的双引号替换为两个双引号
            $str = str_replace('"', '""', $str);
            // 用双引号包裹整个字符串
            return '"' . $str . '"';
        }
        return $str;
    }

    private function getLists($filter, $count)
    {
        $withdrawApplyService = new WithdrawApplyService();
        $dataList = [];
        
        // 状态映射
        $statusMap = [
            0 => '审核中',
            1 => '审核通过',
            2 => '已拒绝',
            3 => '处理中',
            4 => '处理成功',
            5 => '处理失败'
        ];

        // 操作者类型映射
        $operatorTypeMap = [
            'distributor' => '店铺',
            'merchant' => '商户',
            'admin' => '超级管理员',
            'staff' => '员工'
        ];

        // 分批获取数据
        $page = 1;
        $pageSize = 1000;
        while (true) {
            $result = $withdrawApplyService->getListsWithNames($filter, '*', $page, $pageSize, ['created' => 'DESC']);
            if (empty($result['list'])) {
                break;
            }

            foreach ($result['list'] as $item) {
                $dataList[] = [[
                    "'" . $item['id'],  // 申请ID
                    $this->escapeCSV(date('Y-m-d H:i:s', $item['created'])),  // 申请时间
                    $this->escapeCSV($operatorTypeMap[$item['operator_type']] ?? $item['operator_type']),  // 申请人类型
                    $this->escapeCSV($item['operator']),  // 申请人账号
                    $this->escapeCSV($item['distributor_name']),  // 申请店铺名称
                    $this->escapeCSV($item['distributor_id']),  // 申请店铺ID
                    $this->escapeCSV($item['merchant_name']),  // 申请商户名称
                    $this->escapeCSV($item['merchant_id']),  // 申请商户ID
                    $this->escapeCSV($item['withdraw_type']),  // 提现类型
                    $this->escapeCSV(bcdiv($item['amount'], 100, 2)),  // 提现金额（元）
                    $this->escapeCSV($item['invoice_file'] ?? ''),  // 发票
                    $this->escapeCSV($item['audit_time'] ? date('Y-m-d H:i:s', $item['audit_time']) : ''),  // 审核时间
                    $this->escapeCSV($item['audit_remark']),  // 审核备注
                    $this->escapeCSV($item['auditor']),  // 审核人账号
                    $this->escapeCSV($statusMap[$item['status']] ?? '未知状态'),  // 提现状态
                    $this->escapeCSV($item['failure_reason']),  // 处理结果
                    $this->escapeCSV(date('Y-m-d H:i:s', $item['updated']))  // 最后更新时间
                ]];
            }

            $page++;
        }

        return $dataList;
    }
} 