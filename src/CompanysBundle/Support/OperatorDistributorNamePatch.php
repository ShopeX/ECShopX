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

class OperatorDistributorNamePatch
{
    /**
     * 在 distributor_ids JSON 数组中，按 distributor_id 宽松匹配并更新 name。
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    public static function patchDistributorNameInJson(array $items, $targetId, string $newName): array
    {
        $targetIdStr = (string) $targetId;

        foreach ($items as $index => $item) {
            if (!isset($item['distributor_id'])) {
                continue;
            }
            if ((string) $item['distributor_id'] !== $targetIdStr) {
                continue;
            }
            $items[$index]['name'] = $newName;
        }

        return $items;
    }
}
