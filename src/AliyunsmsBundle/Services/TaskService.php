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

namespace AliyunsmsBundle\Services;
use AliyunsmsBundle\Entities\Task;
use Dingo\Api\Exception\ResourceException;
use MembersBundle\Services\MemberService;
use PromotionsBundle\Http\Api\V1\Action\Sms;
use PromotionsBundle\Services\SmsManagerService;

class TaskService
{
    public $repository;
    public function __construct()
    {
        $this->repository = app('registry')->getManager('default')->getRepository(Task::class);
    }

    /**
     * 新增群发任务
     * @param $params
     * @throws \Exception
     */
    public function addTask($params)
    {
        // ShopEx EcShopX Core Module
        if($params['send_at'] ?? 0) {
            if($params['send_at'] < time()) {
                throw new ResourceException("定时发送时间不能小于当前时间");
            }
        }
        $template = (new TemplateService())->getInfo(['id' => $params['template_id'], 'company_id' => $params['company_id'],'status' => 1,'template_type' => 2]);
        if(!$template) {
            throw new ResourceException("模板无效");
        }
        $sign = (new SignService())->getInfo(['id' => $params['sign_id'], 'company_id' => $params['company_id'],'status' => 1]);
        if(!$sign) {
            throw new ResourceException("签名无效");
        }
        $params['template_name'] = $template['template_name'];
        $memberService = new MemberService();
        if($params['user_id'] ?? 0) {
            $user_id = $params['user_id'];
            $total_num = count($user_id);
        } else {
            $memberList = $memberService->getMemberList(['company_id' => $params['company_id']],1,0);
            $user_id = array_column($memberList, 'user_id');
            $total_num = count($user_id);
        }
        //群发人数超过maxNum,按maxNum平均拆分任务
        $maxNum = 1000;
        $user_id = array_chunk($user_id, $maxNum);
        $i = 0;
        while($total_num / $maxNum > 0) {
            $total_num -= $maxNum;
            $params['total_num'] = $total_num >=0 ? $maxNum : $total_num + $maxNum;
            $params['user_id'] = implode(',', $user_id[$i++]);
            $this->repository->create($params);

        }
        return true;
    }

    /**
     * 编辑群发任务
     * @param $params
     * @throws \Exception
     */
    public function modifyTask($params)
    {
        // ShopEx EcShopX Core Module
        $task = $this->getInfo(['id' => $params['id'], 'company_id' => $params['company_id'], 'status' => '4']);
        if(!$task) {
            throw new ResourceException("已撤销的任务才能编辑");
        }
        if($params['send_at'] ?? 0) {
            if($params['send_at'] < time()) {
                throw new ResourceException("定时发送时间不能小于当前时间");
            }
        }
        $template = (new TemplateService())->getInfo(['id' => $params['template_id'], 'company_id' => $params['company_id'],'status' => 1,'template_type' => 2]);
        if(!$template) {
            throw new ResourceException("模板无效");
        }
        $sign = (new SignService())->getInfo(['id' => $params['sign_id'], 'company_id' => $params['company_id'],'status' => 1]);
        if(!$sign) {
            throw new ResourceException("签名无效");
        }
        $memberService = new MemberService();
        $id = $params['id'];
        unset($params['id']);
        $params['status'] = 1;
        $this->repository->updateOneBy(['id' => $id],$params);
        return true;
    }

    public function revokeTask($params)
    {
        $task = $this->getInfo(['id' => $params['id'], 'company_id' => $params['company_id'], 'status' => '1']);
        if(!$task) {
            throw new ResourceException("当前任务不能撤销");
        }
        if($task['send_at'] < time() + 5 * 60) {
            throw new ResourceException("发送前5分钟不能撤销");
        }
        $this->repository->updateOneBy(['id' => $params['id']],['status' => '4']);
        return true;
    }

    public function runTask()
    {
        $taskLit = $this->lists(['status' => 1, 'is_send' => 0,'send_at|lte' => time()]);
        $memberService = new MemberService();
        foreach ($taskLit['list'] as $task) {
            $smsService = new SmsService($task['company_id']);
            $list = $memberService->getMemberList(['user_id' => explode(',', $task['user_id'])],1,0);
            $mobileList = array_column($list, 'mobile');
            try {
                $smsService->runSmsTask($mobileList, $task);
                $this->updateOneBy(['id' => $task['id']], ['is_send' => 1]);
            } catch (\Exception $e) {
                app('log')->error('执行群发短信任务: =>'. $e->getMessage());
            }
        }
        return true;
    }

    public function updateStatus()
    {
        $taskLit = $this->lists(['status' => 1, 'is_send' => 1]);
        $recordService = new RecordService();
        foreach ($taskLit['list'] as $task) {
            $succNum = $recordService->count(['task_id' => $task['id'], 'status' => 3]);
            if($succNum > 0) {
                $this->updateOneBy(['id' => $task['id']], ['status' => 2]);
                continue;
            }
            $failedNum = $recordService->count(['task_id' => $task['id'], 'status' => 2]);
            if($failedNum == $task['total_num']) {
                $this->updateOneBy(['id' => $task['id']], ['status' => 3, 'failed_num' => $failedNum]);
            }
        }
    }
    /**
     * Dynamically call the CommentService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->repository->$method(...$parameters);
    }
}
