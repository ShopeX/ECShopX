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
use OrdersBundle\Services\RightsService;
use OrdersBundle\Services\Rights\TimesCardService;
use MembersBundle\Services\UserService;
use EspierBundle\Services\ExportFileService;

class RightExportService implements ExportFileInterface
{
    private $title = [
            'mobile' => '手机号',
            'rights_name' => '权益名称',
            'total_num' => ' 权益总数',
            'total_consum_num' => '消耗次数',
            'total_surplus_num' => '剩余次数',
            'start_time' => '权益开始时间',
            'end_time' => '权益到期时间',
            'order_id' => '订单号',
            'rights_from' => '权益来源',
            'operator_desc' => '操作员手机及姓名',
            'user_nickname' => '会员昵称/姓名',
            'sex' => '会员性别',
        ];
    public function exportData($filter)
    {
        // 是否需要数据脱敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        $rightsObj = new RightsService(new TimesCardService());
        $count = $rightsObj->countRights($filter);
        if (!$count) {
            return response()->json(['filename' => '','url' => '']);
        }
        $fileName = date('YmdHis').$filter['company_id'].'right';
        $orderList = $this->getLists($filter, $count, $datapassBlock);

        $exportService = new ExportFileService();
        // 指定需要作为文本处理的数字字段，避免 Excel 显示为科学计数法
        $textFields = ['order_id'];
        $result = $exportService->exportCsv($fileName, $this->title, $orderList, $textFields);
        return $result;
    }

    private function getLists($filter, $count, $datapassBlock)
    {
        $rightsObj = new RightsService(new TimesCardService());
        $userService = new UserService();

        $limit = 500;
        $orderBy = ['mobile' => 'DESC', 'created' => 'desc'];
        $title = $this->title;
        $fileNum = ceil($count / $limit);
        for ($j = 1; $j <= $fileNum; $j++) {
            $orderList = [];
            $rights = $rightsObj->getRightsList($filter, $j, $limit, $orderBy);
            foreach ($rights['list'] as $key => $value) {
                $user_nickname = isset($userInfo['username']) ? $userInfo['username'] : '未知';
                if ($datapassBlock) {
                    $value['mobile'] = data_masking('mobile', (string) $value['mobile']);
                    $operatoreArr = explode(' : ', $value['operator_desc']);
                    if ($operatoreArr[0] && $operatoreArr[1]) {
                        $operator_mobile = data_masking('mobile', (string) $operatoreArr[0]);
                        $operator_name = data_masking('truename', (string) $operatoreArr[1]);
                        $value['operator_desc'] = $operator_mobile . ' : ' . $operator_name;
                    }
                    $user_nickname != '未知' and $user_nickname = data_masking('truename', (string) $user_nickname);
                }
                foreach ($title as $k => $v) {
                    if ($k == "order_id" && isset($value[$k])) {
                        // 直接赋值，不再添加引号，由 ExportFileService 统一处理
                        $orderList[$key][$k] = $value[$k];
                    } elseif ($k == "start_time" && isset($value[$k])) {
                        $orderList[$key][$k] = date('Y-m-d H:i:s', $value[$k]);
                    } elseif ($k == "end_time" && isset($value[$k])) {
                        $orderList[$key][$k] = date('Y-m-d H:i:s', $value[$k]);
                    } elseif (isset($value[$k])) {
                        $orderList[$key][$k] = $value[$k];
                    } else {
                        $orderList[$key][$k] = 'null';
                    }
                }
                $userInfo = $userService->getUserById($value['user_id'], $value['company_id']);
                $orderList[$key]['user_nickname'] = $user_nickname;
                $orderList[$key]['sex'] = isset($userInfo['sex']) ? ($userInfo['sex'] == 1 ? "男" : "女") : '未知';
            }
            yield $orderList;
        }
    }
}
