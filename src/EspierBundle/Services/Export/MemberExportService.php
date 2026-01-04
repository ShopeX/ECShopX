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
use MembersBundle\Services\MemberRegSettingService;
use MembersBundle\Services\MemberService;
use EspierBundle\Services\ExportFileService;
use MembersBundle\Traits\MemberSearchFilter;
use KaquanBundle\Services\MemberCardService;
use KaquanBundle\Services\VipGradeOrderService;
use DistributionBundle\Services\DistributorUserService;

class MemberExportService implements ExportFileInterface
{
    use MemberSearchFilter;
    public function exportData($filter)
    {
        // 是否需要数据脱敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        $memberService = new MemberService();
        $count = $memberService->getMemberCount($filter);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis')."member";
        $memberList = $this->getLists($filter, $count, $datapassBlock);
        app('log')->debug('会员队列导出: count:'. $count.' filter json:'. json_encode($filter).'memberList count'. count($memberList));

        $exportData = array_chunk(array_values($memberList), 500);
        $exportService = new ExportFileService();
        return $exportService->exportCsv($fileName, $this->title, $exportData);
    }

    private $title = [
            'user_card_code' => '会员卡编号',
            'mobile' => '会员手机号',
            'name' => '用户名',
            'sex' => ' 性别',
            'username' => '姓名',
            'created_date' => '注册时间',
            'shop_name' => '所属店铺',
            'store_name' => '所属门店',
            'vip_grade' => 'vip付费会员等级',
            'vip_day' => 'vip付费会员剩余天数',
            'svip_grade' => 'svip付费会员等级',
            'svip_day' => 'svip付费会员剩余天数',
            'grade_id' => '普通会员等级',
            'inviter_id' => '推荐人手机号',
            'birthday' => '出生日期',
            'address' => '家庭住址',
            'email' => '常用邮箱',
            'industry' => '从事行业',
            'income' => '年收入',
            'edu_background' => '学历',
            'habbit' => '爱好',
            'salesman' => '导购员',
            'unionid' => 'unionid',
            'open_id' => 'openid',
        ];

    private function getLists($filter, $count, $datapassBlock)
    {
        $title = $this->title;
        $companyId = $filter['company_id'];
        $regSettinService = new MemberRegSettingService();
        $regSetting = $regSettinService->getRegItem($companyId)['setting'];

        if ($count > 0) {
            $memberService = new MemberService();

            //获取商城会员等级
            $memberCardService = new MemberCardService();
            $userGrade = $memberCardService->getGradeListByCompanyId($companyId);
            $grade = [];
            if ($userGrade) {
                foreach ($userGrade as $val) {
                    $grade[$val['grade_id']] = $val['grade_name'];
                }
            }

            //获取vip相关信息
            $userVipGradeService = new VipGradeOrderService();
            $limit = 500;
            $fileNum = ceil($count / $limit);

            $memberData = [];
            for ($page = 1; $page <= $fileNum; $page++) {
                $list = $memberService->getMemberList($filter, $page, $limit);

                $inviterIds = array_column($list, 'inviter_id');
                $inviterList = $memberService->getMobileByUserIds($companyId, $inviterIds);

                $userIds = array_column($list, 'user_id');
                $vipGradeData = $userVipGradeService->getUserVipGrade($companyId, $userIds);

                foreach ($list as $key => $value) {
                    if ($datapassBlock) {
                        $value['mobile'] = data_masking('mobile', (string) $value['mobile']);
                        $value['username'] = data_masking('truename', (string) $value['username']);
                        $value['birthday'] = data_masking('birthday', (string) $value['birthday']);
                        $value['address'] = data_masking('detailedaddress', (string) $value['address']);
                    }
                    //会员注册时间组装
                    $created_date = $value['created_year'].'-'.$value['created_month'].'-'.$value['created_day'];

                    //会员爱好
                    $habbit_value = json_decode($value['habbit'], true);
                    if ($habbit_value) {
                        if (is_array($habbit_value)) {
                            foreach ($habbit_value as $isval) {
                                if (isset($isval['ischecked']) && $isval['ischecked'] == 'true') {
                                    $habbit[] = $isval['name'];
                                } elseif (!isset($isval['name']) && !isset($isval['ischecked']) && is_string($isval)) {
                                    $habbit[] = $isval;
                                }
                            }
                            if (isset($habbit)) {
                                $habbit_value = implode(',', $habbit);
                            } else {
                                $habbit_value = '-';
                            }
                        }
                    } else {
                        $habbit_value = '-';
                    }

                    //职业/学历/收入
                    $industry_val = $regSetting['industry']['items'][$value['industry']] ?? '-';
                    $income_val = $regSetting['income']['items'][$value['industry']] ?? '-';
                    $edu_background_val = $regSetting['edu_background']['items'][$value['industry']] ?? '-';

                    $tempData = [];
                    foreach ($title as $k => $v) {
                        if ($k == "sex" && isset($value[$k])) {
                            $tempData[$k] = ($value[$k] == 2) ? '女' : ($value[$k] == 1 ? '男' : '未知');
                        } elseif ($k == 'grade_id' && isset($value[$k])) {
                            $tempData[$k] = $grade[$value[$k]] ?? '-';
                        } elseif ($k == 'created_date' && $created_date) {
                            $tempData[$k] = $created_date ?? '-';
                        } elseif ($k == 'vip_grade' && $vipGradeData) {
                            $tempData[$k] = $vipGradeData[$value['user_id']]['vip']['grade_name'] ?? '-';
                        } elseif ($k == 'vip_day' && $vipGradeData) {
                            $tempData[$k] = $vipGradeData[$value['user_id']]['vip']['day'] ?? '-';
                        } elseif ($k == 'svip_grade' && $vipGradeData) {
                            $tempData[$k] = $vipGradeData[$value['user_id']]['svip']['grade_name'] ?? '-';
                        } elseif ($k == 'svip_day' && $vipGradeData) {
                            $tempData[$k] = $vipGradeData[$value['user_id']]['svip']['day'] ?? '-';
                        } elseif ($k == 'inviter_id' && isset($value[$k]) && $inviterList) {
                            $tempData[$k] = isset($inviterList[$value[$k]]) ? $inviterList[$value[$k]] : '-';
                            if ($datapassBlock && $tempData[$k] != '-') {
                                $tempData[$k] = data_masking('mobile', (string) $tempData[$k]);
                            }
                        } elseif ($k == 'salesman') {
                            $obj = new  DistributorUserService();
                            $salesmanInfo = $obj->getSalesmanInfo($value);
                            $tempData[$k] = $salesmanInfo ? $salesmanInfo['mobile'] : '-';
                        } elseif ($k == 'habbit') {
                            $tempData[$k] = $habbit_value;
                        } elseif ($k == 'industry') {
                            $tempData[$k] = $industry_val;
                        } elseif ($k == 'income') {
                            $tempData[$k] = $income_val;
                        } elseif ($k == 'edu_background') {
                            $tempData[$k] = $edu_background_val;
                        } elseif ($k == 'name') {
                            $tempData[$k] = $value[$k] ?: '-';
                        } elseif ($k == 'unionid') {
                            $tempData[$k] = $value[$k] ?: '-';
                        } elseif ($k == 'open_id') {
                            $tempData[$k] = $value[$k] ?: '-';
                        } elseif (isset($value[$k])) {
                            $tempData[$k] = $value[$k];
                        } else {
                            $tempData[$k] = '-';
                        }
                    }
                    $memberData[$value['user_id']] = $tempData;
                }
            }
            return $memberData;
        }
    }
}
