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

namespace PopularizeBundle\Services;

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;

class PopularizeExportService implements ExportFileInterface
{
    private $titleShuyun = [
        'username' => '姓名',
        'mobile' => '手机号',
        'cashWithdrawalRebate' => '可提现',
        'payedRebate' => '已提现',
        'freezeCashWithdrawalRebate' => '申请提现',
        'noCloseRebate' => '未结算',
        'rebateTotal' => '佣金总额',
        'itemTotalPrice' => '商品总额',
        // 'noClosePoint' => '未结算积分',
        // 'pointTotal' => '积分总额',
    ];

    private $title = [
        'username' => '姓名',
        'mobile' => '手机号',
        'cashWithdrawalRebate' => '可提现',
        'payedRebate' => '已提现',
        'freezeCashWithdrawalRebate' => '申请提现',
        'noCloseRebate' => '未结算',
        'rebateTotal' => '佣金总额',
        'itemTotalPrice' => '商品总额',
        'noClosePoint' => '未结算积分',
        'pointTotal' => '积分总额',
    ];

    public function exportData($filter)
    {
        // 是否需要数据脱敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        $promoterService = new PromoterService();
        $data = $promoterService->getPromoterList($filter, 1, 1);
        $count = $data['total_count'];
        if ($count <= 0) {
            return [];
        }
        $isGetSkuList = [];
        $fileName = date('YmdHis')."popularize";
        $dataList = $this->getLists($filter, $count, $datapassBlock);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $dataList);
        return $result;
    }

    public function getLists($filter, $count, $datapassBlock)
    {
        if (config('common.oem-shuyun')) {
            // 数云模式
            $title = $this->titleShuyun;
        } else {
            $title = $this->title;
        }
        $limit = 500;
        $totalPage = ceil($count / $limit);
        $promoterService = new PromoterService();
        for ($i = 1; $i <= $totalPage; $i++) {
            $promoterData = [];
            $result = $promoterService->getPromoterList($filter, $i, $limit);
            if ($result['total_count'] > 0) {
                $promoterCountService = new PromoterCountService();
                foreach ($result['list'] as $k => $row) {
                    $count = $promoterCountService->getPromoterCount($filter['company_id'], $row['user_id']);
                    $result['list'][$k] = array_merge($result['list'][$k], $count);
                }
            }

            $list = $result['list'];
            foreach ($list as $key => $value) {
                $username = $value['username'] ?? '';
                $mobile = $value['mobile'] ?? '';
                if ($datapassBlock) {
                    $username = data_masking('truename', (string) $username);
                    $mobile = data_masking('mobile', (string) $mobile);
                }
                foreach ($title as $k => $val) {
                    if ($k == 'username') {
                        $promoterData[$key][$k] = $username;
                    } else if ($k == 'mobile') {
                        $promoterData[$key][$k] = $mobile;
                    } else if (isset($value[$k])) {
                        switch ($k) {
                            case 'noClosePoint':
                            case 'pointTotal':
                                $promoterData[$key][$k] = $value[$k];
                                break;
                            default:
                                $promoterData[$key][$k] = round(intval($value[$k])/100, 2);
                        }
                    }
                }
            }
            yield $promoterData;
        }
    }
}
