<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
                if ($activityItem['limit_fee'] > 0 && $activityItem['limit_fee'] < $fee ) {
                    throw new ResourceException('商品超过限额');
                }

                if ($activityItem['limit_num'] > 0 && $activityItem['limit_num'] < $num ) {
                    throw new ResourceException('商品超过限购数量');
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

                if ($activityItem['limit_fee'] > 0 && $activityItem['limit_fee'] < $data['aggregate_fee']) {
                    throw new ResourceException('商品超过限额');
                }

                if ($activityItem['limit_num'] > 0 && $activityItem['limit_num'] < $data['aggregate_num']) {
                    throw new ResourceException('商品超过限购数量');
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
