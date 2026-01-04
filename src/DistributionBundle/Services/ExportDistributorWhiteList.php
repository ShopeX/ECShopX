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

namespace DistributionBundle\Services;


use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;

class ExportDistributorWhiteList implements ExportFileInterface
{
    private $title = [
        'mobile' => '手机号',
        'username' => '姓名',
        'distributor' => '店铺',
//        'tradeState' => '交易状态',
    ];

    public function exportData($filter)
    {
        $tradeService = new DistributorWhiteListService();
        $res = $tradeService->getWhiteList($filter);
        $count = $res['total_count'] ?? 0;
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis') . $filter['company_id'] . "店铺白名单导出";
        $title = $this->title;
        $orderList = $this->getLists($filter, $count);
        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $title, $orderList);
        return $result;
    }

    private function getLists($filter, $count)
    {
        $title = $this->title;

        $service = new DistributorWhiteListService();

        $limit = 100;

        $fileNum = ceil($count / $limit);
        for ($j = 1; $j <= $fileNum; $j++) {
            $whiteData = [];
            $data = $service->getWhiteList($filter, $j, $limit);
            foreach ($data['list'] as $key => $value) {
                $tmp= [];
                $tmp['mobile'] = $value['mobile'];
                $tmp['username'] = $value['username'];
                $distributorName = array_column($value['distributor_info'],'name');
                $tmp['distributor'] = implode(',', $distributorName);
                $whiteData[] = $tmp;
            }
            yield $whiteData;
        }
    }
}

