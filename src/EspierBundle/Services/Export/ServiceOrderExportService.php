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
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Services\RightsService;
use OrdersBundle\Services\Rights\TimesCardService;
use EspierBundle\Services\ExportFileService;

class ServiceOrderExportService implements ExportFileInterface
{
    use GetOrderServiceTrait;

    private $title = [
            'order_id' => '订单号',
            'title' => '订单标题',
            'store_name' => '所属门店',
            'create_date' => '下单时间',
            'mobile' => '手机号',
            'total_fee' => '订单价格',
            'source_name' => '来源名称',
            'order_source' => '订单来源',
            'operator_desc' => '操作员手机及姓名',
            'rights_title' => '购买课程',
            'num' => '课程数',
        ];

    public function exportData($filter)
    {
        // 是否需要数据脱敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        $orderService = $this->getOrderService('service');
        $count = $orderService->countOrderNum($filter);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').$filter['company_id'];
        $orderList = $this->getLists($filter, $count, $datapassBlock);

        $exportService = new ExportFileService();
        // 指定需要作为文本处理的数字字段，避免 Excel 显示为科学计数法
        $textFields = ['order_id'];
        $result = $exportService->exportCsv($fileName, $this->title, $orderList, $textFields);
        return $result;
    }

    private function getLists($filter, $count, $datapassBlock)
    {
        $orderService = $this->getOrderService('service');
        $limit = 500;
        $title = $this->title;
        $fileNum = ceil($count / $limit);
        $rightsObj = new RightsService(new TimesCardService());
        for ($j = 1; $j <= $fileNum; $j++) {
            $orderList = [];
            $orderdata = $orderService->getOrderList($filter, $j, $limit);
            foreach ($orderdata['list'] as $key => $value) {
                if ($datapassBlock) {
                    $value['mobile'] = data_masking('mobile', (string) $value['mobile']);
                }
                //获取订单权益
                $rightsFilter = [
                    'company_id' => $value['company_id'],
                    'order_id' => $value['order_id']
                ];
                $rights = $rightsObj->getRightsList($rightsFilter);
                if ($rights['list']) {
                    foreach ($rights['list'] as $i => $rightlist) {
                        foreach ($title as $k => $v) {
                            if ($k == "order_id" && isset($value[$k])) {
                                // 直接赋值，不再添加引号，由 ExportFileService 统一处理
                                $orderList[$key."-".$i][$k] = $value[$k];
                            } elseif ($k == "total_fee" && isset($value[$k])) {
                                $orderList[$key."-".$i][$k] = $value[$k] / 100;
                            } elseif ($k == "order_source" && isset($value[$k])) {
                                $orderList[$key."-".$i][$k] = ($value[$k] == 'shop') ? '代客下单' : '会员自主下单';
                            } elseif (isset($value[$k])) {
                                $orderList[$key."-".$i][$k] = $value[$k];
                            } else {
                                $orderList[$key."-".$i][$k] = '';
                            }
                        }
                        $orderList[$key."-".$i]['rights_title'] = $rightlist['rights_subname'];
                        $orderList[$key."-".$i]['num'] = $rightlist['total_num'];
                    }
                } else {
                    foreach ($title as $k => $v) {
                        if ($k == "order_id" && isset($value[$k])) {
                            // 直接赋值，不再添加引号，由 ExportFileService 统一处理
                            $orderList[$key][$k] = $value[$k];
                        } elseif ($k == "total_fee" && isset($value[$k])) {
                            $orderList[$key][$k] = $value[$k] / 100;
                        } elseif ($k == "order_source" && isset($value[$k])) {
                            $orderList[$key][$k] = ($value[$k] == 'shop') ? '代客下单' : '会员自主下单';
                        } elseif (isset($value[$k])) {
                            $orderList[$key][$k] = $value[$k];
                        } else {
                            $orderList[$key][$k] = '';
                        }
                    }
                    $orderList[$key]['rights_title'] = '未知';
                    $orderList[$key]['num'] = '0';
                }
            }
            yield $orderList;
        }
    }
}
