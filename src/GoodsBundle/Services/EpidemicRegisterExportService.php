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

namespace GoodsBundle\Services;

use EspierBundle\Services\ExportFileService;
use OrdersBundle\Services\OrderEpidemicService;
use OrdersBundle\Services\OrderItemsService;

class EpidemicRegisterExportService
{
    private $title = [
        'order_id' => '订单号',
        'name' => '姓名',
        'mobile' => '手机号',
        'cert_id' => '身份证号',
        'temperature' => '体温',
        'job' => '职业',
        'symptom' => '症状',
        'symptom_des' => '症状描述',
        'distributor_id' => '店铺ID',
        'distributor_name' => '店铺名称',
        'item_name' => '商品名称',
        'item_bn' => '商品编码',
        'barcode' => '商品条码',
        'num' => '商品数量',
        'is_risk_area' => '14天内是否去过中高风险地区',
        'created' => '登记时间',
    ];

    public function exportData($filter)
    {
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        $orderEpidemicService = new OrderEpidemicService();
        $count = $orderEpidemicService->count($filter);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').$filter['company_id']."疫情防控登记列表";
        $list = $this->getLists($filter, $count, $datapassBlock);
        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $list);

        return $result;
    }

    public function getLists($filter, $count, $datapassBlock)
    {
        $isRiskArea = ['否','是'];
        $noProcessCols = ['name', 'mobile', 'temperature', 'job', 'symptom', 'symptom_des', 'distributor_name', 'distributor_id', 'created', 'item_name', 'item_bn', 'barcode', 'num'];
        $limit = 500;
        $fileNum = ceil($count / $limit);
        $orderEpidemicService = new OrderEpidemicService();
        $orderItemsService = new OrderItemsService();
        $itemsService = new ItemsService();
        for ($j = 1; $j <= $fileNum; $j++) {
            $list = [];
            $data = $orderEpidemicService->epidemicRegisterListService($filter, '*', $j, $limit, ['created' => 'DESC']);
            foreach ($data['list'] as $key => &$value) {
                if ($datapassBlock) {
                    $value['name'] = data_masking('truename', $value['name']);
                    $value['mobile'] = data_masking('mobile', $value['mobile']);
                    $value['cert_id'] = data_masking('idcard', $value['cert_id']);
                }
                $orderItems = $orderItemsService->getList(
                    [
                        'company_id' => $value['company_id'],
                        'user_id' => $value['user_id'],
                        'order_id' => $value['order_id'],
                    ]
                );
                $num = 0;
                foreach ($orderItems['list'] as $orderItem) {
                    $num += $orderItem['num'];
                }
                $value['num'] = $num;

                $itemIds = array_column($orderItems['list'], 'item_id');
                $items = $itemsService->getLists(
                    [
                        'company_id' => $value['company_id'],
                        'item_id' => $itemIds,
                    ]
                );
                $itemName = '';
                $itemBn = '';
                $barcode = '';
                foreach ($items as $item) {
                    $itemName .= $item['item_name'] . "\n";
                    $itemBn .= $item['item_bn'] . "\n";
                    $barcode .= $item['barcode'] ? $item['barcode']."\n" : "--\n";
                }
                $value['item_name'] = $itemName;
                $value['item_bn'] = $itemBn;
                $value['barcode'] = $barcode;

                foreach ($this->title as $k => $v) {
                    if (in_array($k, $noProcessCols) && isset($value[$k])) {
                        $list[$key][$k] = $value[$k];
                    } elseif (in_array($k, ['order_id', 'cert_id']) && isset($value[$k])) {
                        $list[$key][$k] = "\t".$value[$k]."\t";
                    } elseif ($k == 'is_risk_area' && isset($value[$k])) {
                        $list[$key][$k] = $isRiskArea[$value[$k]];
                    } else {
                        $list[$key][$k] = '--';
                    }
                }
            }
            yield $list;
        }
    }
}
