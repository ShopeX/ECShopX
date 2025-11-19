<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
        $result = $exportService->exportCsv($fileName, $this->title, $orderList);
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
                                $orderList[$key."-".$i][$k] = "\"'".$value[$k]."\"";
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
                            $orderList[$key][$k] = "\"'".$value[$k]."\"";
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
