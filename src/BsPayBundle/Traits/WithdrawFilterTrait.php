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

namespace BsPayBundle\Traits;

use Dingo\Api\Exception\ResourceException;

trait WithdrawFilterTrait
{
    /**
     * 构建提现记录筛选条件
     * @param array $params 请求参数
     * @param object $user 当前用户
     * @return array 过滤条件
     */
    protected function buildWithdrawFilter($params, $user)
    {
        // 1. 参数验证
        $rules = [
            'status' => ['nullable|integer', '状态必须为整数'],
            'type' => ['nullable|in:list,audit', '列表类型只能是list或audit']
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        // 2. 获取基本参数
        $status = $params['status'] ?? null;
        $type = $params['type'] ?? 'list'; // 默认为提现列表

        // 3. 获取用户信息
        $companyId = $user->get('company_id');
        $operatorType = $user->get('operator_type');
        $operatorId = $user->get('operator_id');

        // 4. 构建基础过滤条件
        $filter = [
            'company_id' => $companyId
        ];

        // 5. 处理数据权限
        if ($type === 'list') {
            $filter['operator_type'] = $operatorType;
            // 管理员需要增加操作者ID过滤
            if (in_array($operatorType, ['admin', 'staff'])) {
                $filter['operator_id'] = $operatorId;
            }

            // 从登录信息中直接获取对应ID并验证
            if ($operatorType === 'distributor') {
                $distributorId = $user->get('distributor_id');
                if (!$distributorId) {
                    throw new ResourceException('分销商身份信息异常，请重新登录');
                }
                $filter['distributor_id'] = $distributorId;
                
            } elseif ($operatorType === 'merchant') {
                $merchantId = $user->get('merchant_id');
                if (!$merchantId) {
                    throw new ResourceException('商户身份信息异常，请重新登录');
                }
                $filter['merchant_id'] = $merchantId;
            } elseif ($operatorType === 'admin' || $operatorType === 'staff') {
                // 超级管理员和员工不需要额外验证
            } else {
                throw new ResourceException('无效的操作者类型');
            }
        } else {
            // 只允许超级管理员和员工审核
            if (!in_array($operatorType, ['admin', 'staff'])) {
                throw new ResourceException('只有超级管理员和员工可以审核提现申请');
            }
        }

        // 6. 添加状态过滤
        if ($status !== null) {
            $filter['status'] = $status;
        }

        // 7. 添加时间范围过滤
        if (isset($params['time_start'])) {
            $filter['created|gte'] = strtotime($params['time_start']);
        }
        if (isset($params['time_end'])) {
            $filter['created|lte'] = strtotime($params['time_end']);
        }

        return $filter;
    }
} 