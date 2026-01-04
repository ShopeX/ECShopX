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

use SelfserviceBundle\Services\FormTemplateService;
use SelfserviceBundle\Services\RegistrationActivityService;
use SelfserviceBundle\Services\RegistrationRecordService;
use EspierBundle\Services\ExportFileService;
use EspierBundle\Interfaces\ExportFileInterface;

class RegistrationRecordExportService implements ExportFileInterface
{
    private $title = [
        'record_no' => '报名编号',
        'mobile' => '会员手机号',
        'activity_name' => '活动名称',
        'group_no' => '活动分组编码',
        'get_points' => '获取积分',
        'is_white_list' => '进白名单',
        'tem_name' => '来源表单',
        'status_name' => '状态',
        'reason' => '拒绝原因',
        'created' => '申请时间',
        // 'content' => '申请内容',        
    ];

    public function exportData($filter)
    {
        //这里需要导出自定义表单的所有输入项，所以每次只能导出一个活动的报名数据
        $filter['activity_id'] = $filter['activity_id'] ?? 0;
        // 是否需要数据脱敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        $recordService = new RegistrationRecordService();
        $count = $recordService->count($filter);
        if (!$count) {
            return [];
        }

        $this->setTitle($filter['activity_id']);
        
        $fileName = date('YmdHis').'_registration_record';
        $datalist = $this->getLists($filter, $count, $datapassBlock);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $datalist);
        return $result;
    }
    
    //处理表单的输入项
    public function setTitle($activity_id = 0)
    {
        $title = $this->title;
        if ($activity_id) {
            $activityService = new RegistrationActivityService();
            $activityInfo = $activityService->entityRepository->getInfoById($activity_id);
            //根据活动关联的表单，获取表单的输入项，追加到导出的csv表头里
            if ($activityInfo && $activityInfo['temp_id']) {
                $formTemplateService = new FormTemplateService();
                $rs = $formTemplateService->entityRepository->getInfoById($activityInfo['temp_id']);
                foreach ($rs['content'] as $v) {
                    if (!isset($v['formdata'])) continue;
                    foreach ($v['formdata'] as $vv) {
                        $title['row' . $vv['id']] = $this->replaceSpecialChar($vv['field_title']);
                    }
                }
            }
        }        
        $this->title = $title;
    }

    private function getLists($filter, $count, $datapassBlock)
    {
        $title = $this->title;
        if ($count > 0) {
            $recordService = new RegistrationRecordService();

            $limit = 500;
            $fileNum = ceil($count / $limit);

            for ($page = 1; $page <= $fileNum; $page++) {
                $recordData = [];
                $data = $recordService->getRocordList($filter, $page, $limit, ["created" => "DESC"]);
                foreach ($data['list'] as $key => $value) {                    
                    $string = [];
                    $conten = is_array($value['content']) ? $value['content'] : json_decode($value['content'], true);
                    if ($datapassBlock) {
                        $value['mobile'] = data_masking('mobile', (string) $value['mobile']);
                        $conten = $recordService->fixeddecryptRocordContent($conten, $datapassBlock);
                    }
                    foreach ($conten as $card) {
                        foreach ($card['formdata'] as $line) {
                            if (isset($line['answer']) && is_array($line['answer'])) {
                                $answer = implode(';', $line['answer']);
                            } else {
                                $answer = isset($line['answer']) && $line['answer'] ? $line['answer'] : '无';
                            }
                            $string[] = $line['field_title']. "：".$answer;                            
                            $value['row' . $line['id']] = $answer;//处理表单的输入项
                        }
                    }
                    // $contentStr = implode(';'.PHP_EOL, $string);
                    $row = [];
                    foreach ($title as $k => $v) {
                        switch ($k) {
                            case 'created':
                                $row[$k] = date('Y-m-d H:i:s', $value[$k]);
                                break;
                                
                            // case 'content':
                            //     $row[$k] = $contentStr;
                            //     break;
                                
                            // case 'review_result':
                            //     $row[$k] = ($value['status'] == 'passed') ? '已通过' : ($value['status'] == 'rejected' ? '已拒绝' : '待审核');
                            //     break;
                                
                            default:
                                $row[$k] = $this->replaceSpecialChar($value[$k] ?? '');
                                break;
                        }
                    }
                    $recordData[] = $row;
                }
                yield $recordData;
            }
        }
    }
    
    public function replaceSpecialChar($str) 
    {
        if (!$str) return $str;
        if (is_numeric($str)) $str .= "\t";
        return str_replace(["\r", "\n", ',', '"'], '；', $str);
    }
}
