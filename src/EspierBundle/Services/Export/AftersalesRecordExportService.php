<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services\Export;

use AftersalesBundle\Services\AftersalesService;
use EspierBundle\Services\ExportFileService;
use EspierBundle\Interfaces\ExportFileInterface;
use OrdersBundle\Services\TradeService;

class AftersalesRecordExportService implements ExportFileInterface
{
    private $title = [
        'distributor_name' => '店铺名称',
        'shop_code' => '店铺号',
        'aftersales_bn' => '售后单号',
        'order_id' => '订单号',
        'trade_no' => '订单序号',
        'item_bn' => '商品编号',
        'item_name' => '商品名称',
        'num' => '数量',
        'aftersales_type' => '售后类型',
        'aftersales_status' => '售后状态',
        'create_time' => '创建时间',
        'refund_fee' => '退款金额',
        'progress' => '处理进度',
        'description' => '申请描述',
        'reason' => '申请售后原因',
        'refuse_reason' => '拒绝原因',
        'memo' => '售后备注'
    ];

    public function exportData($filter)
    {
        $aftersalesService = new AftersalesService();
        $count = $aftersalesService->count($filter);

        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').'_售后列表';
        $datalist = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $datalist);
        return $result;
    }

    private function getLists($filter, $count)
    {
        $title = $this->title;

        $aftersales_type = [
            'ONLY_REFUND' => '仅退款',
            'REFUND_GOODS' => '退货退款',
            'EXCHANGING_GOODS' => '换货',
        ];

        $aftersales_status = [
            0 => '待处理',
            1 => '处理中',
            2 => '已处理',
            3 => '已驳回',
            4 => '已关闭',
        ];

        $progress = [
            0 => '等待商家处理',
            1 => '商家接受申请，等待消费者回寄',
            2 => '消费者回寄，等待商家收货确认',
            3 => '已驳回',
            4 => '已处理',
            5 => '退款驳回',
            6 => '退款完成',
            7 => '售后关闭',
            8 => '商家确认收货,等待审核退款',
            9 => '退款处理中',
        ];

        if ($count > 0) {
            $aftersalesService = new AftersalesService();
            $tradeService = new TradeService();

            $limit = 500;
            $fileNum = ceil($count / $limit);

            for ($page = 1; $page <= $fileNum; $page++) {
                $recordData = [];
                $data = $aftersalesService->exportAftersalesList($filter, $page, $limit, ["create_time" => "DESC"]);

                $orderIdList = array_column($data['list'], 'order_id');
                $tradeIndex = $tradeService->getTradeIndexByOrderIdList($filter['company_id'], $orderIdList);

                foreach ($data['list'] as $key => $value) {
                    $value['trade_no'] = $tradeIndex[$value['order_id']] ?? '-';
                    foreach ($title as $k => $v) {
                        if ($k == 'create_time') {
                            $recordData[$key][$k] = date('Y-m-d H:i:s', $value[$k]);
                        } elseif (in_array($k, ['order_id', 'aftersales_bn']) && isset($value[$k])) {
                            $recordData[$key][$k] = "\"'".$value[$k]."\"";
                        } elseif ($k == 'refund_fee') {
                            $recordData[$key][$k] = $value[$k] / 100;
                        } elseif ($k == "aftersales_type") {
                            $recordData[$key][$k] = $aftersales_type[$value[$k]] ?? '--';
                        } elseif ($k == "aftersales_status") {
                            $recordData[$key][$k] = $aftersales_status[$value[$k]] ?? '--';
                        } elseif ($k == "progress") {
                            $recordData[$key][$k] = $progress[$value[$k]] ?? '--';
                        } elseif ($k == 'item_bn' && is_numeric($value[$k])) {
                            $recordData[$key][$k] = "\"'".$value[$k]."\"";
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
