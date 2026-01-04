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

namespace SelfserviceBundle\Services;

use PromotionsBundle\Entities\WxaNoticeTemplate;
use PromotionsBundle\Services\MarketingActivityService;
use SelfserviceBundle\Entities\RegistrationActivity;
use PromotionsBundle\Services\SmsService;
use SelfserviceBundle\Jobs\SendWxRemindJob;

class RegistrationActivityService
{
    /** @var \SelfserviceBundle\Repositories\RegistrationActivityRepository */
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(RegistrationActivity::class);
    }
    
    /**
     *  活动开始提醒
     */
    public function scheduleSendWxRemindMsg()
    {
        //获取当前开启的活动开始提醒
        $filter = [
            'scenes_name' => 'registrationActivityNotice', 
            'template_name' => 'yykweishop', 
            'is_open' => 1,
        ];
        /** @var \PromotionsBundle\Repositories\WxaNoticeTemplateRepository $wxaNoticeTemplateRepository */
        $wxaNoticeTemplateRepository = app('registry')->getManager('default')->getRepository(WxaNoticeTemplate::class);
        $rsTemplates = $wxaNoticeTemplateRepository->getLists($filter);
        if (!$rsTemplates) {
            return true;
        }
        foreach ($rsTemplates as $v) {
            //微信模板id不能为空
            if (!$v['template_id'] or !$v['send_time_desc']) {
                continue;
            }
            //是否存在符合活动开始时间范围的报名活动
            //send_time_desc {"title":"\u6d3b\u52a8\u5f00\u59cb\u524d","time_list":[2,4,8,12,24,48,72],"value":24,"time_unit":"\u5c0f\u65f6","end_title":"\uff0c\u89e6\u53d1\u901a\u77e5"}
            $send_time_desc = json_decode($v['send_time_desc'], true);
            if (!$send_time_desc) {
                continue;
            }
            $remind_hour = intval($send_time_desc['value'] ?? 0);
            if (!$remind_hour) {
                continue;
            }
            //提醒的活动开始时间，当前时间+提前小时数+一小时
            //7:30 执行范围：8:30 - 9:30
            //8:30 执行范围：9:30 - 10:30
            $remind_hour ++;
            $remind_start_time = time() + $remind_hour * 3600;
            $remind_end_time = $remind_start_time + 3600;
            
            $_filter = [
                'company_id' => $v['company_id'],
                'start_time|gte' => $remind_start_time,
                'start_time|lt' => $remind_end_time,
                'is_wxapp_notice' => 1,
            ];
            $rsActivity = $this->entityRepository->getLists($_filter);
            foreach ($rsActivity as $vv) {
                $queue = (new SendWxRemindJob($vv['activity_id']))->onQueue('slow');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($queue);
            }
        }
        return true;
    }
    
    public function getStatusName(&$activityInfo = [])
    {
        if ($activityInfo['start_time'] > time()) {
            $activityInfo['status'] = 'waiting';
            $activityInfo['status_name'] = trans('SelfserviceBundle.status_waiting');
        } elseif ($activityInfo['end_time'] < time()) {
            $activityInfo['status'] = 'end';
            $activityInfo['status_name'] = trans('SelfserviceBundle.status_ended');
        }  else {
            $activityInfo['status'] = 'ongoing';
            $activityInfo['status_name'] = trans('SelfserviceBundle.status_ongoing');
        }
    }
    
    //检查报名活动是否有效
    public function checkActivityValid($params, &$err_msg = '')
    {
        $rsActivity = $this->entityRepository->getInfoById($params['activity_id']);
        if (!$rsActivity) {
            $err_msg = trans('SelfserviceBundle.activity_not_exist_err');
            return false;
        }
        if ($rsActivity['start_time']>time() or $rsActivity['end_time']<time()) {
            $err_msg = trans('SelfserviceBundle.activity_not_started_or_ended');
            return false;
        }
        if ($rsActivity['member_level']) {
            $member_level = explode(',', $rsActivity['member_level']);
            $marketingActivityService = new MarketingActivityService();
            $user_grade_id = $marketingActivityService->getUserGrade($params['user_id'], $params['company_id']);
            if (!$user_grade_id or !in_array($user_grade_id, $member_level)) {
                $err_msg = trans('SelfserviceBundle.only_specific_members_allowed');
                return false;
            }
        }

        if ($params['distributor_id']) {
            $registrationActivityRelShopService = new RegistrationActivityRelShopService();
            $rsRelShops = $registrationActivityRelShopService->entityRepository->getInfo(['activity_id' => $params['activity_id'], 'distributor_id' => [0, $params['distributor_id']]]);
            if (!$rsRelShops) {
                $err_msg = trans('SelfserviceBundle.only_specific_stores_allowed');
                return false;
            }
        }
        
        //获取已经参加活动的总人数
        if (!$params['record_id'] && $rsActivity['join_limit']) {
            $filter = [
                'activity_id' => $rsActivity['activity_id'],
            ];
            $registrationRecordService = new RegistrationRecordService();
            $total_join_num = $registrationRecordService->entityRepository->count($filter);
            if ($total_join_num >= $rsActivity['join_limit']) {
                $err_msg = trans('SelfserviceBundle.activity_quota_full');
                return false;
            }
        }

        $registrationRecordService = new RegistrationRecordService();
        if ($params['record_id']) {
            $record = $registrationRecordService->entityRepository->getInfoById($params['record_id']);
            if (!$record or $record['user_id'] != $params['user_id']) {
                $err_msg = trans('SelfserviceBundle.can_only_modify_own_registration');
                return false;
            }
            if (!in_array($record['status'], ['pending', 'rejected'])) {
                $err_msg = trans('SelfserviceBundle.current_registration_status_cannot_modify');
                return false;
            }
        } elseif (!$rsActivity['is_allow_duplicate']) {
            $_filter = [
                'company_id' => $params['company_id'],
                'user_id' => $params['user_id'],
                'activity_id' => $params['activity_id'],
            ];
            if ($registrationRecordService->entityRepository->count($_filter)) {
                $err_msg = trans('SelfserviceBundle.cannot_register_duplicate');
                return false;
            }
        }
        return $rsActivity;
    }

    public function saveData($params, $filter = [])
    {
        if ($params['distributor_ids']) {
            $distributor_ids = explode(',', $params['distributor_ids']);
            if (strlen($params['distributor_ids']) > 100) {
                //防止数据库字段溢出
                $params['distributor_ids'] = substr($params['distributor_ids'], 0, 100);
            }
        } elseif ($params['distributor_id']) {
            $distributor_ids = [$params['distributor_id']];
        } else {
            $distributor_ids = [];
        }
        $params['is_sms_notice'] = ($params['is_sms_notice'] ?? false) == 'true' ? true : false;
        $params['is_wxapp_notice'] = ($params['is_wxapp_notice'] ?? false) == 'true' ? true : false;
        
        $checkFields = ['gift_points', 'is_allow_duplicate', 'is_allow_cancel', 'is_offline_verify', 'is_need_check', 'is_white_list'];
        foreach ($checkFields as $v) {
            $params[$v] = intval($params[$v] ?? 0);
        }

        $arrFields = ['area', 'show_fields', 'pics'];
        foreach ($arrFields as $v) {
            if (isset($params[$v]) && is_array($params[$v])) {
                $params[$v] = json_encode($params[$v], 256);
            }
        }

        $arrFields = ['member_level', 'distributor_ids', 'enterprise_ids'];
        foreach ($arrFields as $v) {
            if (isset($params[$v]) && is_array($params[$v])) {
                $params[$v] = implode(',', $params[$v]);
            }
        }
        
        if ($filter) {
            $result = $this->entityRepository->updateOneBy($filter, $params);
        } else {
            $result = $this->entityRepository->create($params);
        }
        $activity_id = $result['activity_id'];
        
        //保存店铺 distributor_ids 关联数据 RegistrationActivityRelShop        
        $registrationActivityRelShopService = new RegistrationActivityRelShopService();
        $registrationActivityRelShopService->saveRelShops($activity_id, $distributor_ids);
        
        $this->updateSmsTempStatus($result['company_id'], $result['is_sms_notice']);
        return $result;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }

    private function updateSmsTempStatus($companyId, $isOpen)
    {
        $templateName = 'registration_result_notice';
        $params['is_open'] = ($isOpen == 'true') ? 'true' : 'false';
        $smsService = new SmsService();
        $result = $smsService->updateTemplate($companyId, $templateName, $params);
        return true;
    }
}
