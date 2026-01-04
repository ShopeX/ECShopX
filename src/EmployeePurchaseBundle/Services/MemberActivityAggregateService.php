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

namespace EmployeePurchaseBundle\Services;

use Dingo\Api\Exception\ResourceException;

use EmployeePurchaseBundle\Entities\MemberActivityAggregate;
use EmployeePurchaseBundle\Services\ActivitiesService;
use EmployeePurchaseBundle\Services\EmployeesService;
use EmployeePurchaseBundle\Services\RelativesService;

class MemberActivityAggregateService
{
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(MemberActivityAggregate::class);
    }


    public function getAggregateFee($companyId, $enterpriseId, $activityId, $userId) {
        $activityService = new ActivitiesService();
        $activity = $activityService->getInfo(['company_id' => $companyId, 'id' => $activityId]);
        if (!$activity) {
            throw new ResourceException('活动不存在');
        }

        if (!in_array($enterpriseId, $activity['enterprise_id'])) {
            throw new ResourceException('企业不参与该活动');
        }

        $employeesService = new EmployeesService();
        $relativesService = new RelativesService();
        $employee = $employeesService->check($companyId, $enterpriseId, $userId);
        if (!$employee) {
            $relative = $relativesService->check($companyId, $enterpriseId, $activityId, $userId);
            if (!$relative) {
                return ['limit_fee' => 0, 'aggregate_fee' => 0, 'left_fee' => 0];
            }

            $employeeUserId = $relative['employee_user_id'];
        } else {
            $employeeUserId = $employee['user_id'];
        }

        if ($activity['if_share_limitfee']) {
            $relatives = $relativesService->getLists(['company_id' => $companyId, 'enterprise_id' => $enterpriseId, 'employee_user_id' => $employeeUserId, 'activity_id' => $activityId, 'disabled' => 0], 'user_id');
            $userIds = array_column($relatives, 'user_id');
            $userIds[] = $employeeUserId;
            $aggregateFee = $this->entityRepository->sum(['company_id' => $companyId, 'enterprise_id' => $enterpriseId, 'activity_id' => $activityId, 'user_id' => $userIds]);
            return ['limit_fee' => $activity['employee_limitfee'], 'aggregate_fee' => $aggregateFee, 'left_fee' => bcsub($activity['employee_limitfee'], $aggregateFee)];
        } else {
            $aggregateFee = $this->entityRepository->sum(['company_id' => $companyId, 'enterprise_id' => $enterpriseId, 'activity_id' => $activityId, 'user_id' => $userId]);
            if ($employee) {
                return ['limit_fee' => $activity['employee_limitfee'], 'aggregate_fee' => $aggregateFee, 'left_fee' => bcsub($activity['employee_limitfee'], $aggregateFee)];
            } else {
                return ['limit_fee' => $activity['relative_limitfee'], 'aggregate_fee' => $aggregateFee, 'left_fee' => bcsub($activity['relative_limitfee'], $aggregateFee)];
            }
        }
    }

    public function addAggregateFee($companyId, $enterpriseId, $activityId, $userId, $fee)
    {
        $activityService = new ActivitiesService();
        $activity = $activityService->getInfo(['company_id' => $companyId, 'id' => $activityId]);
        if (!$activity) {
            throw new ResourceException('活动不存在');
        }

        if (!in_array($enterpriseId, $activity['enterprise_id'])) {
            throw new ResourceException('企业不参与该活动');
        }

        $employeesService = new EmployeesService();
        $relativesService = new RelativesService();
        $employee = $employeesService->check($companyId, $enterpriseId, $userId);
        if (!$employee) {
            $relative = $relativesService->check($companyId, $enterpriseId, $activityId, $userId);
            if (!$relative) {
                throw new ResourceException('既不是员工也不是亲友');
            }

            $employeeUserId = $relative['employee_user_id'];
        } else {
            $employeeUserId = $employee['user_id'];
        }

        try {
            if ($activity['if_share_limitfee']) {
                $key = 'addAggregateFee_'.$companyId.'_'.$enterpriseId.'_'.$activityId.'_'.$employeeUserId;
            } else {
                $key = 'addAggregateFee_'.$companyId.'_'.$enterpriseId.'_'.$activityId.'_'.$userId;
            }

            $succ = app('redis')->setnx($key, 1);
            while (!$succ) {
                usleep(rand(1000, 1000000));
                $succ = app('redis')->setnx($key, 1);
            }

            if ($activity['if_share_limitfee']) {
                $relatives = $relativesService->getLists(['company_id' => $companyId, 'enterprise_id' => $enterpriseId, 'employee_user_id' => $employeeUserId, 'activity_id' => $activityId, 'disabled' => 0], 'user_id');
                $userIds = array_column($relatives, 'user_id');
                $userIds[] = $employeeUserId;
                $aggregateFee = $this->entityRepository->sum(['company_id' => $companyId, 'enterprise_id' => $enterpriseId, 'activity_id' => $activityId, 'user_id' => $userIds]);
                if ($aggregateFee + $fee > $activity['employee_limitfee']) {
                    throw new ResourceException('超过共享额度');
                }
            } else {
                $aggregateFee = $this->entityRepository->sum(['company_id' => $companyId, 'enterprise_id' => $enterpriseId, 'activity_id' => $activityId, 'user_id' => $userId]);
                if ($employee) {
                    if ($aggregateFee + $fee > $activity['employee_limitfee']) {
                        throw new ResourceException('超过员工额度');
                    }
                } else {
                    if ($aggregateFee + $fee > $activity['relative_limitfee']) {
                        throw new ResourceException('超过家属额度');
                    }
                }
            }

            $aggregateInfo = $this->entityRepository->getInfo(['company_id' => $companyId, 'enterprise_id' => $enterpriseId, 'activity_id' => $activityId, 'user_id' => $userId]);
            if (!$aggregateInfo) {
                $data = [
                    'company_id' => $companyId,
                    'enterprise_id' => $enterpriseId,
                    'activity_id' => $activityId,
                    'user_id' => $userId,
                    'aggregate_fee' => $fee,
                ];
                $this->entityRepository->create($data);
            } else {
                $data = [
                    'aggregate_fee' => $aggregateInfo['aggregate_fee'] + $fee,
                ];
                $filter = [
                    'company_id' => $companyId,
                    'enterprise_id' => $enterpriseId,
                    'activity_id' => $activityId,
                    'user_id' => $userId,
                ];
                $this->entityRepository->updateBy($filter, $data);
            }
            app('redis')->del($key);
        } catch (\Exception $e) {
            app('redis')->del($key);
            throw new ResourceException($e->getMessage());
        }

        return true;
    }

    public function minusAggregateFee($companyId, $enterpriseId, $activityId, $userId, $fee)
    {
        try {
            $key = 'minusAggregateFee_'.$companyId.'_'.$enterpriseId.'_'.$activityId.'_'.$userId;

            $succ = app('redis')->setnx($key, 1);
            while (!$succ) {
                usleep(rand(1000, 1000000));
                $succ = app('redis')->setnx($key, 1);
            }

            $aggregateInfo = $this->entityRepository->getInfo(['company_id' => $companyId, 'enterprise_id' => $enterpriseId, 'activity_id' => $activityId, 'user_id' => $userId]);
            if (!$aggregateInfo || $aggregateInfo['aggregate_fee'] < $fee) {
                throw ResourceException('额度返还失败');
            }

            $data = [
                'aggregate_fee' => $aggregateInfo['aggregate_fee'] - $fee,
            ];
            $filter = [
                'company_id' => $companyId,
                'enterprise_id' => $enterpriseId,
                'activity_id' => $activityId,
                'user_id' => $userId,
            ];
            $this->entityRepository->updateBy($filter, $data);
            app('redis')->del($key);
        } catch (\Exception $e) {
            app('redis')->del($key);
            throw new ResourceException($e->getMessage());
        }

        return true;
    }


    public function getUserActivityDataList(int $companyId, int $userId,array $activityList, int $enterpriseId)
    {
        foreach ($activityList as $index => $activity) {
            $activityList[$index]['fee'] = $this->getAggregateFee($companyId,$enterpriseId,$activity['id'],$userId);
        }
        return $activityList;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
