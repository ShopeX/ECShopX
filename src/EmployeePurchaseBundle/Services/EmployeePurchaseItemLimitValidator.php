<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 */

namespace EmployeePurchaseBundle\Services;

use Dingo\Api\Exception\ResourceException;

/**
 * 内购活动商品每人限购数、每人限购额（与历史聚合）统一校验。
 */
final class EmployeePurchaseItemLimitValidator
{
    public const MSG_FEE = '商品购买金额已达限额';

    public const MSG_NUM = '商品购买数量已达限额';

    /**
     * @param  array  $activityItemByItemId  item_id => row（含 limit_num、limit_fee）
     * @param  array  $aggregateByItemId     item_id => row（含 aggregate_num、aggregate_fee，可缺省）
     * @param  array  $linesOrdered          [['item_id'=>,'num'=>int,'item_fee'=>int 分], ...] 展示/请求顺序
     */
    public static function assertWithPreloadedData(array $activityItemByItemId, array $aggregateByItemId, array $linesOrdered): void
    {
        $merged = self::mergeLinesByFirstAppearance($linesOrdered);
        foreach ($merged as $line) {
            $itemId = $line['item_id'];
            if (!isset($activityItemByItemId[$itemId])) {
                throw new ResourceException('商品未参加内购活动');
            }
            $limitNum = (int) ($activityItemByItemId[$itemId]['limit_num'] ?? 0);
            $limitFee = (int) ($activityItemByItemId[$itemId]['limit_fee'] ?? 0);
            $aggNum = isset($aggregateByItemId[$itemId]) ? (int) $aggregateByItemId[$itemId]['aggregate_num'] : 0;
            $aggFee = isset($aggregateByItemId[$itemId]) ? (int) $aggregateByItemId[$itemId]['aggregate_fee'] : 0;
            $newNum = $aggNum + (int) $line['num'];
            $newFee = $aggFee + (int) $line['item_fee'];
            $qtyExceed = $limitNum > 0 && $newNum > $limitNum;
            $feeExceed = $limitFee > 0 && $newFee > $limitFee;
            if ($qtyExceed && $feeExceed) {
                throw new ResourceException(self::MSG_FEE);
            }
            if ($feeExceed) {
                throw new ResourceException(self::MSG_FEE);
            }
            if ($qtyExceed) {
                throw new ResourceException(self::MSG_NUM);
            }
        }
    }

    /**
     * @param  array  $context  company_id, enterprise_id, activity_id, user_id
     * @param  array  $linesOrdered 每行含 item_id、num、item_fee（分）
     */
    public static function assertFromContextAndLines(array $context, array $linesOrdered): void
    {
        $itemIds = array_values(array_unique(array_column($linesOrdered, 'item_id')));
        if ($itemIds === []) {
            return;
        }
        $activityItemsService = new ActivityItemsService();
        $activityItemList = $activityItemsService->getLists([
            'company_id' => $context['company_id'],
            'activity_id' => $context['activity_id'],
            'item_id' => $itemIds,
        ]);
        $activityItemByItemId = array_column($activityItemList, null, 'item_id');

        $memberActivityItemsAggregateService = new MemberActivityItemsAggregateService();
        $aggregateList = $memberActivityItemsAggregateService->getLists([
            'company_id' => $context['company_id'],
            'enterprise_id' => $context['enterprise_id'],
            'user_id' => $context['user_id'],
            'activity_id' => $context['activity_id'],
            'item_id' => $itemIds,
        ]);
        $aggregateByItemId = array_column($aggregateList, null, 'item_id');

        self::assertWithPreloadedData($activityItemByItemId, $aggregateByItemId, $linesOrdered);
    }

    /**
     * @return list<array{item_id:mixed,num:int,item_fee:int}>
     */
    private static function mergeLinesByFirstAppearance(array $linesOrdered): array
    {
        $bucket = [];
        $order = [];
        foreach ($linesOrdered as $line) {
            $id = $line['item_id'];
            if (!isset($bucket[$id])) {
                $bucket[$id] = ['item_id' => $id, 'num' => 0, 'item_fee' => 0];
                $order[] = $id;
            }
            $bucket[$id]['num'] += (int) $line['num'];
            $bucket[$id]['item_fee'] += (int) $line['item_fee'];
        }
        $merged = [];
        foreach ($order as $id) {
            $merged[] = $bucket[$id];
        }

        return $merged;
    }
}
