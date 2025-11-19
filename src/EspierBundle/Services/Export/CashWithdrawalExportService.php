<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services\Export;

use CommunityBundle\Services\CashWithdrawalService;
use EspierBundle\Services\ExportFileService;
use EspierBundle\Interfaces\ExportFileInterface;

class CashWithdrawalExportService implements ExportFileInterface
{
    private $title = [
        'created' => '申请时间',
        'bank_name' => '打款方式',
        'account_name' => '户名',
        'bank_account' => '银行卡号',
        'bank_address' => '开户行信息',
        'account_mobile' => '手机号',
        'money' => '提现金额',
        'point' => '提现积分',
        'status' => '提现状态',
    ];

    public function exportData($filter)
    {
        // TODO: Implement exportData() method.
        $cashWithdrawalService = new CashWithdrawalService();
        $count = $cashWithdrawalService->count($filter);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').'community_withdraw';
        $orderList = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $orderList);
        return $result;
    }

    private function getLists($filter, $count)
    {
        $field_title = $this->title;

        if ($count > 0) {
            $cashWithdrawalService = new CashWithdrawalService();

            $limit = 500;
            $fileNum = ceil($count / $limit);

            for ($page = 1; $page <= $fileNum; $page++) {
                $cashData = [];
                $data = $cashWithdrawalService->lists($filter, $page, $limit, ["created" => "DESC"]);

                foreach ($data['list'] as $key => $value) {
                    foreach ($field_title as $k => $v) {
                        if ($k == 'created') {
                            $cashData[$key][$k] = date('Y-m-d H:i:s', $value[$k]);
                        } elseif ($k == 'status') {
                            switch ($value[$k]) {
                                case 'apply':
                                    $title = '待处理';
                                    break;
                                case 'process':
                                    $title = '处理异常';
                                    break;
                                case 'success':
                                    $title = '提现完成';
                                    break;
                                case 'reject':
                                    $title = '以拒绝';
                                    break;
                                default:
                                    $title = '未知状态';
                            }
                            $cashData[$key][$k] = $title;
                        } elseif ($k == 'money') {
                            $cashData[$key][$k] = '¥'.$value[$k] / 100;
                        } else {
                            $cashData[$key][$k] = $value[$k];
                        }
                    }
                }
                yield $cashData;
            }
        }
    }
}
