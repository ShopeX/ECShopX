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

namespace SelfserviceBundle\Jobs;

use EspierBundle\Jobs\Job;

use PromotionsBundle\Jobs\WxopenTemplateSend;
use PromotionsBundle\Services\WxaTemplateMsgService;
use SelfserviceBundle\Services\RegistrationActivityService;
use SelfserviceBundle\Services\RegistrationRecordService;

class SendWxRemindJob extends Job
{
    protected $activity_id;

    public function __construct($activity_id)
    {
        $this->activity_id = $activity_id;
    }

    //发送活动开始提醒的微信消息
    public function handle()
    {
        try {
            $wxaTemplateMsgService = new WxaTemplateMsgService();
            $activityService = new RegistrationActivityService();
            $recordService = new RegistrationRecordService();
            $activityInfo = $activityService->entityRepository->getInfoById($this->activity_id);
            if (!$activityInfo) {
                return true;
            }
            if ($activityInfo['end_time'] < time()) {
                return true;//活动已经结束
            }
            if (!$activityInfo['is_wxapp_notice']) {
                return true;//没有开启微信通知
            }
            $sendData = [];
            $wxaTemplateMsgData = [
                'activity_name' => $activityInfo['activity_name'],
                'activity_start_time' => date('Y-m-d H:i:s', $activityInfo['start_time']),
                'activity_end_time' => date('Y-m-d H:i:s', $activityInfo['end_time']),
                'activity_address' => $activityInfo['address'],
            ];
            $sendData['data'] = $wxaTemplateMsgData;
            $sendData['scenes_name'] = 'registrationActivityNotice';
            $sendData['company_id'] = $activityInfo['company_id'];

            $filter = [
                'activity_id' => $this->activity_id,
                'status' => 'passed',//状态: pending 待审核，passed 已通过，rejected 已拒绝, canceled 已取消, verified 已核销
            ];
            $rsRecord = $recordService->entityRepository->getLists($filter);
            foreach ($rsRecord as $v) {      
                if (!$v['wxapp_appid'] or !$v['open_id']) {
                    continue;
                }
                $sendData['appid'] = $v['wxapp_appid'];
                $sendData['openid'] = $v['open_id'];
                //marketing/pages/member/activity-detail?record_id=123
                $sendData['page_query_str'] = "record_id=" . $v['record_id'];
                //执行异步发送微信消息的任务
                $job = (new WxopenTemplateSend($sendData, true))->onQueue('slow');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
            }            
        } catch (\Exception $e) {
            app('log')->error('SendWxRemindJob_error => activity_id:' . $this->activity_id . ', '.$e->getMessage().$e->getFile().$e->getLine());
        }
    }
}
