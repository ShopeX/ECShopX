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

namespace EmployeePurchaseBundle\Support;

use Dingo\Api\Exception\ResourceException;

/**
 * 内购活动列表「展示态」status 查询解析，与 Api Activity::getActivityList 一致。
 */
final class ActivityListDisplayStatusQuery
{
    public const ALLOWED_STATUSES = ['not_started', 'warm_up', 'ongoing', 'pending', 'cancel', 'over'];

    /**
     * @param mixed $rawStatus 来自 query：字符串、逗号分隔、或重复参数数组
     * @return string[]|null 非空时返回去重后的 slug 列表，供 $filter['status|or']；无有效入参时返回 null
     */
    public static function statusSlugsForFilterOrNull($rawStatus): ?array
    {
        $statuses = self::collectSlugs($rawStatus);
        if ($statuses === []) {
            return null;
        }
        foreach ($statuses as $s) {
            if (!in_array($s, self::ALLOWED_STATUSES, true)) {
                throw new ResourceException('status 参数不合法');
            }
        }

        return $statuses;
    }

    /**
     * @param mixed $rawStatus
     * @return string[]
     */
    private static function collectSlugs($rawStatus): array
    {
        $statuses = [];
        if ($rawStatus === null || $rawStatus === '') {
            return [];
        }
        if (is_array($rawStatus)) {
            foreach ($rawStatus as $chunk) {
                if (!is_string($chunk) || $chunk === '') {
                    continue;
                }
                foreach (array_filter(array_map('trim', explode(',', $chunk))) as $s) {
                    $statuses[] = $s;
                }
            }
        } elseif (is_string($rawStatus)) {
            foreach (array_filter(array_map('trim', explode(',', $rawStatus))) as $s) {
                $statuses[] = $s;
            }
        }

        return array_values(array_unique($statuses));
    }
}
