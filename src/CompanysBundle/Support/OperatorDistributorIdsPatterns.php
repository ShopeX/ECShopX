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

namespace CompanysBundle\Support;

class OperatorDistributorIdsPatterns
{
    /**
     * operators.distributor_ids 为 json_encode 结果：数字 ID 常为 "distributor_id":123，
     * 旧逻辑只匹配 "distributor_id":"123" 会漏数据。此处返回多段子串，由 OperatorsRepository::lists 做 OR contains。
     * 使用 123} / 123, / 123] 等后缀，避免 "distributor_id":12 误匹配 129。
     */
    public static function forDistributorId($distributorId): array
    {
        $id = (string) $distributorId;
        if ($id === '') {
            return [];
        }

        return array_values(array_unique(array_filter([
            '"distributor_id":"' . $id . '"',
            '"distributor_id":' . $id . '}',
            '"distributor_id":' . $id . ',',
            '"distributor_id":' . $id . ']',
        ])));
    }
}
