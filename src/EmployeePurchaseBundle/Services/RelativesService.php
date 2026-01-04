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

use EmployeePurchaseBundle\Entities\Relatives;
use EmployeePurchaseBundle\Services\EmployeesService;
use EmployeePurchaseBundle\Services\ActivitiesService;

class RelativesService
{
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(Relatives::class);
    }

    public function check($companyId, $enterpriseId, $activityId, $userId)
    {
        $filter = [
            'company_id' => $companyId,
            'enterprise_id' => $enterpriseId,
            'activity_id' => $activityId,
            'user_id' => $userId,
            'disabled' => 0,
        ];
        return $this->entityRepository->getInfo($filter);
    }

    public function bindRelative($params)
    {
        $employeesService = new EmployeesService();
        list($enterpriseId, $activityId, $employeeUserId) = $employeesService->getInviteTicket($params['company_id'], $params['invite_code']);

        if (!$enterpriseId || !$activityId || !$employeeUserId) {
            throw new ResourceException('分享链接已失效');
        }

        $activitiesService = new ActivitiesService();
        $activity = $activitiesService->getInfo(['company_id' => $params['company_id'], 'id' => $activityId]);
        if (!$activity) {
            throw new ResourceException('活动不存在');
        }

        $invitor = $employeesService->check($params['company_id'], $enterpriseId, $employeeUserId);
        if (!$invitor) {
            throw new ResourceException('分享链接已失效');
        }

        $employee = $employeesService->check($params['company_id'], $enterpriseId, $params['user_id']);
        if ($employee) {
            throw new ResourceException('已经是员工，不可以绑定');
        }

        $relative = $this->check($params['company_id'], $enterpriseId, $activityId, $params['user_id']);
        if ($relative) {
            throw new ResourceException('已经是亲友，不需要重复绑定');
        }

        $inviteNum = $employeesService->getInviteNum($params['company_id'], $enterpriseId, $activityId, $employeeUserId);
        if ($inviteNum >= $activity['invite_limit']) {
            throw new ResourceException('已达到邀请上限');
        }

        $data = [
            'company_id' => $params['company_id'],
            'distributor_id' => $invitor['distributor_id'],
            'enterprise_id' => $enterpriseId,
            'activity_id' => $activityId,
            'employee_id' => $invitor['id'],
            'employee_user_id' => $invitor['user_id'],
            'user_id' => $params['user_id'],
            'member_mobile' => $params['member_mobile'],
        ];
        $result = $this->entityRepository->create($data);
        if (!$result) {
            throw new ResourceException('绑定成为亲友失败');
        }

        return true;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
