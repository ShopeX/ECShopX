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

namespace OrdersBundle\Services\Export;

use EspierBundle\Services\ExportFileService;
use OrdersBundle\Services\OfflinePaymentService;

class OfflinePaymentExportService
{
    private $title = [
        'order_id' => '订单号',
        'total_fee' => '订单金额',
        // 'pay_sn' => '转账流水号',
        'bank_account_name' => '收款账户名',
        'bank_name' => '收款银行名称',
        'bank_account_no' => '收款银行账号',
        'china_ums_no' => '收款银联号',
        'pay_account_bank' => '付款银行',
        'pay_account_no' => '付款卡号',
        'pay_account_name' => '付款账户名',
        'pay_fee' => '转账金额',
        'voucher_pic' => '凭证图片集合',
        'transfer_remark' => '支付备注',
        'check_status' => '审核',
        'remark' => '审核备注',
        // 'operator_name' => '审核人',
        'create_time' => '凭证创建时间',
        'update_time' => '审核时间',
    ];

    public function exportData($filter)
    {
        $offlinePaymentService = new OfflinePaymentService();
        $count = $offlinePaymentService->repository->count($filter);
        if (!$count) {
            return [];
        }
        $fileName = 'offline_payments_' . date('YmdHis');
        $orderList = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $orderList);
        return $result;
    }

    private function getLists($filter, $totalCount)
    {
        $offlinePaymentService = new OfflinePaymentService();
        $pageSize = 500;
        $orderBy = ['id' => 'DESC'];
        $totalPage = ceil($totalCount / $pageSize);
        for ($page = 1; $page <= $totalPage; $page++) {
            $exportData = [];
            $rs = $offlinePaymentService->repository->getLists($filter, '*', $page, $pageSize, $orderBy);
            foreach ($rs as $v) {
                $lineData = [];
                $v['total_fee'] = bcdiv((string)$v['total_fee'], '100', 2);
                $v['pay_fee'] = bcdiv((string)$v['pay_fee'], '100', 2);
                $v['order_id'] .= "\t";
                // $v['pay_sn'] .= "\t";
                $v['pay_account_no'] .= "\t";
                // 修复 voucher_pic 处理：添加空值检查，避免 json_decode 返回 null 时 implode 报错
                if (!empty($v['voucher_pic'])) {
                    $voucherPicArray = json_decode($v['voucher_pic'], true);
                    $v['voucher_pic'] = is_array($voucherPicArray) ? implode(',', $voucherPicArray) : '';
                } else {
                    $v['voucher_pic'] = '';
                }
                // 给银行账号和银联号添加制表符，避免 Excel 显示为科学计数法
                if (!empty($v['bank_account_no'])) {
                    $v['bank_account_no'] .= "\t";
                }
                if (!empty($v['china_ums_no'])) {
                    $v['china_ums_no'] .= "\t";
                }
                $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                if (in_array($v['check_status'], [1, 2])) {
                    $v['update_time'] = date('Y-m-d H:i:s', $v['update_time']);
                } else {
                    $v['update_time'] = '';
                }
                if ($v['check_status'] == 1) $v['check_status'] = '审核通过';
                elseif ($v['check_status'] == 2) $v['check_status'] = '审核不通过';
                elseif ($v['check_status'] == 9) $v['check_status'] = '已取消';
                else $v['check_status'] = '待审核';
                
                // 构建关联数组，键为字段名，值为字段值（ExportFileService 需要关联数组格式）
                foreach ($this->title as $column => $label) {
                    $lineData[$column] = $v[$column] ?? '';
                }
                $exportData[] = $lineData;
            }
            yield $exportData;
        }
    }
}
