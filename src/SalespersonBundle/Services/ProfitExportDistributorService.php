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

namespace SalespersonBundle\Services;

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;

class ProfitExportDistributorService implements ExportFileInterface
{
    public function exportData($filter)
    {
        $profitService = new ProfitService();
        $count = $profitService->profitStatisticsRepository->count($filter);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis') . $filter['company_id'] . "distributor_profit";
        $title = $this->getTitle();
        $list = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $title, $list);
        return $result;
    }

    private function getTitle()
    {
        $title = [
            'distributor_id' => '门店ID',
            'name' => '名店名称',
            'commissions_num' => '门店拉新分润订单数',
            'commissions' => '门店拉新分润金额',
            'order_num' => '门店交易笔数',
            'goods_amount' => '门店货款金额',
            'seller_withdrawals_fee' => '门店代导购获取分成',
            'total' => '总计金额',
        ];
        return $title;
    }

    private function getLists($filter, $count)
    {
        $title = $this->getTitle();
        $profitService = new ProfitService();
        $count = $profitService->profitStatisticsRepository->count($filter);
        $limit = 500;
        $orderBy = ['id' => 'DESC'];
        $fileNum = ceil($count / $limit);

        for ($j = 1; $j <= $fileNum; $j++) {
            $profitList = [];
            $list = $profitService->profitStatisticsRepository->getLists($filter, '*', $j, $limit, $orderBy);

            foreach ($list as $key => $value) {
                if (isset($value['params'])) {
                    $value['params'] = json_decode($value['params'], true);
                }
                foreach ($title as $k => $v) {
                    if ($k == 'distributor_id' && isset($value['profit_user_id'])) {
                        $profitList[$key][$k] = $value['profit_user_id'];
                    } elseif ($k == 'name' && isset($value[$k])) {
                        $profitList[$key][$k] = $value[$k];
                    } elseif ($k == 'commissions_num' && isset($value['params'])) {
                        $profitList[$key][$k] = $value['params']['commissions_num'] ?? 0;
                    } elseif ($k == 'commissions' && isset($value['params'])) {
                        $profitList[$key][$k] = isset($value['params']['commissions']) ? bcdiv($value['params']['commissions'], 100, 2) : 0;
                    } elseif ($k == 'order_num' && isset($value['params'])) {
                        $profitList[$key][$k] = $value['params']['order_num'] ?? 0;
                    } elseif ($k == 'goods_amount' && isset($value['params'])) {
                        $profitList[$key][$k] = isset($value['params']['goods_amount']) ? bcdiv($value['params']['goods_amount'], 100, 2) : 0;
                    } elseif ($k == 'seller_withdrawals_fee' && isset($value['params'])) {
                        $profitList[$key][$k] = isset($value['params']['seller_withdrawals_fee']) ? bcdiv($value['params']['seller_withdrawals_fee'], 100, 2) : 0;
                    } elseif ($k == 'total' && isset($value['withdrawals_fee'])) {
                        $profitList[$key][$k] = $value['withdrawals_fee'];
                    } else {
                        $profitList[$key][$k] = '--';
                    }
                }
            }
            yield $profitList;
        }
    }
}
