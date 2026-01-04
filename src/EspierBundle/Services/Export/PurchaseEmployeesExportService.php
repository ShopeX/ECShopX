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

use EspierBundle\Services\ExportFileService;
use EspierBundle\Interfaces\ExportFileInterface;
use EmployeePurchaseBundle\Services\EmployeesService;

class PurchaseEmployeesExportService implements ExportFileInterface
{
    private $title = [
        'mobile'  => '手机号',
        'name' => '姓名',
        'auth_type' => '登录类型',
        'account' => '账户',
        // 'auth_code' => '密码',
        'email' => '邮箱',
        'distributor_name' => '来源店铺',
        'enterprise_id' => '企业ID',
        'enterprise_name' => '企业名称',
        'enterprise_sn' => '企业编码',
        'member_mobile' => '会员手机号',
    ];

    public function exportData($filter)
    {
        // 是否需要数据脱敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        $employeesService = new EmployeesService();
        $count = $employeesService->count($filter);

        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').'_企业员工列表';
        $datalist = $this->getLists($filter, $count, $datapassBlock);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $datalist);
        return $result;
    }

    private function getLists($filter, $count, $datapassBlock)
    {
        $title = $this->title;

        $auth_type = [
            'mobile' => '手机号',
            'account' => '账号',
            'email' => '邮箱',
            'qr_code' => '二维码',
        ];

        if ($count > 0) {
            $employeesService = new EmployeesService();

            $orderBy = ['created' => 'DESC'];
            $limit = 500;
            $fileNum = ceil($count / $limit);

            for ($j = 1; $j <= $fileNum; $j++) {
                $employeeData = [];
                $data = $employeesService->getEmployeeListWithRel($filter, $j, $limit, $orderBy);
                foreach ($data['list'] as $key => $value) {
                    if ($datapassBlock) {
                        $value['mobile'] = data_masking('mobile', (string) $value['mobile']);
                        $value['name'] = data_masking('truename', (string) $value['name']);
                        $value['member_mobile'] = data_masking('mobile', (string) $value['member_mobile']);
                    }
                    foreach ($title as $k => $v) {
                        if ($k == "auth_type") {
                            $employeeData[$key][$k] = $auth_type[$value[$k]] ?? '--';
                        } else {
                            $employeeData[$key][$k] = $value[$k] ?? '';
                        }
                    }
                }
                yield $employeeData;
            }
        }
    }
}
