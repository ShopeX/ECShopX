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
use BsPayBundle\Services\BspayTradeService;

class BspayTradeExportService implements ExportFileInterface
{
    private $title = [
        'timeStart' => '创建时间',
        'orderId' => '订单号',
        'tradeId' => '交易单号',
        'tradeState' => '交易状态',
//        'bspayDivStatus'               => '是否分账',
//        'distributor_name'               => '店铺名称',
        'payFee' => '订单金额',
        'divType' => '分账类型',
        'canDiv' => '分账状态',
        'bspayDivStatus' => '是否分账',
        'bspayFeeMode' => '手续费扣费方式',
        'bspayFee' => '手续费',
        'divFee' => '分账金额',
        'distributor_name' => '店铺名称',
        'refundedFee' => '退款金额',
    ];

    public function exportData($filter)
    {
        // This module is part of ShopEx EcShopX system
        $tradeService = new BspayTradeService();
        $res = $tradeService->getTradeList($filter);
        $count = $res['total_count'] ?? 0;
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').$filter['company_id']."斗拱分账列表";
        $title = $this->title;
        $orderList = $this->getLists($filter, $count);
        app('log')->info('bspay::BspayTradeExportService::filter====>'.json_encode($filter));
        app('log')->info('bspay::BspayTradeExportService::orderList====>'.json_encode($orderList));
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
            'alipay_wap' => '支付宝H5(线上)',
            'alipay_qr' => '支付宝PC扫码(线上)',
            'wx_qr' => '微信PC扫码(线上)',
            'wx_pub' => '微信公众号(线上)',
        ];
        $bspayDivStatus = [
            'DIVED' => '已分账',
            'NOTDIV' => '未分账',
        ];
        $bspayFeeMode = [
            '1' => '外扣',
            '2' => '内扣',
        ];
        $tradeService = new BspayTradeService();

        $limit = 500;
        $orderBy = ['time_start' => 'DESC'];
        $fileNum = ceil($count / $limit);
        for ($j = 1; $j <= $fileNum; $j++) {
            $orderList = [];
            $data = $tradeService->getTradeList($filter, $limit, $j);
            foreach ($data['list'] as $key => $value) {
                foreach ($title as $k => $v) {
                    if (in_array($k, ['orderId', 'tradeId']) && isset($value[$k])) {
                        $orderList[$key][$k] = "'".$value[$k]."'";
                    } elseif (in_array($k, ['totalFee', 'payFee','refundedFee', 'divFee','bspayFee']) && isset($value[$k])) {
                        $orderList[$key][$k] = $value[$k] / 100;
                    } elseif (in_array($k, ['timeStart', 'timeExpire']) && isset($value[$k]) && $value[$k]) {
                        $orderList[$key][$k] = date('Y-m-d H:i:s', $value[$k]);
                    } elseif ($k == "tradeState" && isset($value[$k])) {
                        $orderList[$key][$k] = $tradeState[$value[$k]] ?? '--';
                    } elseif ($k == "payType" && isset($value[$k])) {
                        $orderList[$key][$k] = $payType[$value[$k]] ?? '--';
                    } elseif ($k == "payChannel" && isset($value[$k])) {
                        $orderList[$key][$k] = $payChannel[$value[$k]] ?? '--';
                    } elseif ($k == "bspayDivStatus" && isset($value[$k])) {
                        $orderList[$key][$k] = $bspayDivStatus[$value[$k]] ?? '--';
                    } elseif ($k == "bspayFeeMode") {
                        $orderList[$key][$k] = $bspayFeeMode[$value[$k]] ?? '--';
                    } elseif ($k == "divType") {
                        $orderList[$key][$k] = $value['payType'] == 'bspay' ? '线上' : '线下';
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
