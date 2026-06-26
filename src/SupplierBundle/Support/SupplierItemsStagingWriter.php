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

namespace SupplierBundle\Support;

class SupplierItemsStagingWriter
{
    /**
     * @return array{main: array<string, mixed>, draft_meta: array<string, mixed>, draft_content: array<string, mixed>}
     */
    public static function prepareStagedUpdate(array $incomingData, array $mainRow, array $specParams, $goodsId)
    {
        $split = SupplierItemsDraftFields::splitRow($incomingData);
        $sourceItemId = $specParams['item_id'];

        return [
            'main' => $split['main'],
            'draft_meta' => [
                'source_item_id' => $sourceItemId,
                'goods_id' => $goodsId ?: ($mainRow['goods_id'] ?? $sourceItemId),
                'company_id' => $incomingData['company_id'],
                'supplier_id' => $incomingData['supplier_id'] ?? ($mainRow['supplier_id'] ?? 0),
                'default_item_id' => $incomingData['default_item_id'] ?? ($mainRow['default_item_id'] ?? null),
                'is_default' => $specParams['is_default'] ?? ($mainRow['is_default'] ?? 0),
            ],
            'draft_content' => $split['content'],
        ];
    }

    public static function shouldBlockPlatformContentSync($stagingActive, $hasPendingDraft)
    {
        return $stagingActive || $hasPendingDraft;
    }
}
