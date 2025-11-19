<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SalespersonBundle\Services;

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;

class ProfitExportSalespersonService implements ExportFileInterface
{
    public function exportData($filter)
    {
        $profitService = new ProfitService();
        $count = $profitService->profitStatisticsRepository->count($filter);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis') . $filter['company_id'] . "salesperson_profit";
        $title = $this->getTitle();
        $list = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $title, $list);
        return $result;
    }

    private function getTitle()
    {
        $title = [
            'salesperson_id' => '导购ID',
            'name' => '导购名称',
            'distributor_name' => '门店名称',
            'popularize_commissions_num' => '导购推广笔数',
            'popularize_commissions' => '导购推广金额',
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
                    if ($k == 'salesperson_id' && isset($value['profit_user_id'])) {
                        $profitList[$key][$k] = $value['profit_user_id'];
                    } elseif ($k == 'name' && isset($value[$k])) {
                        $profitList[$key][$k] = $value[$k];
                    } elseif ($k == 'distributor_name' && isset($value['params'])) {
                        $profitList[$key][$k] = $value['params']['distributor_name'] ?? 0;
                    } elseif ($k == 'popularize_commissions_num' && isset($value['params'])) {
                        $profitList[$key][$k] = $value['params']['popularize_commissions_num'] ?? 0;
                    } elseif ($k == 'popularize_commissions' && isset($value['params'])) {
                        $profitList[$key][$k] = isset($value['params']['popularize_commissions']) ? bcdiv($value['params']['popularize_commissions'], 100, 2) : 0;
                    } elseif ($k == 'total' && isset($value['withdrawals_fee'])) {
                        $profitList[$key][$k] = bcdiv($value['withdrawals_fee'], 100, 2);
                    } else {
                        $profitList[$key][$k] = '--';
                    }
                }
            }
            yield $profitList;
        }
    }
}
