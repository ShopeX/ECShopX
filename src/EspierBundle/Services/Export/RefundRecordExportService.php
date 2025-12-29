<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services\Export;

use OrdersBundle\Entities\Trade;
use EspierBundle\Services\ExportFileService;
use EspierBundle\Interfaces\ExportFileInterface;
use AftersalesBundle\Services\AftersalesRefundService;
use OrdersBundle\Services\TradeService;

class RefundRecordExportService implements ExportFileInterface
{
    private $title = [
        'distributor_name'  => '店铺名称',
        'shop_code' => '店铺号',
        'refund_bn' => '退款单号',
        'aftersales_bn' => '售后单号',
        'order_id' => '订单号',
        'trade_no' => '订单序号',
        'refund_type' => '退款类型',
        'refund_channel' => '退款方式',
        'refund_status' => '退款状态',
        'refund_fee' => '应退金额',
        'refunded_fee' => '实退金额',
        'refund_point' => '退款积分',
        'create_time' => '创建时间',
        'refund_success_time' => '退款成功时间',
    ];

    public function exportData($filter)
    {
        $aftersalesRefundService = new AftersalesRefundService();
        $count = $aftersalesRefundService->refundCount($filter);

        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').'_退款列表';
        $datalist = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        // 指定需要作为文本处理的数字字段，避免 Excel 显示为科学计数法
        $textFields = ['refund_bn', 'aftersales_bn', 'order_id'];
        $result = $exportService->exportCsv($fileName, $this->title, $datalist, $textFields);
        return $result;
    }

    private function getLists($filter, $count)
    {
        $title = $this->title;

        $refund_type = [
            0 => '售后',
            1 => '售前',
            2 => '拒单',
        ];

        $refund_channel = [
            'offline' => '线下退回',
            'original' => '原路退回',
        ];

        $refund_status = [
            'AUDIT_SUCCESS' => '审核成功待退款',
            'SUCCESS' => '退款成功',
            'REFUSE' => '退款驳回',
            'CANCEL' => '撤销退款',
            'REFUNDCLOSE' => '退款关闭',
            'PROCESSING' => '已发起退款等待到账',
            'CHANGE' => '退款异常',
        ];

        if ($count > 0) {
            $aftersalesRefundService = new AftersalesRefundService();
            $tradeService = new TradeService();

            //获取交易单信息
            $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);

            $orderBy = ['create_time' => 'DESC'];
            $limit = 500;
            $fileNum = ceil($count / $limit);

            for ($j = 1; $j <= $fileNum; $j++) {
                $recordData = [];
                $data = $aftersalesRefundService->getAftersalesRefundList($filter, $orderBy, $limit, $j);

                $orderIdList = array_column($data['list'], 'order_id');
                $tradeIndex = $tradeService->getTradeIndexByOrderIdList($filter['company_id'], $orderIdList);

                foreach ($data['list'] as $key => $value) {
                    $tradeInfo = $tradeRepository->getTradeList([
                        'company_id' => $filter['company_id'],
                        'order_id' => $value['order_id']
                    ]);
                    $value['trade_no'] = $tradeIndex[$value['order_id']] ?? '-';
                    foreach ($title as $k => $v) {
                        if (in_array($k, ['create_time', 'refund_success_time'])) {
                            $recordData[$key][$k] = date('Y-m-d H:i:s', $value[$k]);
                        } elseif (in_array($k, ['refund_bn', 'aftersales_bn', 'order_id']) && isset($value[$k])) {
                            // 直接赋值，不再添加引号，由 ExportFileService 统一处理
                            $recordData[$key][$k] = $value[$k];
                        } elseif (in_array($k, ['refund_fee', 'refunded_fee'])) {
                            $recordData[$key][$k] = $value[$k] / 100;
                        } elseif ($k == "refund_type") {
                            $recordData[$key][$k] = $refund_type[$value[$k]] ?? '--';
                        } elseif ($k == "refund_channel") {
                            $recordData[$key][$k] = $refund_channel[$value[$k]] ?? '--';
                        } elseif ($k == "refund_status") {
                            $recordData[$key][$k] = $refund_status[$value[$k]] ?? '--';
                        } elseif ($k == "pay_time") {
                            $recordData[$key][$k] = $tradeInfo['list'][0]['payDate'] ?? '--';
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
