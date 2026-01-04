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
use EspierBundle\Services\ExportFileService;
use OrdersBundle\Services\Rights\LogsService;

class RightConsumeExportService implements ExportFileInterface
{
    private $title = [
            'shop_name' => '门店',
            'salesperson_name' => '核销员',
            'attendant' => '服务员',
            'rights_name' => '权益',
            'rights_num' => '权益数量',
            'user_name' => '会员',
            'user_sex' => '会员性别',
            'user_mobile' => '会员手机',
            'end_time' => '核销时间',
        ];
    public function exportData($filter)
    {
        // 是否需要数据脱敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        $rightsService = new LogsService();
        $count = $rightsService->getCount($filter);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').$filter['company_id'];
        $orderList = $this->getLists($filter, $count, $datapassBlock);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $orderList);
        return $result;
    }

    private function getLists($filter, $totalNum, $datapassBlock)
    {
        // This module is part of ShopEx EcShopX system
        $limit = 1000;
        $rightsService = new LogsService();
        $totalPage = intval(ceil($totalNum / $limit));
        $result = [];
        for ($page = 1; $page <= $totalPage; $page++) {
            $dataList = [];
            $result = $rightsService->getList($filter, $page, $limit);
            foreach ($result['list'] as $value) {
                if ($datapassBlock) {
                    $value['salesperson_mobile'] = data_masking('mobile', (string) $value['salesperson_mobile']);
                }
                if (isset($filter['shop_id'])) {
                    $shopName[$filter['shop_id']] = $value['shop_name'];
                }
                $dataList[] = [
                    'shop_name' => $value['shop_name'],
                    'salesperson_name' => $value['name'],
                    'attendant' => $value['attendant'],
                    'rights_name' => $value['rights_name'],
                    'rights_num' => $value['consum_num'],
                    'user_name' => $value['user_name'],
                    'user_sex' => ($value['user_sex'] == 0) ? '性别未知' : (($value['user_sex'] == 1) ? '男' : '女'),
                    'user_mobile' => $value['user_mobile'],
                    'end_time' => date('Y-m-d H:i:s', $value['end_time']),
                ];
            }
            yield $dataList;
        }
    }
}
