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

use EmployeePurchaseBundle\Entities\MemberActivityItemsAggregate;
use EmployeePurchaseBundle\Services\ActivityItemsService;

class MemberActivityItemsAggregateService
{
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(MemberActivityItemsAggregate::class);
    }

    public function addItemAggregate($companyId, $enterpriseId, $activityId, $userId, $itemId, $fee, $num)
    {
        $activityItemsService = new ActivityItemsService();
        $activityItem = $activityItemsService->getInfo(['company_id' => $companyId, 'activity_id' => $activityId, 'item_id' => $itemId]);
        if (!$activityItem) {
            throw new ResourceException('商品不参与活动');
        }

        try {
            $key = 'addItemAggregate_'.$companyId.'_'.$enterpriseId.'_'.$activityId.'_'.$userId;

            $succ = app('redis')->setnx($key, 1);
            while (!$succ) {
                usleep(rand(1000, 1000000));
                $succ = app('redis')->setnx($key, 1);
            }

            $aggregateInfo = $this->entityRepository->getInfo(['company_id' => $companyId, 'enterprise_id' => $enterpriseId, 'activity_id' => $activityId, 'user_id' => $userId, 'item_id' => $itemId]);
            if (!$aggregateInfo) {
                $qtyExceed = $activityItem['limit_num'] > 0 && $num > $activityItem['limit_num'];
                $feeExceed = $activityItem['limit_fee'] > 0 && $fee > $activityItem['limit_fee'];
                if ($qtyExceed && $feeExceed) {
                    throw new ResourceException(EmployeePurchaseItemLimitValidator::MSG_FEE);
                }
                if ($feeExceed) {
                    throw new ResourceException(EmployeePurchaseItemLimitValidator::MSG_FEE);
                }
                if ($qtyExceed) {
                    throw new ResourceException(EmployeePurchaseItemLimitValidator::MSG_NUM);
                }

                $data = [
                    'company_id' => $companyId,
                    'enterprise_id' => $enterpriseId,
                    'activity_id' => $activityId,
                    'user_id' => $userId,
                    'item_id' => $itemId,
                    'aggregate_fee' => $fee,
                    'aggregate_num' => $num,
                ];
                $this->entityRepository->create($data);
            } else {
                $data = [
                    'aggregate_fee' => $aggregateInfo['aggregate_fee'] + $fee,
                    'aggregate_num' => $aggregateInfo['aggregate_num'] + $num,
                ];

                $qtyExceed = $activityItem['limit_num'] > 0 && $data['aggregate_num'] > $activityItem['limit_num'];
                $feeExceed = $activityItem['limit_fee'] > 0 && $data['aggregate_fee'] > $activityItem['limit_fee'];
                if ($qtyExceed && $feeExceed) {
                    throw new ResourceException(EmployeePurchaseItemLimitValidator::MSG_FEE);
                }
                if ($feeExceed) {
                    throw new ResourceException(EmployeePurchaseItemLimitValidator::MSG_FEE);
                }
                if ($qtyExceed) {
                    throw new ResourceException(EmployeePurchaseItemLimitValidator::MSG_NUM);
                }

                $filter = [
                    'company_id' => $companyId,
                    'enterprise_id' => $enterpriseId,
                    'activity_id' => $activityId,
                    'user_id' => $userId,
                    'item_id' => $itemId,
                ];
                $this->entityRepository->updateBy($filter, $data);
            }
            app('redis')->del($key);
        } catch (\Exception $e) {
            app('redis')->del($key);
            throw new ResourceException($e->getMessage());
        }
    }

    public function minusItemAggregate($companyId, $enterpriseId, $activityId, $userId, $itemId, $fee, $num)
    {
        try {
            $key = 'minusItemAggregate_'.$companyId.'_'.$enterpriseId.'_'.$activityId.'_'.$userId;

            $succ = app('redis')->setnx($key, 1);
            while (!$succ) {
                usleep(rand(1000, 1000000));
                $succ = app('redis')->setnx($key, 1);
            }

            $aggregateInfo = $this->entityRepository->getInfo(['company_id' => $companyId, 'enterprise_id' => $enterpriseId, 'activity_id' => $activityId, 'user_id' => $userId, 'item_id' => $itemId]);
            if (!$aggregateInfo || $aggregateInfo['aggregate_fee'] < $fee || $aggregateInfo['aggregate_num'] < $num) {
                throw new ResourceException('商品限额返还失败');
            }
            $data = [
                'aggregate_fee' => $aggregateInfo['aggregate_fee'] - $fee,
                'aggregate_num' => $aggregateInfo['aggregate_num'] - $num,
            ];

            $filter = [
                'company_id' => $companyId,
                'enterprise_id' => $enterpriseId,
                'activity_id' => $activityId,
                'user_id' => $userId,
                'item_id' => $itemId,
            ];
            $this->entityRepository->updateBy($filter, $data);
            app('redis')->del($key);
        } catch (\Exception $e) {
            app('redis')->del($key);
            throw new ResourceException($e->getMessage());
        }
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
