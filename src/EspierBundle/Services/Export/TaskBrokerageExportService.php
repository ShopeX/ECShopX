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
use OrdersBundle\Entities\NormalOrders;
use PopularizeBundle\Services\TaskBrokerageService;
use EspierBundle\Services\ExportFileService;

class TaskBrokerageExportService implements ExportFileInterface
{
    public function exportData($filter)
    {
        // 是否需要数据脱敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        $taskBrokerageService = new TaskBrokerageService();
        $count = $taskBrokerageService->getTaskBrokerageCountList($filter, '*', 1, 1);
        if (!$count['total_count']) {
            return [];
        }
        $fileName = date('YmdHis')."任务佣金统计";
        $list = $this->getLists($filter, $count['total_count'], $datapassBlock);

        $exportService = new ExportFileService();
        // 指定需要作为文本处理的数字字段，避免 Excel 显示为科学计数法
        $textFields = ['item_bn'];
        $result = $exportService->exportCsv($fileName, $this->title, $list, $textFields);
        return $result;
    }

    private $title = [
        'promoter_mobile' => '推广员手机号',
        'rebate_type' => '任务类型',
        'plan_date' => '账期',
        'item_bn' => '商品编码',
        'item_name' => '商品名称',
        'item_spec_desc' => '商品规格',
        'total_fee' => '已完成总销售额',
        'finish_num' => '已完成销售数量',
        'wait_num' => '待确认数量',
        'close_num' => '已关闭数量',
        'rebate_money' => '分销奖金',
        'status' => '是否达标',
        'wait' => '待统计的订单',
        'finish' => '已完成的订单',
        'close' => '已关闭的订单',
    ];

    public function taskBrokerageList($list)
    {
        $userIds = array_column($list['list'], 'user_id');
        $itemIds = array_column($list['list'], 'item_id');
        $userIds = array_unique($userIds);
        $itemIds = array_unique($itemIds);

        $params = [
            'company_id' => $list['list'][0]['company_id'],
            'item_id' => $itemIds,
            'user_id' => $userIds,
        ];
        if (!$params['item_id'] || !$params['user_id']) {
            $taskBrokerageList = [
                'total_count' => 0,
                'list' => [],
            ];
        } else {
            $taskBrokerageService = new TaskBrokerageService();
            $taskBrokerageList = $taskBrokerageService->taskBrokerageRepository->lists($params);
        }

        if ($taskBrokerageList['list']) {
            $orderStatus = [
                'NOTPAY' => '未支付',
                'CANCEL' => '已取消',
                'CANCEL_WAIT_PROCESS' => '取消待处理',
                'DONE' => '已完成',
                'PAYED' => '已支付',
                'REFUND_SUCCESS' => '已退款',
                'WAIT_BUYER_CONFIRM' => '待收货',
                'REVIEW_PASS' => '审核通过待出库',
                'WAIT_GROUPS_SUCCESS' => '等待成团',
                'REFUND_PROCESS' => '退款处理中',
            ];
            $statusMsg = [
                'wait' => '待统计',
                'finish' => '已统计',
                'close' => '已关闭',
            ];
            $orderIds = array_column($taskBrokerageList['list'], 'order_id');
            $normalOrderRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
            $orderList = $normalOrderRepository->getList(['order_id' => $orderIds]);
            foreach ($taskBrokerageList['list'] as &$task) {
                foreach ($orderList as $order) {
                    if ($order['order_id'] == $task['order_id']) {
                        $task['mobile'] = $order['mobile'];
                        $task['order_status'] = $order['order_status'];
                        $task['order_status_msg'] = $orderStatus[$order['order_status']] ?? '';
                        $task['total_fee'] = bcdiv($order['total_fee'], 100, 2);
                    }
                }
            }
        }

        return $taskBrokerageList;
    }

    private function getLists($filter, $count, $datapassBlock)
    {
        $title = $this->title;
        $companyId = $filter['company_id'];
        $taskBrokerageService = new TaskBrokerageService();
        $limit = 500;
        $fileNum = ceil($count / $limit);
        for ($page = 1; $page <= $fileNum; $page++) {
            $data = [];
            $list = $taskBrokerageService->getTaskBrokerageCountList($filter, '*', $page, $limit);
            $taskBrokerageList = $this->taskBrokerageList($list);
            foreach ($list['list'] as $key => $value) {
                if ($datapassBlock) {
                    $value['promoter_mobile'] = data_masking('mobile', (string) $value['promoter_mobile']);
                }
                $value['wait'] = [];
                $value['finish'] = [];
                $value['close'] = [];
                foreach ($taskBrokerageList['list'] as $v) {
                    if ($value['company_id'] == $v['company_id'] && $value['item_id'] == $v['item_id'] && $value['user_id'] == $v['user_id']) {
                        $orderId = "\t".$v['order_id']."\t";
                        switch ($v['status']) {
                            case 'wait':
                                array_push($value['wait'], $orderId);
                                break;
                            case 'finish':
                                array_push($value['finish'], $orderId);
                                break;
                            case 'close':
                                array_push($value['close'], $orderId);
                                break;
                        }
                    }
                }

                foreach ($title as $k => $v) {
                    if ($k == 'status') {
                        $data[$key][$k] = $value[$k] ? '已达标' : $value['limit_desc'];
                    } elseif ($k == 'rebate_money' || $k == 'total_fee') {
                        $data[$key][$k] = '¥'.$value[$k] / 100;
                    } elseif ($k == 'rebate_type') {
                        $data[$key][$k] = $value[$k] == 'total_num' ? '按总数量' : '按总金额';
                    } elseif ($k == 'wait' || $k == 'finish' || $k == 'close') {
                        $data[$key][$k] = implode("\n", $value[$k]);
                    } elseif ($k == 'item_bn') {
                        // 直接赋值，不再判断是否为数字，不再添加引号，由 ExportFileService 统一处理
                        $data[$key][$k] = $value[$k] ?? '';
                    } else {
                        $data[$key][$k] = $value[$k];
                    }
                }
            }
            yield $data;
        }
    }
}
