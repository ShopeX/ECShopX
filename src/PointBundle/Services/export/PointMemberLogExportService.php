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

namespace PointBundle\Services\export;

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;
use MembersBundle\Services\MemberService;
use PointBundle\Services\PointMemberLogService;
use PointBundle\Services\PointMemberService;

class PointMemberLogExportService implements ExportFileInterface
{
    public function exportData($filter)
    {
        $title = $this->getTitle();
        $fileName = date('YmdHis') . $filter['company_id'] . "member_point_logs";
        // 是否需要数据脱敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        $pointMemberService = new PointMemberLogService();
        $resultList = $pointMemberService->lists($filter, 1, -1, $orderBy = ["created" => "DESC"]);
        if (!$resultList['total_count']) {
            return [];
        }
        $list = $this->getLists($filter, $resultList['total_count'], $datapassBlock);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $title, $list);
        return $result;
    }

    public function getTitle()
    {
        // 0x53686f704578
        $title = [
            'created' => '时间',
            'username' => '微信昵称',
            'name' => '用户名',
            'mobile' => '手机号',
            'in_outcome' => '积分变动',
            'order_id' => '订单号',
            'journal_type_desc' => '变动类型',
            'point_desc' => '记录',
            'point' => '当前剩余积分',
        ];
        return $title;
    }

    public function getLists($filter, $totalCount = 10000, $datapassBlock)
    {
        // 0x53686f704578
        $limit = 2000;
        $fileNum = ceil($totalCount / $limit);
        $memberService = new MemberService();
        $pointMemberService = new PointMemberLogService();
        for ($j = 1; $j <= $fileNum; $j++) {
            $pointLogsList = $pointMemberService->lists($filter, $j, $limit, $orderBy = ["created" => "DESC"]);
            // 用户信息
            $userIds = array_column($pointLogsList['list'],'user_id');
            if ($userIds) {
                $uFilter = [
                    'company_id' => $filter['company_id'],
                    'user_id' => $userIds,
                ];
                $userList = $memberService->getMemberList($uFilter, 1, $limit);
                $userData = array_column($userList, null, 'user_id');
            }

            $list = [];
            foreach ($pointLogsList['list'] as $newData) {
                $point = explode('：', $newData['point_desc']);
                $item = [
                    'created' => date('Y-m-d H:i:s', $newData['created']),
                    'username' => $userData[$newData['user_id']]['username'] ?? '',
                    'name' => $userData[$newData['user_id']]['name'] ?? '',
                    'mobile' => $userData[$newData['user_id']]['mobile'] ?? '',
                    'in_outcome' => $newData['income'] > 0 ? '+'.$newData['income'] : ($newData['outcome'] > 0 ? '-'.$newData['outcome'] : 0),
                    'order_id' => "\"'" . $newData['order_id'] . "\"",
                    'journal_type_desc' => PointMemberService::JOURNAL_TYPE_MAP[$newData['journal_type']] ?? '',
                    'point_desc' => $newData['point_desc'],
                    's_point' => end($point) ?? 0,
                ];
                // 不是脱敏数据
                if (!$datapassBlock) {
                    if (!empty($item['mobile'])) {
                        $item['mobile'] = substr_replace($item['mobile'], '****', 3, 4);
                    }
                }
                $list[] = $item;
            }
            yield $list;
        }
    }

}
