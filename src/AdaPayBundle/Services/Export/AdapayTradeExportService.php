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

namespace AdaPayBundle\Services\Export;

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;
use AdaPayBundle\Services\AdapayTradeService;

class AdapayTradeExportService implements ExportFileInterface
{
    private $title = [
        'timeStart' => '创建时间',
        'orderId' => '订单号',
        'tradeId' => '交易单号',
        'tradeState' => '交易状态',
//        'adapayDivStatus'               => '是否分账',
//        'distributor_name'               => '店铺名称',
        'payFee' => '订单金额',
        'divType' => '分账类型',
        'canDiv' => '分账状态',
        'adapayDivStatus' => '是否分账',
        'adapayFeeMode' => '手续费扣费方式',
        'adapayFee' => '手续费',
        'divFee' => '分账金额',
        'distributor_name' => '店铺名称',
        'refundedFee' => '退款金额',
    ];

    public function exportData($filter)
    {
        $adapayTradeService = new AdapayTradeService();
        $res = $adapayTradeService->getTradeList($filter);
        $count = $res['total_count'] ?? 0;
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').$filter['company_id']."adapay分账列表";
        $title = $this->title;
        $orderList = $this->getLists($filter, $count);
        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $title, $orderList);
        return $result;
    }

    private function getLists($filter, $count)
    {
        $title = $this->title;
        $tradeState = [
            'PARTIAL_REFUND' => '部分退款',
            'FULL_REFUND' => '全额退款',
            'SUCCESS' => '支付完成',
        ];
        $payChannel = [
            'wx_lite' => '微信小程序(线上)',
        ];
        $adapayDivStatus = [
            'DIVED' => '已分账',
            'NOTDIV' => '未分账',
        ];
        $adapayFeeMode = [
            'I' => '内扣',
            'O' => '外扣',
        ];
        $adapayTradeService = new AdapayTradeService();

        $limit = 500;
        $orderBy = ['time_start' => 'DESC'];
        $fileNum = ceil($count / $limit);
        for ($j = 1; $j <= $fileNum; $j++) {
            $orderList = [];
            $data = $adapayTradeService->getTradeList($filter, $limit, $j);
            foreach ($data['list'] as $key => $value) {
                foreach ($title as $k => $v) {
                    if (in_array($k, ['orderId', 'tradeId']) && isset($value[$k])) {
                        $orderList[$key][$k] = "'".$value[$k]."'";
                    } elseif (in_array($k, ['totalFee', 'payFee','refundedFee', 'divFee','adapayFee']) && isset($value[$k])) {
                        $orderList[$key][$k] = $value[$k] / 100;
                    } elseif (in_array($k, ['timeStart', 'timeExpire']) && isset($value[$k]) && $value[$k]) {
                        $orderList[$key][$k] = date('Y-m-d H:i:s', $value[$k]);
                    } elseif ($k == "tradeState" && isset($value[$k])) {
                        $orderList[$key][$k] = $tradeState[$value[$k]] ?? '--';
                    } elseif ($k == "payType" && isset($value[$k])) {
                        $orderList[$key][$k] = $payType[$value[$k]] ?? '--';
                    } elseif ($k == "payChannel" && isset($value[$k])) {
                        $orderList[$key][$k] = $payChannel[$value[$k]] ?? '--';
                    } elseif ($k == "adapayDivStatus" && isset($value[$k])) {
                        $orderList[$key][$k] = $adapayDivStatus[$value[$k]] ?? '--';
                    } elseif ($k == "adapayFeeMode") {
                        $orderList[$key][$k] = $adapayFeeMode[$value[$k]] ?? '--';
                    } elseif ($k == "divType") {
                        $orderList[$key][$k] = $value['payType'] == 'adapay' ? '线上' : '线下';
                    } elseif ($k == "canDiv") {
                        $orderList[$key][$k] = $value['canDiv'] ? '可分账' : '不可分账';
                    } elseif (isset($value[$k])) {
                        $orderList[$key][$k] = $value[$k];
                    } else {
                        $orderList[$key][$k] = '--';
                    }
                }
            }
            yield $orderList;
        }
    }
}
