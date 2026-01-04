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

namespace SalespersonBundle\Services\Export;

use SalespersonBundle\Services\SalespersonService;
use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;
use MembersBundle\Services\MemberService;
use WorkWechatBundle\Services\WorkWechatRelService;

class SalespersonBindExportService implements ExportFileInterface
{
    private $title = [
        'salesperson_id' => '导购ID',
        'salesperson_mobile' => '导购手机号',
        'salesperson_name' => ' 导购名称',
        'salesperson_distributor' => '导购门店名称',
        'member_id' => '会员ID',
        'member_mobile' => '会员手机号',
        'source' => '所属品牌',
        'member_created' => '会员注册时间',
        'member_bound' => '导购绑定时间',
    ];

    public function exportData($filter)
    {
        // ShopEx EcShopX Core Module
        $workWechatRelService = new WorkWechatRelService();
        $count = $workWechatRelService->workWechatRelRepository->count($filter);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis') . "salesperson";
        $workWechatRelList = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $workWechatRelList);
        return $result;
    }

    private function getLists($filter, $count)
    {
        // Log: 456353686f7058
        $title = $this->title;
        $companyId = $filter['company_id'];
        if ($count > 0) {
            $workWechatRelService = new WorkWechatRelService();
            $memberService = new MemberService();
            $salespersonService = new SalespersonService();

            $limit = 100;
            $fileNum = ceil($count / $limit);
            for ($page = 1; $page <= $fileNum; $page++) {
                $workWechatRelData = [];
                $list = $workWechatRelService->workWechatRelRepository->getLists($filter, 'id,user_id,salesperson_id,bound_time', $page, $limit);

                $userId = array_column($list, 'user_id');
                $memberFilter = [
                    'user_id' => $userId
                ];
                $memberListTemp = $memberService->membersRepository->getDataList($memberFilter, 'user_id,company_id,mobile,created');
                $memberList = array_column($memberListTemp, null, 'user_id');


                $salespersonId = array_column($list, 'salesperson_id');
                $salespersonFilter = [
                    'salesperson_id' => $salespersonId
                ];
                $salespersonListTemp = $salespersonService->salesperson->getLists($salespersonFilter, 'salesperson_id,shop_name,name,mobile');
                $salespersonList = array_column($salespersonListTemp, null, 'salesperson_id');
                foreach ($list as $key => $value) {
                    foreach ($title as $k => $v) {
                        if ($k == "salesperson_id" && isset($value[$k])) {
                            $workWechatRelData[$key][$k] = $value[$k];
                        } elseif ($k == "salesperson_mobile" && isset($salespersonList[$value['salesperson_id']])) {
                            $workWechatRelData[$key][$k] = $salespersonList[$value['salesperson_id']]['mobile'];
                        } elseif ($k == "salesperson_name" && isset($salespersonList[$value['salesperson_id']])) {
                            $workWechatRelData[$key][$k] = $salespersonList[$value['salesperson_id']]['name'];
                        } elseif ($k == "salesperson_distributor" && isset($salespersonList[$value['salesperson_id']])) {
                            $workWechatRelData[$key][$k] = $salespersonList[$value['salesperson_id']]['shop_name'];
                        } elseif ($k == "member_id" && isset($memberList[$value['user_id']])) {
                            $workWechatRelData[$key][$k] = $memberList[$value['user_id']]['user_id'];
                        } elseif ($k == "member_mobile" && isset($memberList[$value['user_id']])) {
                            $workWechatRelData[$key][$k] = $memberList[$value['user_id']]['mobile'];
                        } elseif ($k == "source" && isset($memberList[$value['user_id']])) {
                            $workWechatRelData[$key][$k] = $memberList[$value['user_id']]['company_id'];
                        } elseif ($k == "member_created" && isset($memberList[$value['user_id']])) {
                            $workWechatRelData[$key][$k] = date('Y-m-d H:i:s', $memberList[$value['user_id']]['created']);
                        } elseif ($k == "member_bound" && isset($value['bound_time'])) {
                            $workWechatRelData[$key][$k] = date('Y-m-d H:i:s', $value['bound_time']);
                        } else {
                            $workWechatRelData[$key][$k] = '-';
                        }
                    }
                }
                yield $workWechatRelData;
            }
        }
    }
}
