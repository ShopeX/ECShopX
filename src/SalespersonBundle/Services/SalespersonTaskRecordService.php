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

namespace SalespersonBundle\Services;

use SalespersonBundle\Entities\SalespersonTaskRecord;
use SalespersonBundle\Entities\SalespersonTaskRecordLogs;
use SalespersonBundle\Entities\ShopsRelSalesperson;
use WorkWechatBundle\Jobs\sendTaskProgressNoticeJob;
use Dingo\Api\Exception\ResourceException;

/**
 * 导购任务 class
 */
class SalespersonTaskRecordService
{
    public $salespersonTaskRecordRepository;
    public $salespersonTaskRecordLogsRepository;

    public function __construct()
    {
        $this->salespersonTaskRecordRepository = app('registry')->getManager('default')->getRepository(SalespersonTaskRecord::class);
        $this->salespersonTaskRecordLogsRepository = app('registry')->getManager('default')->getRepository(SalespersonTaskRecordLogs::class);
    }

    /**
     * 完成下单任务方法
     *
     * @param int $params 下单任务相关参数
     * @return void
     */
    public function completeOrder($params)
    {
        $salespersonTaskService = new SalespersonTaskService();
        $params['task_type'] = $salespersonTaskService::TASK_TYPE_USER_ORDER;
        if ($this->checkParams($params)) {
            $remark = [
                'user_id' => $params['user_id'],
                'order_id' => $params['order_id'],
            ];
            $result = $this->completeTask($params, $remark);
            return $result;
        }
        return false;
    }

    /**
     * 完成分享任务方法
     *
     * @param int $params 分享任务相关参数
     * @return void
     */
    public function completeShare($params)
    {
        $salespersonTaskService = new SalespersonTaskService();
        $params['task_type'] = $salespersonTaskService::TASK_TYPE_SHARE;
        if ($this->checkParams($params)) {
            $remark = [
                'user_id' => $params['user_id'],
                'type' => $params['type'],
                'id' => $params['id'],
            ];
            $result = $this->completeTask($params, $remark);
            if (true == $result) {
                // 导购待发货通知
                $gotoJob = (new sendTaskProgressNoticeJob($params['company_id'], $params['task_id'], $params['salesperson_id'], $params['username']))->onQueue('slow');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
            }
            return $result ? true : false;
        }
        return false;
    }

    /**
     * 完成拉新用户任务方法
     *
     * @param array $params 拉新用户任务相关参数
     * @return bool|void
     */
    public function completeNewUser($params)
    {
        $salespersonTaskService = new SalespersonTaskService();
        $params['task_type'] = $salespersonTaskService::TASK_TYPE_NEWUSER;
        if ($this->checkParams($params)) {
            $remark = [
                'user_id' => $params['user_id'],
            ];
            $result = $this->completeTask($params, $remark);
            return $result;
        }
        return false;
    }

    /**
     * 完成福利任务方法
     *
     * @param int $params 福利任务相关参数
     * @return void
     */
    public function completeWelfare($params)
    {
        $salespersonTaskService = new SalespersonTaskService();
        $params['task_type'] = $salespersonTaskService::TASK_TYPE_USER_WELFARE;
        if ($this->checkParams($params)) {
            $remark = [
                'user_id' => $params['user_id'],
                'type' => $params['type'],
                'id' => $params['id'],
            ];
            $result = $this->completeTask($params, $remark);
            return $result;
        }
        return false;
    }

    /**
    * 完成用户前台获取优惠券方法
    *
    * 用户前台获取优惠券相关参数
    * @param  $params
    * @return bool
    */
    public function completeGetCoupon($params)
    {
        $salespersonTaskService = new SalespersonTaskService();
        $params['task_type'] = $salespersonTaskService::TASK_TYPE_USER_WELFARE;
        if ($this->checkParams($params)) {
            $remark = [
               'user_id' => $params['user_id'],
               'type' => $params['type'],
               'id' => $params['id'],
           ];
            $result = $this->completeTask($params, $remark);
            return $result;
        }
        return false;
    }

    /**
     * 完成任务统一方法
     *
     * 任务相关参数
     * @param $params
     * 任务备注
     * @param $remark
     * @return bool
     */
    public function completeTask($params, $remark)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $taskParam = [
                'company_id' => $params['company_id'],
                'salesperson_id' => $params['salesperson_id'],
                'task_id' => $params['task_id'],
                'distributor_id' => $params['distributor_id'],
            ];
            $this->salespersonTaskRecordRepository->add($taskParam);

            $taskLogParam = [
                'company_id' => $params['company_id'],
                'salesperson_id' => $params['salesperson_id'],
                'task_id' => $params['task_id'],
                'distributor_id' => $params['distributor_id'],
            ];
            $taskLogParam['remark'] = $remark;
            $this->salespersonTaskRecordLogsRepository->create($taskLogParam);
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e);
        }
    }

    /**
     * 检测并处理导购任务参数
     *
     * @param array $params
     * @return bool
     */
    public function checkParams(array &$params)
    {
        $shopsRelSalespersonRepository = app('registry')->getManager('default')->getRepository(ShopsRelSalesperson::class);
        $salespersonInfo = $shopsRelSalespersonRepository->getInfo(['salesperson_id' => $params['salesperson_id'], 'store_type' => 'distributor']);
        if (!$salespersonInfo) {
            return false;
        }
        $params['distributor_id'] = $salespersonInfo['shop_id'];
        $salespersonTaskService = new SalespersonTaskService();
        $taskInfo = $salespersonTaskService->getDistributorAccordTask($params['company_id'], $params['distributor_id'], $params['task_type']);
        if (!$taskInfo) {
            return false;
        }
        $params['task_id'] = $taskInfo['task_id'];
        return true;
    }


    /**
     * Dynamically call the SalespersonTaskRecordService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->salespersonTaskRecordRepository->$method(...$parameters);
    }
}
